<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasHelperMethods
{
    /**
     * Extract audio from video
     */
    public function extractAudio(): self
    {
        $this->removeVideo();

        return $this;
    }

    /**
     * Remove video stream (audio only)
     */
    protected function removeVideo(): self
    {
        return $this->addOutputOption('vn', true);
    }

    /**
     * Replace audio track
     */
    public function replaceAudio(): self
    {
        // Map video from first input (0:v) and audio from second input (1:a)
        $this->addOutputOption('map', ['0:v', '1:a']);

        // Ensure we copy the video stream (fastest) unless filters are applied
        if (empty($this->filters)) {
            $this->videoCodec('copy');
        }

        return $this;
    }

    /**
     * Create video from image sequence
     */
    public function fromImages(string $pattern, array $options = []): self
    {
        $framerate = $options['framerate'] ?? 24;

        $this->addInputOption('framerate', $framerate);
        $this->addInputOption('pattern_type', 'sequence');
        $this->inputs[] = $pattern;

        return $this;
    }

    /**
     * Generate waveform visualization
     */
    public function waveform(array $options = []): self
    {
        $width = $options['width'] ?? 1920;
        $height = $options['height'] ?? 1080;
        $color = $options['color'] ?? 'white';

        $this->addFilter("showwavespic=s={$width}x{$height}:colors={$color}");
        $this->addOutputOption('frames:v', 1);

        return $this;
    }

    /**
     * Apply a preset configuration
     */
    public function preset(string|array $preset): self
    {
        if (is_string($preset)) {
            $presetConfig = config("fluent-ffmpeg.presets.{$preset}");

            if (!$presetConfig) {
                throw new \InvalidArgumentException("Preset '{$preset}' not found in configuration");
            }
        } else {
            $presetConfig = $preset;
        }

        // Apply preset options
        if (isset($presetConfig['resolution'])) {
            $this->resolution(...$presetConfig['resolution']);
        }

        if (isset($presetConfig['video_codec'])) {
            $this->videoCodec($presetConfig['video_codec']);
        }

        if (isset($presetConfig['audio_codec'])) {
            $this->audioCodec($presetConfig['audio_codec']);
        }

        if (isset($presetConfig['video_bitrate'])) {
            $this->videoBitrate($presetConfig['video_bitrate']);
        }

        if (isset($presetConfig['audio_bitrate'])) {
            $this->audioBitrate($presetConfig['audio_bitrate']);
        }

        return $this;
    }
}
