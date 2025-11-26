<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Actions\ProbeMediaFile;

trait HasMetadata
{
    /**
     * Set metadata
     */
    public function addMetadata(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->metadata[$key] = $value;
        }

        return $this;
    }

    /**
     * Set title
     */
    public function title(string $title): self
    {
        $this->metadata['title'] = $title;

        return $this;
    }

    /**
     * Set artist
     */
    public function artist(string $artist): self
    {
        $this->metadata['artist'] = $artist;

        return $this;
    }

    /**
     * Set comment
     */
    public function comment(string $comment): self
    {
        $this->metadata['comment'] = $comment;

        return $this;
    }

    /**
     * Set album
     */
    public function album(string $album): self
    {
        $this->metadata['album'] = $album;

        return $this;
    }

    /**
     * Set year
     */
    public function year(int $year): self
    {
        $this->metadata['date'] = (string) $year;

        return $this;
    }

    /**
     * Get all metadata from input file
     */
    public function getMetadata(): array
    {
        if (empty($this->inputs)) {
            return [];
        }

        $mediaInfo = app(ProbeMediaFile::class)->execute($this->inputs[0]);

        return $mediaInfo->metadata();
    }

    /**
     * Get title from input
     */
    public function getTitle(): ?string
    {
        return $this->getMetadata()['title'] ?? null;
    }

    /**
     * Get artist from input
     */
    public function getArtist(): ?string
    {
        return $this->getMetadata()['artist'] ?? null;
    }

    /**
     * Get duration from input
     */
    public function getDuration(): ?float
    {
        if (empty($this->inputs)) {
            return null;
        }

        $mediaInfo = app(ProbeMediaFile::class)->execute($this->inputs[0]);

        return $mediaInfo->duration();
    }

    /**
     * Get format from input
     */
    public function getFormat(): ?string
    {
        if (empty($this->inputs)) {
            return null;
        }

        $mediaInfo = app(ProbeMediaFile::class)->execute($this->inputs[0]);

        return $mediaInfo->format();
    }
}
