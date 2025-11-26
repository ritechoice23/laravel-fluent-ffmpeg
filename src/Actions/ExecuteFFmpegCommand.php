<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        array $inputs = []
    ): bool {
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

                if ($errorCallback) {
                    call_user_func($errorCallback, $error);
                }

                throw new ExecutionException(
                    "FFmpeg command failed: {$error}",
                    $process->getExitCode()
                );
            }

            // If output disk is specified, move file to disk
            if ($outputDisk && $outputPath) {
                $tempPath = $this->extractTempPathFromCommand($command);
                if ($tempPath && file_exists($tempPath)) {
                    Storage::disk($outputDisk)->put(
                        $outputPath,
                        file_get_contents($tempPath)
                    );
                    @unlink($tempPath); // Clean up temp file
                }
            }

            // Log success
            if ($logChannel = config('fluent-ffmpeg.log_channel')) {
                Log::channel($logChannel)->info('FFmpeg command completed successfully');
            }

            // Dispatch completed event
            $duration = microtime(true) - $startTime;
            event(new FFmpegProcessCompleted($command, $outputPath ?? 'stream', $duration));

            return true;
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
     * Extract temp path from command
     */
    protected function extractTempPathFromCommand(string $command): ?string
    {
        // Extract the last argument which should be the output path
        if (preg_match("/['\"]([^'\"]*ffmpeg_[^'\"]*)['\"]$/", $command, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
