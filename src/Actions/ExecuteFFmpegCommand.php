<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Ritechoice23\FluentFFmpeg\Enums\PeaksFormat;
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessCompleted;
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessFailed;
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessStarted;
use Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ExecuteFFmpegCommand
{
    /**
     * Execute the FFmpeg command
     */
    public function execute(
        string $command,
        ?callable $progressCallback = null,
        ?callable $errorCallback = null,
        ?string $outputDisk = null,
        ?string $outputPath = null,
        array $inputs = [],
        ?array $peaksConfig = null
    ): array {
        // Determine if we need streaming execution (only for S3/disk output that requires streaming)
        $needsStreaming = $outputDisk !== null;

        if ($needsStreaming) {
            return $this->executeWithStreaming(
                $command,
                $progressCallback,
                $errorCallback,
                $outputDisk,
                $outputPath,
                $inputs,
                $peaksConfig
            );
        }

        // Use simple execution - convert normally, then generate peaks separately if needed
        $result = $this->executeStandard(
            $command,
            $progressCallback,
            $errorCallback,
            $outputDisk,
            $outputPath,
            $inputs
        );

        // Generate peaks after conversion if requested
        if ($peaksConfig && $outputPath) {
            $peaksData = $this->generatePeaksAfterConversion($outputPath, $inputs, $peaksConfig);
            $result['peaks'] = $peaksData;
        }

        return $result;
    }

    /**
     * Execute with streaming (for S3 output and/or peaks generation)
     */
    protected function executeWithStreaming(
        string $command,
        ?callable $progressCallback,
        ?callable $errorCallback,
        ?string $outputDisk,
        ?string $outputPath,
        array $inputs,
        ?array $peaksConfig
    ): array {
        $startTime = microtime(true);

        // Dispatch started event
        event(new FFmpegProcessStarted($command, $inputs, $outputPath));

        // Log the command
        if ($logChannel = config('fluent-ffmpeg.log_channel')) {
            Log::channel($logChannel)->info('Executing FFmpeg command with streaming', [
                'command' => $command,
            ]);
        }

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout (progress)
            2 => ['pipe', 'w'],  // stderr
        ];

        if ($peaksConfig) {
            $descriptors[3] = ['pipe', 'w'];  // PCM output for peaks
        }

        if ($outputDisk) {
            $descriptors[4] = ['pipe', 'w'];  // Transcoded output
        }

        $process = proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new ExecutionException('Failed to start FFmpeg process');
        }

        fclose($pipes[0]); // Close stdin

        // Set non-blocking mode
        foreach ($pipes as $i => $pipe) {
            if ($i > 0 && is_resource($pipe)) {
                stream_set_blocking($pipe, false);
            }
        }

        // Prepare output stream for disk
        $outputStream = null;
        if ($outputDisk && $outputPath) {
            $outputStream = fopen('php://temp', 'w+b');
        }

        // Initialize peaks processing
        $peaksData = null;
        $peaksProcessor = null;
        if ($peaksConfig) {
            $peaksProcessor = new PeaksStreamProcessor($peaksConfig);
        }

        $progressBuffer = '';
        $errorBuffer = '';

        // Process streams
        while (true) {
            // Check if process is still running first
            $status = proc_get_status($process);
            if (! $status['running']) {
                // Read any remaining data from pipes that are still open
                foreach ($pipes as $i => $pipe) {
                    if (! is_resource($pipe)) {
                        continue;
                    }

                    // Set non-blocking mode to prevent hanging
                    stream_set_blocking($pipe, false);

                    // Read all remaining data without using feof which can block
                    $data = stream_get_contents($pipe);
                    if ($data !== false && $data !== '') {
                        if ($i === 1) {
                            $progressBuffer .= $data;
                            $this->handleProgressBuffer($progressBuffer, $progressCallback);
                        } elseif ($i === 2) {
                            $errorBuffer .= $data;
                        } elseif ($i === 3 && $peaksProcessor) {
                            $peaksProcessor->processPcmChunk($data);
                        } elseif ($i === 4 && $outputStream) {
                            fwrite($outputStream, $data);
                        }
                    }
                }
                break;
            }

            // Read from available pipes
            $read = array_filter($pipes, fn ($p) => is_resource($p));
            if (empty($read)) {
                // No pipes to read from, but process is still running - wait a bit
                usleep(10000); // 10ms

                continue;
            }

            $write = null;
            $except = null;

            // Wait for data with short timeout
            $ready = stream_select($read, $write, $except, 0, 100000); // 100ms timeout
            if ($ready === false) {
                // stream_select failed
                break;
            }

            if ($ready === 0) {
                // Timeout - no data available, continue to next iteration
                continue;
            }

            // Process available data
            foreach ($read as $i => $stream) {
                $data = fread($stream, 8192);

                if ($data === false || $data === '') {
                    continue;
                }

                // Progress from pipe 1
                if ($i === 1) {
                    $progressBuffer .= $data;
                    $this->handleProgressBuffer($progressBuffer, $progressCallback);
                }

                // Errors from pipe 2
                if ($i === 2) {
                    $errorBuffer .= $data;
                }

                // PCM data from pipe 3
                if ($i === 3 && $peaksProcessor) {
                    $peaksProcessor->processPcmChunk($data);
                }

                // Transcoded output from pipe 4
                if ($i === 4 && $outputStream) {
                    fwrite($outputStream, $data);
                }
            }
        }

        // Close all pipes
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            if ($outputStream) {
                fclose($outputStream);
            }

            event(new FFmpegProcessFailed($command, $errorBuffer, $exitCode));

            if ($errorCallback) {
                call_user_func($errorCallback, $errorBuffer);
            }

            throw new ExecutionException(
                "FFmpeg process failed: {$errorBuffer}\nCommand: {$command}",
                $exitCode
            );
        }

        // Finalize peaks
        if ($peaksProcessor) {
            $peaksData = $peaksProcessor->finalize();
        }

        // Upload to disk if needed
        if ($outputDisk && $outputPath && $outputStream) {
            rewind($outputStream);
            Storage::disk($outputDisk)->writeStream($outputPath, $outputStream);
            fclose($outputStream);
        }

        // Save peaks to JSON file if generated
        if ($peaksData && $outputPath) {
            $this->savePeaksFile($peaksData, $outputPath, $outputDisk, $peaksConfig);
        }

        // Log success
        if ($logChannel = config('fluent-ffmpeg.log_channel')) {
            Log::channel($logChannel)->info('FFmpeg command completed successfully');
        }

        // Dispatch completed event
        $duration = microtime(true) - $startTime;
        event(new FFmpegProcessCompleted($command, $outputPath ?? 'stream', $duration));

        return [
            'success' => true,
            'peaks' => $peaksData,
        ];
    }

    /**
     * Save peaks data to JSON file
     *
     * @return string The path to the saved peaks file
     */
    protected function savePeaksFile(array $peaksData, string $outputPath, ?string $outputDisk, array $peaksConfig): string
    {
        // Generate peaks filename
        $peaksFilename = $peaksConfig['peaks_filename'] ?? null;

        if ($peaksFilename !== null) {
            // Custom filename provided
            if (is_callable($peaksFilename)) {
                $peaksPath = call_user_func($peaksFilename, $outputPath);
            } else {
                $peaksPath = $peaksFilename;
            }

            // Validate and sanitize the filename for security
            $peaksPath = $this->validatePeaksFilename($peaksPath);
        } else {
            // Default: output.m4a -> output-peaks.json
            $pathInfo = pathinfo($outputPath);
            $peaksPath = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'].'/' : '')
                .$pathInfo['filename'].'-peaks.json';
        }

        // Determine format from config parameter (not global config)
        $peaksFormat = $peaksConfig['format'] ?? PeaksFormat::SIMPLE->value;
        $peaksContent = $peaksFormat === PeaksFormat::FULL->value
            ? $peaksData
            : $peaksData['data'];

        $json = json_encode($peaksContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($outputDisk) {
            // Save to disk
            Storage::disk($outputDisk)->put($peaksPath, $json);
        } else {
            // Save to local file
            $directory = dirname($peaksPath);
            if ($directory !== '.' && ! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            file_put_contents($peaksPath, $json);
        }

        return $peaksPath;
    }

    /**
     * Validate and sanitize peaks filename to prevent directory traversal
     *
     * @throws \InvalidArgumentException
     */
    protected function validatePeaksFilename(string $filename): string
    {
        // Normalize path separators
        $filename = str_replace('\\', '/', $filename);

        // Remove any directory traversal attempts
        $filename = str_replace('..', '', $filename);

        // Remove leading slashes for security
        $filename = ltrim($filename, '/');

        // Validate against strict pattern: alphanumeric, underscore, dash, dot, forward slash
        if (! preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $filename)) {
            throw new \InvalidArgumentException(
                'Invalid peaks filename. Only alphanumeric characters, underscores, dashes, dots, and forward slashes are allowed.'
            );
        }

        return $filename;
    }

    /**
     * Execute standard (non-streaming)
     */
    protected function executeStandard(
        string $command,
        ?callable $progressCallback,
        ?callable $errorCallback,
        ?string $outputDisk,
        ?string $outputPath,
        array $inputs
    ): array {
        // Ensure output directory exists before running FFmpeg
        if ($outputPath && ! $outputDisk) {
            $outputDir = dirname($outputPath);
            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
        }

        $startTime = microtime(true);

        // Dispatch started event
        event(new FFmpegProcessStarted($command, $inputs, $outputPath));

        try {
            // Create process
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(config('fluent-ffmpeg.timeout', 3600));

            // Log the command if logging is enabled
            if ($logChannel = config('fluent-ffmpeg.log_channel')) {
                Log::channel($logChannel)->info('Executing FFmpeg command', [
                    'command' => $command,
                ]);
            }

            // Execute the process
            $process->run(function ($type, $buffer) use ($progressCallback) {
                if ($progressCallback && $type === Process::OUT) {
                    // Parse FFmpeg progress output
                    $progress = $this->parseProgress($buffer);
                    if ($progress) {
                        call_user_func($progressCallback, $progress);
                    }
                }
            });

            // Check if process was successful
            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput();

                // Include stdout as well for better debugging
                $output = $process->getOutput();

                if ($errorCallback) {
                    call_user_func($errorCallback, $error);
                }

                throw new ExecutionException(
                    "FFmpeg command failed: {$error}\nOutput: {$output}\nCommand: {$command}",
                    $process->getExitCode()
                );
            }

            // Log success
            if ($logChannel = config('fluent-ffmpeg.log_channel')) {
                Log::channel($logChannel)->info('FFmpeg command completed successfully');
            }

            // Dispatch completed event
            $duration = microtime(true) - $startTime;
            event(new FFmpegProcessCompleted($command, $outputPath ?? 'stream', $duration));

            return ['success' => true, 'peaks' => null];
        } catch (ProcessFailedException $e) {
            // Dispatch failed event
            event(new FFmpegProcessFailed($command, $e->getMessage(), $e->getCode()));

            if ($errorCallback) {
                call_user_func($errorCallback, $e->getMessage());
            }

            throw new ExecutionException(
                "FFmpeg process failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        } catch (ExecutionException $e) {
            // Dispatch failed event for execution exceptions
            event(new FFmpegProcessFailed($command, $e->getMessage(), $e->getCode()));

            throw $e;
        }
    }

    /**
     * Handle progress buffer
     */
    protected function handleProgressBuffer(string &$buffer, ?callable $callback): void
    {
        if (! $callback) {
            return;
        }

        $progress = $this->parseProgress($buffer);
        if ($progress) {
            call_user_func($callback, $progress);
        }
    }

    /**
     * Parse FFmpeg progress output
     */
    protected function parseProgress(string $buffer): ?array
    {
        // FFmpeg outputs progress in format: frame=  123 fps= 45 q=28.0 size=    1024kB time=00:00:05.00 bitrate=1677.7kbits/s speed=1.5x
        if (preg_match('/time=(\d+):(\d+):(\d+\.\d+)/', $buffer, $timeMatches)) {
            $hours = (int) $timeMatches[1];
            $minutes = (int) $timeMatches[2];
            $seconds = (float) $timeMatches[3];
            $timeProcessed = ($hours * 3600) + ($minutes * 60) + $seconds;

            $progress = [
                'time_processed' => $timeProcessed,
            ];

            // Extract FPS
            if (preg_match('/fps=\s*(\d+\.?\d*)/', $buffer, $fpsMatches)) {
                $progress['fps'] = (float) $fpsMatches[1];
            }

            // Extract speed
            if (preg_match('/speed=\s*(\d+\.?\d*)x/', $buffer, $speedMatches)) {
                $progress['speed'] = (float) $speedMatches[1];
            }

            return $progress;
        }

        return null;
    }

    /**
     * Generate peaks after conversion completes
     * Uses either the original input file or processed output based on config
     */
    protected function generatePeaksAfterConversion(string $outputPath, array $inputs, array $peaksConfig): ?array
    {
        // Choose source file based on config
        $useProcessedFile = $peaksConfig['use_processed_file'] ?? false;
        $sourceFile = $useProcessedFile ? $outputPath : ($inputs[0] ?? null);

        if (! $sourceFile || ! file_exists($sourceFile)) {
            return null;
        }

        try {
            $generator = new GenerateAudioPeaks;
            $peaksData = $generator->execute(
                $sourceFile,
                $peaksConfig['samples_per_pixel'] ?? 512,
                $peaksConfig['normalize_range'] ?? null
            );

            // Save peaks to JSON file
            if ($peaksData) {
                $this->savePeaksFile($peaksData, $outputPath, null, $peaksConfig);
            }

            return $peaksData;
        } catch (\Exception $e) {
            // Log error but don't fail the conversion
            if ($logChannel = config('fluent-ffmpeg.log_channel')) {
                Log::channel($logChannel)->warning('Failed to generate peaks', [
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        }
    }

    /**
     * Execute with peaks processing only (for legacy streaming mode)
     * This is now only used when outputDisk is set
     */
    protected function executeWithPeaksOnly_DEPRECATED(
        string $command,
        ?callable $progressCallback,
        ?callable $errorCallback,
        ?string $outputPath,
        array $inputs,
        array $peaksConfig,
        float $startTime
    ): array {
        // Set up pipes: stdin, stdout (progress), stderr, PCM (pipe:3)
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
            3 => ['pipe', 'w'],  // PCM for peaks
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new ExecutionException('Failed to start FFmpeg process');
        }

        fclose($pipes[0]); // Close stdin

        // Set non-blocking mode on all pipes
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        stream_set_blocking($pipes[3], false);

        // Initialize peaks processor
        $peaksProcessor = new PeaksStreamProcessor($peaksConfig);

        // Get media info for peaks
        $mediaInfo = $this->getMediaInfoForPeaks(config('fluent-ffmpeg.ffprobe_path', 'ffprobe'), $inputs[0] ?? '');
        $peaksProcessor->setAudioInfo($mediaInfo['channels'], $mediaInfo['sample_rate']);

        $errorBuffer = '';

        // Read from pipes until process completes
        while (true) {
            $status = proc_get_status($process);

            // Read available data from all pipes
            $progressData = fread($pipes[1], 8192);
            if ($progressData && $progressCallback) {
                $progress = $this->parseProgress($progressData);
                if ($progress) {
                    call_user_func($progressCallback, $progress);
                }
            }

            $errorData = fread($pipes[2], 8192);
            if ($errorData) {
                $errorBuffer .= $errorData;
            }

            $pcmData = fread($pipes[3], 8192);
            if ($pcmData) {
                $peaksProcessor->processPcmChunk($pcmData);
            }

            if (! $status['running']) {
                // Process has finished, read any remaining data
                while ($data = fread($pipes[1], 8192)) {
                    if ($progressCallback) {
                        $progress = $this->parseProgress($data);
                        if ($progress) {
                            call_user_func($progressCallback, $progress);
                        }
                    }
                }
                while ($data = fread($pipes[2], 8192)) {
                    $errorBuffer .= $data;
                }
                while ($data = fread($pipes[3], 8192)) {
                    $peaksProcessor->processPcmChunk($data);
                }
                break;
            }

            usleep(10000); // 10ms sleep to prevent busy-waiting
        }

        // Close pipes
        fclose($pipes[1]);
        fclose($pipes[2]);
        fclose($pipes[3]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            event(new FFmpegProcessFailed($command, $errorBuffer, $exitCode));

            if ($errorCallback) {
                call_user_func($errorCallback, $errorBuffer);
            }

            throw new ExecutionException(
                "FFmpeg process failed: {$errorBuffer}\nCommand: {$command}",
                $exitCode
            );
        }

        // Finalize peaks
        $peaksData = $peaksProcessor->finalize();

        // Save peaks to JSON file
        if ($peaksData && $outputPath) {
            $this->savePeaksFile($peaksData, $outputPath, null, $peaksConfig);
        }

        // Log success
        if ($logChannel = config('fluent-ffmpeg.log_channel')) {
            Log::channel($logChannel)->info('FFmpeg command with peaks completed successfully');
        }

        $duration = microtime(true) - $startTime;
        event(new FFmpegProcessCompleted($command, $outputPath ?? 'stream', $duration));

        return [
            'success' => true,
            'peaks' => $peaksData,
        ];
    }

    /**
     * Get media info for peaks processing
     */
    protected function getMediaInfoForPeaks(string $ffprobePath, string $input): array
    {
        $command = sprintf(
            '%s -v quiet -print_format json -show_streams -select_streams a:0 %s',
            $ffprobePath,
            escapeshellarg($input)
        );

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            // Return defaults if probe fails
            return ['channels' => 2, 'sample_rate' => 44100];
        }

        $data = json_decode($process->getOutput(), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['streams'][0])) {
            return ['channels' => 2, 'sample_rate' => 44100];
        }

        $audioStream = $data['streams'][0];

        return [
            'channels' => (int) ($audioStream['channels'] ?? 2),
            'sample_rate' => (int) ($audioStream['sample_rate'] ?? 44100),
        ];
    }
}
