<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasFilters
{
    /**
     * Add custom filter
     */
    public function addFilter(string $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * Crop video
     */
    public function crop(int $width, int $height, int $x = 0, int $y = 0): self
    {
        return $this->addFilter("crop={$width}:{$height}:{$x}:{$y}");
    }

    /**
     * Scale video
     */
    public function scale(int $width, int $height, bool $maintainAspectRatio = true): self
    {
        if ($maintainAspectRatio) {
            return $this->addFilter("scale={$width}:{$height}:force_original_aspect_ratio=decrease");
        }

        return $this->addFilter("scale={$width}:{$height}");
    }

    /**
     * Resize (alias for scale)
     */
    public function resize(int $width, int $height): self
    {
        return $this->scale($width, $height);
    }

    /**
     * Rotate video
     */
    public function rotate(int $degrees): self
    {
        $rotations = [
            90 => 'transpose=1',
            180 => 'transpose=1,transpose=1',
            270 => 'transpose=2',
        ];

        if (isset($rotations[$degrees])) {
            return $this->addFilter($rotations[$degrees]);
        }

        return $this;
    }

    /**
     * Flip video
     */
    public function flip(string $direction = 'horizontal'): self
    {
        $filter = $direction === 'vertical' ? 'vflip' : 'hflip';

        return $this->addFilter($filter);
    }

    /**
     * Add fade effect
     */
    public function fade(string $type, int $duration): self
    {
        return $this->addFilter("fade={$type}:d={$duration}");
    }

    /**
     * Fade in
     */
    public function fadeIn(int $duration = 1): self
    {
        return $this->fade('in', $duration);
    }

    /**
     * Fade out
     */
    public function fadeOut(int $duration = 1): self
    {
        return $this->fade('out', $duration);
    }

    /**
     * Extract thumbnail at specific time
     */
    public function thumbnail(string $outputPath, string $time): self
    {
        $this->seek($time);
        $this->addOutputOption('vframes', 1);

        return $this;
    }

    /**
     * Extract multiple thumbnails
     */
    public function thumbnails(string $directory, int $count = 10): self
    {
        $this->addOutputOption('vf', "fps=1/{$count}");

        return $this;
    }

    /**
     * Apply blur effect
     */
    public function blur(int $strength = 5): self
    {
        return $this->addFilter("boxblur={$strength}:{$strength}");
    }

    /**
     * Apply sharpen effect
     */
    public function sharpen(int $strength = 5): self
    {
        $luma = $strength / 10;
        $chroma = $luma / 2;

        return $this->addFilter("unsharp=5:5:{$luma}:5:5:{$chroma}");
    }

    /**
     * Convert to grayscale
     */
    public function grayscale(): self
    {
        return $this->addFilter('hue=s=0');
    }

    /**
     * Apply sepia tone
     */
    public function sepia(): self
    {
        return $this->addFilter('colorchannelmixer=.393:.769:.189:0:.349:.686:.168:0:.272:.534:.131');
    }

    /**
     * Change playback speed
     */
    public function speed(float $multiplier): self
    {
        $videoSpeed = 1 / $multiplier;
        $audioSpeed = $multiplier;

        $this->addFilter("setpts={$videoSpeed}*PTS");
        $this->addOutputOption('af', "atempo={$audioSpeed}");

        return $this;
    }

    /**
     * Reverse video playback
     */
    public function reverse(): self
    {
        $this->addFilter('reverse');
        $this->addOutputOption('af', 'areverse');

        return $this;
    }
}
