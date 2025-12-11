<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;
use Ritechoice23\FluentFFmpeg\Enums\ContainerFormat;
use Ritechoice23\FluentFFmpeg\Enums\FFmpegPipe;
use Ritechoice23\FluentFFmpeg\Enums\HttpMethod;

class BuildFFmpegCommand
{
    /**
     * Execute the action to build FFmpeg command
     */
    public function execute(FFmpegBuilder $builder): string
    {
        $ffmpegPath = config('fluent-ffmpeg.ffmpeg_path', 'ffmpeg');
        $parts = [$ffmpegPath];

        // Add input options
        foreach ($builder->getInputOptions() as $key => $value) {
            $parts[] = $this->formatOption($key, $value);
        }

        // Add inputs
        foreach ($builder->getInputs() as $input) {
            $parts[] = '-i';
            $parts[] = escapeshellarg($input);
        }

        // Add progress output
        $parts[] = '-progress';
        $parts[] = FFmpegPipe::PROGRESS->value;

        // Add filters
        if (count($builder->getFilters()) > 0) {
            $filterString = implode(',', $builder->getFilters());

            // Use -filter_complex for multiple inputs, -vf for single input
            if (count($builder->getInputs()) > 1) {
                $parts[] = '-filter_complex';
            } else {
                $parts[] = '-vf';
            }
            $parts[] = escapeshellarg($filterString);
        }

        // Add metadata
        foreach ($builder->getMetadata() as $key => $value) {
            $parts[] = '-metadata';
            $parts[] = escapeshellarg("{$key}={$value}");
        }

        // Add output options for main output
        foreach ($builder->getOutputOptions() as $key => $value) {
            $parts[] = $this->formatOption($key, $value);
        }

        // Add output path
        if ($outputPath = $builder->getOutputPath()) {
            // If saving to disk, check for direct upload support
            if ($outputDisk = $builder->getOutputDisk()) {
                $disk = \Illuminate\Support\Facades\Storage::disk($outputDisk);

                // Check if disk supports temporaryUploadUrl (Laravel 9+)
                if (method_exists($disk, 'temporaryUploadUrl')) {
                    try {
                        // Try to use temporaryUploadUrl for direct FFmpeg->S3 upload
                        $uploadUrl = $disk->temporaryUploadUrl($outputPath, now()->addHour());

                        // Use HTTP PUT method for direct upload
                        $parts[] = '-method';
                        $parts[] = HttpMethod::PUT->value;
                        $parts[] = escapeshellarg($uploadUrl);
                    } catch (\Exception $e) {
                        // Failed to generate upload URL - throw descriptive error
                        throw new \RuntimeException(
                            "Failed to generate temporary upload URL for disk '{$outputDisk}': ".$e->getMessage(),
                            0,
                            $e
                        );
                    }
                } else {
                    // Disk doesn't support temporaryUploadUrl - throw descriptive error
                    throw new \RuntimeException(
                        "Storage disk '{$outputDisk}' does not support temporaryUploadUrl(). ".
                        'Direct upload requires S3-compatible storage (s3, minio, digitalocean) or filesystems with signed upload support. '.
                        'Current disk driver: '.config("filesystems.disks.{$outputDisk}.driver")
                    );
                }
            } else {
                $parts[] = '-y'; // Overwrite without asking
                $parts[] = escapeshellarg($outputPath);
            }
        }

        // Add PCM output for peaks if requested
        if ($peaksConfig = $builder->getPeaksConfig()) {
            $parts[] = '-map';
            $parts[] = '0:a';
            $parts[] = '-f';
            $parts[] = 's16le';
            $parts[] = '-acodec';
            $parts[] = 'pcm_s16le';
            $parts[] = FFmpegPipe::PEAKS_PCM->value;
        }

        return implode(' ', $parts);
    }

    /**
     * Get output format for streaming
     */
    protected function getOutputFormat(string $outputPath, FFmpegBuilder $builder): string
    {
        $extension = pathinfo($outputPath, PATHINFO_EXTENSION);
        $format = ContainerFormat::fromExtension($extension);

        return $format?->getFFmpegFormat() ?? ContainerFormat::MP4->getFFmpegFormat();
    }

    /**
     * Format an option for FFmpeg command
     */
    protected function formatOption(string $key, mixed $value): string
    {
        // Handle boolean flags
        if ($value === true) {
            return "-{$key}";
        }

        // Handle options with values
        if ($value === null || $value === false) {
            return '';
        }

        // Handle array values (multiple flags)
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $parts[] = "-{$key} ".escapeshellarg($item);
            }

            return implode(' ', $parts);
        }

        // Special handling for options that contain FFmpeg patterns (%, strftime, etc.)
        // These should not be escaped as they contain special formatting characters
        $noEscapeOptions = [
            'hls_segment_filename',
            'segment_filename',
            'strftime_mkdir',
        ];

        if (in_array($key, $noEscapeOptions) || $this->containsFFmpegPattern($value)) {
            return "-{$key} \"{$value}\"";
        }

        return "-{$key} ".escapeshellarg($value);
    }

    /**
     * Check if value contains FFmpeg formatting patterns
     */
    protected function containsFFmpegPattern(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        // Check for common FFmpeg patterns: %d, %03d, %Y, etc.
        return (bool) preg_match('/%\d*[a-zA-Z]/', $value);
    }
}
