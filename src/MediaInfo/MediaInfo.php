<?php

namespace Ritechoice23\FluentFFmpeg\MediaInfo;

class MediaInfo
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get duration in seconds
     */
    public function duration(): ?float
    {
        return isset($this->data['format']['duration'])
            ? (float) $this->data['format']['duration']
            : null;
    }

    /**
     * Get format name
     */
    public function format(): ?string
    {
        return $this->data['format']['format_name'] ?? null;
    }

    /**
     * Get file size in bytes
     */
    public function size(): ?int
    {
        return isset($this->data['format']['size'])
            ? (int) $this->data['format']['size']
            : null;
    }

    /**
     * Get bitrate
     */
    public function bitrate(): ?int
    {
        return isset($this->data['format']['bit_rate'])
            ? (int) $this->data['format']['bit_rate']
            : null;
    }

    /**
     * Get video stream information
     */
    public function videoStream(): ?array
    {
        foreach ($this->data['streams'] ?? [] as $stream) {
            if ($stream['codec_type'] === 'video') {
                return $stream;
            }
        }

        return null;
    }

    /**
     * Get audio stream information
     */
    public function audioStream(): ?array
    {
        foreach ($this->data['streams'] ?? [] as $stream) {
            if ($stream['codec_type'] === 'audio') {
                return $stream;
            }
        }

        return null;
    }

    /**
     * Get video codec
     */
    public function videoCodec(): ?string
    {
        $video = $this->videoStream();

        return $video['codec_name'] ?? null;
    }

    /**
     * Get audio codec
     */
    public function audioCodec(): ?string
    {
        $audio = $this->audioStream();

        return $audio['codec_name'] ?? null;
    }

    /**
     * Get video resolution
     */
    public function resolution(): ?array
    {
        $video = $this->videoStream();

        if ($video && isset($video['width'], $video['height'])) {
            return [
                'width' => (int) $video['width'],
                'height' => (int) $video['height'],
            ];
        }

        return null;
    }

    /**
     * Get frame rate
     */
    public function frameRate(): ?float
    {
        $video = $this->videoStream();

        if ($video && isset($video['r_frame_rate'])) {
            // Parse fraction like "30/1" or "30000/1001"
            $parts = explode('/', $video['r_frame_rate']);
            if (count($parts) === 2 && $parts[1] > 0) {
                return (float) $parts[0] / (float) $parts[1];
            }
        }

        return null;
    }

    /**
     * Get all metadata
     */
    public function metadata(): array
    {
        return $this->data['format']['tags'] ?? [];
    }

    /**
     * Get specific metadata tag
     */
    public function getTag(string $key): ?string
    {
        return $this->data['format']['tags'][$key] ?? null;
    }

    /**
     * Get raw data
     */
    public function raw(): array
    {
        return $this->data;
    }
}
