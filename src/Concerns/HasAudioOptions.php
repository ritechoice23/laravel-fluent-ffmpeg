<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasAudioOptions
{
    /**
     * Set audio codec
     */
    public function audioCodec(?string $codec = null): self
    {
        $codec = $codec ?? config('fluent-ffmpeg.defaults.audio.codec', 'aac');

        return $this->addOutputOption('c:a', $codec);
    }

    /**
     * Set audio bitrate
     */
    public function audioBitrate(?string $bitrate = null): self
    {
        $bitrate = $bitrate ?? config('fluent-ffmpeg.defaults.audio.bitrate', '128k');

        return $this->addOutputOption('b:a', $bitrate);
    }

    /**
     * Set audio channels
     */
    public function audioChannels(?int $channels = null): self
    {
        $channels = $channels ?? config('fluent-ffmpeg.defaults.audio.channels', 2);

        return $this->addOutputOption('ac', $channels);
    }

    /**
     * Set audio sample rate
     */
    public function audioSampleRate(?int $rate = null): self
    {
        $rate = $rate ?? config('fluent-ffmpeg.defaults.audio.sample_rate', 44100);

        return $this->addOutputOption('ar', $rate);
    }

    /**
     * Set audio quality
     */
    public function audioQuality(?int $quality = null): self
    {
        if ($quality) {
            return $this->addOutputOption('q:a', $quality);
        }

        return $this;
    }

    /**
     * Remove audio track
     */
    public function removeAudio(): self
    {
        return $this->addOutputOption('an', true);
    }
}
