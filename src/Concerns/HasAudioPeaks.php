<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Enums\PeaksFormat;

trait HasAudioPeaks
{
    /**
     * Peaks generation configuration
     */
    protected ?array $peaksConfig = null;

    /**
     * Enable peaks generation during transcoding
     *
     * @param  int  $samplesPerPixel  Number of audio samples per waveform min/max pair (higher = less detail, smaller file)
     * @param  array|null  $normalizeRange  Normalization range [min, max] or null for raw values
     *                                      Examples: [0, 1] for wavesurfer.js, [-1, 1] for signed normalized, null for raw values (-32768 to 32767)
     * @param  bool  $only  If true, only generate peaks without transcoding (lightweight mode)
     * @param  PeaksFormat|string  $format  Output format: PeaksFormat::SIMPLE or PeaksFormat::FULL (legacy string support)
     * @param  string|callable|null  $peaksFilename  Custom peaks filename (string or callback receiving output path)
     * @param  bool  $useProcessedFile  If true, generate peaks from processed output; if false, use original input (default: false for better quality)
     */
    public function withPeaks(
        int $samplesPerPixel = 512,
        ?array $normalizeRange = null,
        bool $only = false,
        PeaksFormat|string $format = PeaksFormat::SIMPLE,
        string|callable|null $peaksFilename = null,
        bool $useProcessedFile = false
    ): self {
        if ($normalizeRange !== null && count($normalizeRange) !== 2) {
            throw new \InvalidArgumentException('normalizeRange must be an array with exactly 2 values [min, max] or null');
        }

        // Support legacy string format for backward compatibility
        if (is_string($format)) {
            $format = PeaksFormat::tryFrom($format) ?? throw new \InvalidArgumentException(
                'format must be either "simple" or "full"'
            );
        }

        $this->peaksConfig = [
            'samples_per_pixel' => $samplesPerPixel,
            'normalize_range' => $normalizeRange,
            'only' => $only,
            'format' => $format->value,
            'peaks_filename' => $peaksFilename,
            'use_processed_file' => $useProcessedFile,
        ];

        return $this;
    }

    /**
     * Get peaks configuration
     */
    public function getPeaksConfig(): ?array
    {
        return $this->peaksConfig;
    }
}
