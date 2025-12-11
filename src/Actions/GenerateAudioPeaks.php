<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GenerateAudioPeaks
{
    /**
     * Generate audio peaks data
     *
     * @param  string  $filePath  Path to the audio/video file
     * @param  int  $samplesPerPixel  Number of audio samples per waveform min/max pair
     * @param  array|null  $normalizeRange  Normalization range [min, max] or null for no normalization
     *                                      Examples: [0, 1] for wavesurfer.js, [-1, 1] for signed normalized, null for raw values (-32768 to 32767)
     * @return array The peaks data
     *
     * @throws ExecutionException
     */
    public function execute(string $filePath, int $samplesPerPixel = 512, ?array $normalizeRange = null): array
    {
        if ($normalizeRange !== null && count($normalizeRange) !== 2) {
            throw new \InvalidArgumentException('normalizeRange must be an array with exactly 2 values [min, max] or null');
        }

        $ffmpegPath = config('fluent-ffmpeg.ffmpeg_path', 'ffmpeg');
        $ffprobePath = config('fluent-ffmpeg.ffprobe_path', 'ffprobe');

        // First, get media info to determine sample rate and channels
        $mediaInfo = $this->getMediaInfo($ffprobePath, $filePath);

        $sampleRate = $mediaInfo['sample_rate'];
        $channels = $mediaInfo['channels'];

        // Stream and process PCM data in chunks
        $peaks = $this->streamAndCalculatePeaks($ffmpegPath, $filePath, $sampleRate, $channels, $samplesPerPixel, $normalizeRange);

        // Build the response structure
        return [
            'version' => 2,
            'channels' => $channels,
            'sample_rate' => $sampleRate,
            'samples_per_pixel' => $samplesPerPixel,
            'bits' => $normalizeRange !== null ? 32 : 16, // 32-bit float for normalized, 16-bit int otherwise
            'length' => count($peaks) / ($channels * 2), // min/max pairs per channel
            'data' => $peaks,
        ];
    }

    /**
     * Get media information using FFprobe
     */
    protected function getMediaInfo(string $ffprobePath, string $filePath): array
    {
        $command = sprintf(
            '%s -v quiet -print_format json -show_streams -select_streams a:0 %s',
            $ffprobePath,
            escapeshellarg($filePath)
        );

        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(config('fluent-ffmpeg.timeout', 3600));
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ExecutionException(
                    "FFprobe command failed: {$process->getErrorOutput()}",
                    $process->getExitCode()
                );
            }

            $output = $process->getOutput();
            $data = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ExecutionException('Failed to parse FFprobe output as JSON');
            }

            if (empty($data['streams'][0])) {
                throw new ExecutionException('No audio stream found in file');
            }

            $audioStream = $data['streams'][0];

            return [
                'sample_rate' => (int) $audioStream['sample_rate'],
                'channels' => (int) $audioStream['channels'],
            ];
        } catch (ProcessFailedException $e) {
            throw new ExecutionException(
                "FFprobe process failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Stream PCM data from FFmpeg and calculate peaks incrementally
     */
    protected function streamAndCalculatePeaks(
        string $ffmpegPath,
        string $filePath,
        int $sampleRate,
        int $channels,
        int $samplesPerPixel,
        ?array $normalizeRange
    ): array {
        // Decode to 16-bit signed integer PCM (s16le)
        $command = sprintf(
            '%s -i %s -f s16le -acodec pcm_s16le -ar %d -ac %d -',
            $ffmpegPath,
            escapeshellarg($filePath),
            $sampleRate,
            $channels
        );

        $peaks = [];
        $buffer = '';
        $sampleSize = 2; // 16-bit = 2 bytes
        $bytesPerFrame = $sampleSize * $channels;
        $bytesPerChunk = $samplesPerPixel * $bytesPerFrame;

        // Current chunk stats
        $currentSample = 0;
        $channelMinMax = array_fill(0, $channels, ['min' => PHP_INT_MAX, 'max' => PHP_INT_MIN]);

        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(config('fluent-ffmpeg.timeout', 3600));

            // Stream output callback
            $process->run(function ($type, $data) use (&$buffer, &$peaks, &$currentSample, &$channelMinMax, $channels, $sampleSize, $bytesPerFrame, $samplesPerPixel, $normalizeRange) {
                if ($type === Process::OUT) {
                    // Append new data to buffer
                    $buffer .= $data;

                    // Process complete frames from buffer
                    while (strlen($buffer) >= $bytesPerFrame) {
                        // Read one frame (all channels for one sample)
                        for ($channel = 0; $channel < $channels; $channel++) {
                            $byteOffset = $channel * $sampleSize;
                            $bytes = substr($buffer, $byteOffset, $sampleSize);
                            $value = unpack('s', $bytes)[1]; // 's' = signed short (16-bit)

                            $channelMinMax[$channel]['min'] = min($channelMinMax[$channel]['min'], $value);
                            $channelMinMax[$channel]['max'] = max($channelMinMax[$channel]['max'], $value);
                        }

                        // Remove processed frame from buffer
                        $buffer = substr($buffer, $bytesPerFrame);
                        $currentSample++;

                        // If we've processed enough samples for one pixel, output peaks
                        if ($currentSample >= $samplesPerPixel) {
                            for ($channel = 0; $channel < $channels; $channel++) {
                                $min = $channelMinMax[$channel]['min'];
                                $max = $channelMinMax[$channel]['max'];

                                // Handle case where chunk had no data
                                if ($min === PHP_INT_MAX) {
                                    $min = 0;
                                }
                                if ($max === PHP_INT_MIN) {
                                    $max = 0;
                                }

                                if ($normalizeRange !== null) {
                                    [$targetMin, $targetMax] = $normalizeRange;

                                    // Normalize from -32768..32767 to target range
                                    $minNorm = $this->normalizeValue($min, -32768, 32767, $targetMin, $targetMax);
                                    $maxNorm = $this->normalizeValue($max, -32768, 32767, $targetMin, $targetMax);

                                    $peaks[] = round($minNorm, 6);
                                    $peaks[] = round($maxNorm, 6);
                                } else {
                                    // Keep as signed 16-bit integers
                                    $peaks[] = $min;
                                    $peaks[] = $max;
                                }
                            }

                            // Reset for next chunk
                            $currentSample = 0;
                            $channelMinMax = array_fill(0, $channels, ['min' => PHP_INT_MAX, 'max' => PHP_INT_MIN]);
                        }
                    }
                }
            });

            if (! $process->isSuccessful()) {
                throw new ExecutionException(
                    "FFmpeg decode command failed: {$process->getErrorOutput()}",
                    $process->getExitCode()
                );
            }

            // Process any remaining samples in buffer
            if ($currentSample > 0) {
                for ($channel = 0; $channel < $channels; $channel++) {
                    $min = $channelMinMax[$channel]['min'];
                    $max = $channelMinMax[$channel]['max'];

                    if ($min === PHP_INT_MAX) {
                        $min = 0;
                    }
                    if ($max === PHP_INT_MIN) {
                        $max = 0;
                    }

                    if ($normalizeRange !== null) {
                        [$targetMin, $targetMax] = $normalizeRange;

                        $minNorm = $this->normalizeValue($min, -32768, 32767, $targetMin, $targetMax);
                        $maxNorm = $this->normalizeValue($max, -32768, 32767, $targetMin, $targetMax);

                        $peaks[] = round($minNorm, 6);
                        $peaks[] = round($maxNorm, 6);
                    } else {
                        $peaks[] = $min;
                        $peaks[] = $max;
                    }
                }
            }

            return $peaks;
        } catch (ProcessFailedException $e) {
            throw new ExecutionException(
                "FFmpeg decode process failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Normalize a value from one range to another
     */
    protected function normalizeValue(int $value, int $fromMin, int $fromMax, float $toMin, float $toMax): float
    {
        // Linear interpolation: map value from [fromMin, fromMax] to [toMin, toMax]
        return $toMin + (($value - $fromMin) / ($fromMax - $fromMin)) * ($toMax - $toMin);
    }
}
