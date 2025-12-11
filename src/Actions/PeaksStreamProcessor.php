<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

class PeaksStreamProcessor
{
    protected array $peaksData = [];

    protected string $pcmBuffer = '';

    protected int $currentSample = 0;

    protected array $channelMinMax = [];

    protected int $samplesPerPixel;

    protected ?array $normalizeRange;

    protected int $channels = 2;

    protected int $sampleRate = 44100;

    public function __construct(array $config)
    {
        $this->samplesPerPixel = $config['samples_per_pixel'] ?? 512;
        $this->normalizeRange = $config['normalize_range'] ?? null;
    }

    /**
     * Set audio stream info (called once media info is known)
     */
    public function setAudioInfo(int $channels, int $sampleRate): void
    {
        $this->channels = $channels;
        $this->sampleRate = $sampleRate;
        $this->channelMinMax = array_fill(0, $channels, ['min' => PHP_INT_MAX, 'max' => PHP_INT_MIN]);
    }

    /**
     * Process a chunk of PCM data
     */
    public function processPcmChunk(string $data): void
    {
        $this->pcmBuffer .= $data;

        $sampleSize = 2; // 16-bit
        $bytesPerFrame = $sampleSize * $this->channels;

        // Process complete frames
        while (strlen($this->pcmBuffer) >= $bytesPerFrame) {
            // Read one frame (all channels for one sample)
            for ($channel = 0; $channel < $this->channels; $channel++) {
                $byteOffset = $channel * $sampleSize;
                $bytes = substr($this->pcmBuffer, $byteOffset, $sampleSize);

                if (strlen($bytes) < $sampleSize) {
                    break 2; // Not enough data, wait for more
                }

                $value = unpack('s', $bytes)[1]; // 's' = signed short (16-bit)

                $this->channelMinMax[$channel]['min'] = min($this->channelMinMax[$channel]['min'], $value);
                $this->channelMinMax[$channel]['max'] = max($this->channelMinMax[$channel]['max'], $value);
            }

            $this->pcmBuffer = substr($this->pcmBuffer, $bytesPerFrame);
            $this->currentSample++;

            // Output peaks when we have enough samples
            if ($this->currentSample >= $this->samplesPerPixel) {
                $this->outputPeaks();
                $this->currentSample = 0;
                $this->channelMinMax = array_fill(0, $this->channels, ['min' => PHP_INT_MAX, 'max' => PHP_INT_MIN]);
            }
        }
    }

    /**
     * Output peaks for current chunk
     */
    protected function outputPeaks(): void
    {
        for ($channel = 0; $channel < $this->channels; $channel++) {
            $min = $this->channelMinMax[$channel]['min'];
            $max = $this->channelMinMax[$channel]['max'];

            if ($min === PHP_INT_MAX) {
                $min = 0;
            }
            if ($max === PHP_INT_MIN) {
                $max = 0;
            }

            if ($this->normalizeRange !== null) {
                [$targetMin, $targetMax] = $this->normalizeRange;
                $minNorm = $this->normalizeValue($min, -32768, 32767, $targetMin, $targetMax);
                $maxNorm = $this->normalizeValue($max, -32768, 32767, $targetMin, $targetMax);
                $this->peaksData[] = round($minNorm, 6);
                $this->peaksData[] = round($maxNorm, 6);
            } else {
                $this->peaksData[] = $min;
                $this->peaksData[] = $max;
            }
        }
    }

    /**
     * Finalize peaks (process remaining samples) and return result
     */
    public function finalize(): array
    {
        // Process any remaining samples
        if ($this->currentSample > 0) {
            $this->outputPeaks();
        }

        return [
            'version' => 2,
            'channels' => $this->channels,
            'sample_rate' => $this->sampleRate,
            'samples_per_pixel' => $this->samplesPerPixel,
            'bits' => $this->normalizeRange !== null ? 32 : 16,
            'length' => count($this->peaksData) / ($this->channels * 2),
            'data' => $this->peaksData,
        ];
    }

    /**
     * Normalize a value from one range to another
     */
    protected function normalizeValue(int $value, int $fromMin, int $fromMax, float $toMin, float $toMax): float
    {
        return $toMin + (($value - $fromMin) / ($fromMax - $fromMin)) * ($toMax - $toMin);
    }
}
