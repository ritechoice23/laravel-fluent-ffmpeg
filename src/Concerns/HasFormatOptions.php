<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasFormatOptions
{
    /**
     * Set output format
     */
    public function outputFormat(string $format): self
    {
        return $this->addOutputOption('f', $format);
    }

    /**
     * Configure HLS output
     */
    public function hls(array $options = []): self
    {
        $this->outputFormat('hls');

        $segmentTime = $options['segment_time'] ?? 10;
        $playlistType = $options['playlist_type'] ?? 'vod';

        $this->addOutputOption('hls_time', $segmentTime);
        $this->addOutputOption('hls_playlist_type', $playlistType);

        if (isset($options['segment_filename'])) {
            $this->addOutputOption('hls_segment_filename', $options['segment_filename']);
        }

        return $this;
    }

    /**
     * Configure DASH output
     */
    public function dash(array $options = []): self
    {
        $this->outputFormat('dash');

        $segmentDuration = $options['segment_duration'] ?? 10;

        $this->addOutputOption('seg_duration', $segmentDuration);

        if (isset($options['init_seg_name'])) {
            $this->addOutputOption('init_seg_name', $options['init_seg_name']);
        }

        if (isset($options['media_seg_name'])) {
            $this->addOutputOption('media_seg_name', $options['media_seg_name']);
        }

        return $this;
    }

    /**
     * Configure GIF output
     */
    public function gif(array $options = []): self
    {
        $this->outputFormat('gif');

        $fps = $options['fps'] ?? 10;
        $width = $options['width'] ?? -1;

        $this->addOutputOption('vf', "fps={$fps},scale={$width}:-1:flags=lanczos");

        return $this;
    }

    /**
     * Convert to GIF (helper method)
     */
    public function toGif(array $options = []): self
    {
        return $this->gif($options);
    }
}
