<?php

namespace Ritechoice23\FluentFFmpeg\Actions;

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

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

        // Add output options
        foreach ($builder->getOutputOptions() as $key => $value) {
            $parts[] = $this->formatOption($key, $value);
        }

        // Add output path
        if ($outputPath = $builder->getOutputPath()) {
            // If saving to disk, use temp path first
            if ($builder->getOutputDisk()) {
                $tempPath = sys_get_temp_dir().'/'.uniqid('ffmpeg_').'_'.basename($outputPath);
                $parts[] = '-y'; // Overwrite without asking
                $parts[] = escapeshellarg($tempPath);
            } else {
                $parts[] = '-y'; // Overwrite without asking
                $parts[] = escapeshellarg($outputPath);
            }
        }

        return implode(' ', $parts);
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
