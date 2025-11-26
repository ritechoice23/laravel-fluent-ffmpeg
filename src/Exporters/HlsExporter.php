<?php

namespace Ritechoice23\FluentFFmpeg\Exporters;

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

class HlsExporter
{
    protected array $formats = [];

    protected string $segmentLength = '10';

    protected string $playlistType = 'vod';

    protected bool $encrypted = false;

    protected ?string $keyInfoPath = null;

    public function __construct(protected FFmpegBuilder $builder)
    {
    }

    /**
     * Add a format to the HLS export
     */
    public function addFormat(string $resolution, ?string $bitrate = null, ?string $audioBitrate = null): self
    {
        // Smart defaults based on resolution
        if ($bitrate === null) {
            $bitrate = $this->getDefaultVideoBitrate($resolution);
        }

        if ($audioBitrate === null) {
            $audioBitrate = $this->getDefaultAudioBitrate($resolution);
        }

        $this->formats[] = [
            'resolution' => $resolution,
            'bitrate' => $bitrate,
            'audio_bitrate' => $audioBitrate,
            'level' => $this->getLevelForResolution($resolution),
            'profile' => $this->getProfileForResolution($resolution),
        ];

        return $this;
    }

    /**
     * Set segment length
     */
    public function setSegmentLength(int $seconds): self
    {
        $this->segmentLength = (string) $seconds;

        return $this;
    }

    /**
     * Save the HLS export
     */
    public function save(string $path): bool
    {
        // For now, we'll implement the robust multi-pass approach
        // 1. Generate each variant stream
        // 2. Generate the master playlist

        $masterPlaylist = "#EXTM3U\n#EXT-X-VERSION:3\n";
        $baseName = pathinfo($path, PATHINFO_FILENAME);
        $dirName = pathinfo($path, PATHINFO_DIRNAME);

        foreach ($this->formats as $format) {
            $variantName = "{$baseName}_{$format['resolution']}";
            $variantPath = "{$dirName}/{$variantName}.m3u8";

            // Clone builder for this variant
            $variantBuilder = clone $this->builder;

            // Parse resolution
            [$width, $height] = explode('x', $this->normalizeResolution($format['resolution']));

            $variantBuilder
                ->videoCodec('libx264')
                ->audioCodec('aac')
                ->resolution((int) $width, (int) $height)
                ->videoBitrate($format['bitrate'])
                ->audioBitrate($format['audio_bitrate'])
                ->addOutputOption('profile:v', $format['profile'])
                ->addOutputOption('level:v', $format['level'])
                ->addOutputOption('g', 48) // GOP size (approx 2s at 24fps)
                ->addOutputOption('keyint_min', 48)
                ->addOutputOption('sc_threshold', 0)
                ->addOutputOption('hls_time', $this->segmentLength)
                ->addOutputOption('hls_playlist_type', $this->playlistType)
                ->addOutputOption('hls_segment_filename', "{$dirName}/{$variantName}_%03d.ts")
                ->outputFormat('hls')
                ->save($variantPath);

            // Add to master playlist
            $bandwidth = (int) filter_var($format['bitrate'], FILTER_SANITIZE_NUMBER_INT) * 1000;
            $bandwidth += (int) filter_var($format['audio_bitrate'], FILTER_SANITIZE_NUMBER_INT) * 1000;

            $masterPlaylist .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$width}x{$height}\n";
            $masterPlaylist .= "{$variantName}.m3u8\n";
        }

        // Write master playlist
        return file_put_contents($path, $masterPlaylist) !== false;
    }

    protected function normalizeResolution(string $res): string
    {
        // Handle "1080p" style
        $map = [
            '2160p' => '3840x2160',
            '1440p' => '2560x1440',
            '1080p' => '1920x1080',
            '720p' => '1280x720',
            '480p' => '854x480',
            '360p' => '640x360',
        ];

        return $map[$res] ?? $res;
    }

    protected function getDefaultVideoBitrate(string $res): string
    {
        $res = $this->normalizeResolution($res);
        $map = [
            '3840x2160' => '8000k',
            '2560x1440' => '5000k',
            '1920x1080' => '2500k',
            '1280x720' => '1200k',
            '854x480' => '800k',
            '640x360' => '500k',
        ];

        return $map[$res] ?? '1000k';
    }

    protected function getDefaultAudioBitrate(string $res): string
    {
        $res = $this->normalizeResolution($res);
        // Higher quality audio for HD/4K
        if (in_array($res, ['3840x2160', '2560x1440', '1920x1080'])) {
            return '192k';
        }

        return '128k';
    }

    protected function getProfileForResolution(string $res): string
    {
        $res = $this->normalizeResolution($res);
        if (in_array($res, ['3840x2160', '2560x1440', '1920x1080'])) {
            return 'high';
        }

        return 'main';
    }

    protected function getLevelForResolution(string $res): string
    {
        $res = $this->normalizeResolution($res);
        $map = [
            '3840x2160' => '5.1',
            '2560x1440' => '5.0',
            '1920x1080' => '4.2',
            '1280x720' => '3.1',
            '854x480' => '3.1',
            '640x360' => '3.0',
        ];

        return $map[$res] ?? '3.1';
    }
}
