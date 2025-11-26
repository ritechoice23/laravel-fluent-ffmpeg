<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasSubtitleOptions
{
    /**
     * Set subtitle codec
     */
    public function subtitleCodec(string $codec): self
    {
        return $this->addOutputOption('c:s', $codec);
    }

    /**
     * Burn subtitles into video
     */
    public function burnSubtitles(string $path, array $options = []): self
    {
        // Escape path for filter
        $path = str_replace('\\', '/', $path);
        $path = str_replace(':', '\\:', $path);

        return $this->addFilter("subtitles='{$path}'");
    }

    /**
     * Add subtitle file as input
     */
    public function addSubtitle(string $path): self
    {
        return $this->addInput($path);
    }

    /**
     * Extract subtitles from input
     */
    public function extractSubtitles(?int $streamIndex = 0): self
    {
        // Disable video and audio
        $this->addOutputOption('vn', true);
        $this->addOutputOption('an', true);

        // Map subtitle stream
        $this->addOutputOption('map', "0:s:{$streamIndex}");

        // Default to srt format if not specified
        if (! isset($this->outputOptions['f'])) {
            $this->outputFormat('srt');
        }

        return $this;
    }
}
