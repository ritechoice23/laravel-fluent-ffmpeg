<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasVideoOptions
{
    /**
     * Set video codec
     */
    public function videoCodec(?string $codec = null): self
    {
        $codec = $codec ?? config('fluent-ffmpeg.defaults.video.codec', 'libx264');

        return $this->addOutputOption('c:v', $codec);
    }

    /**
     * Set output resolution
     */
    public function resolution(?int $width = null, ?int $height = null): self
    {
        if ($width && $height) {
            return $this->addOutputOption('s', "{$width}x{$height}");
        }

        return $this;
    }

    /**
     * Set aspect ratio
     */
    public function aspectRatio(?string $ratio = null): self
    {
        $ratio = $ratio ?? config('fluent-ffmpeg.defaults.video.aspect_ratio', '16:9');

        return $this->addOutputOption('aspect', $ratio);
    }

    /**
     * Set frame rate
     */
    public function frameRate(?int $fps = null): self
    {
        $fps = $fps ?? config('fluent-ffmpeg.defaults.video.frame_rate', 30);

        return $this->addOutputOption('r', $fps);
    }

    /**
     * Set video bitrate
     */
    public function videoBitrate(?string $bitrate = null): self
    {
        if ($bitrate) {
            return $this->addOutputOption('b:v', $bitrate);
        }

        return $this;
    }

    /**
     * Set quality (CRF value)
     */
    public function quality(?int $crf = null): self
    {
        $crf = $crf ?? config('fluent-ffmpeg.defaults.video.crf', 23);

        return $this->addOutputOption('crf', $crf);
    }

    /**
     * Set encoding preset (for x264/x265)
     */
    public function encodingPreset(?string $preset = null): self
    {
        $preset = $preset ?? config('fluent-ffmpeg.defaults.video.preset', 'medium');

        return $this->addOutputOption('preset', $preset);
    }

    /**
     * Set pixel format
     */
    public function pixelFormat(?string $format = null): self
    {
        $format = $format ?? config('fluent-ffmpeg.defaults.video.pixel_format', 'yuv420p');

        return $this->addOutputOption('pix_fmt', $format);
    }
}
