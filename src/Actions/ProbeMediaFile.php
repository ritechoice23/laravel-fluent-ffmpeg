<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Ritechoice23\FluentFFmpeg\Exceptions\ExecutionException;
use Ritechoice23\FluentFFmpeg\MediaInfo\MediaInfo;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ProbeMediaFile
{
    /**
     * Execute FFprobe to get media information
     */
    public function execute(string $filePath): MediaInfo
    {
        $ffprobePath = config('fluent-ffmpeg.ffprobe_path', 'ffprobe');

        $command = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s',
            $ffprobePath,
            escapeshellarg($filePath)
        );

        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(30);
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

            return new MediaInfo($data);
        } catch (ProcessFailedException $e) {
            throw new ExecutionException(
                "FFprobe process failed: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }
}
