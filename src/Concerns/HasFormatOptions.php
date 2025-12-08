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

    /**
     * Convert to MP3 audio
     */
    public function toMp3(int $bitrate = 192): self
    {
        return $this->outputFormat('mp3')
            ->audioCodec('libmp3lame')
            ->audioBitrate("{$bitrate}k")
            ->removeVideo();
    }

    /**
     * Convert to MP4 video
     */
    public function toMp4(array $options = []): self
    {
        $this->outputFormat('mp4')
            ->videoCodec($options['video_codec'] ?? 'libx264')
            ->audioCodec($options['audio_codec'] ?? 'aac');

        // Add GOP settings for reliable indexing (unless explicitly disabled)
        if (! isset($options['skip_gop_settings']) || ! $options['skip_gop_settings']) {
            $this->gopSize($options['gop_size'] ?? 60)
                ->keyframeInterval($options['keyframe_interval'] ?? 60)
                ->sceneChangeThreshold($options['scene_threshold'] ?? 0);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        return $this;
    }

    /**
     * Convert to WebM video
     */
    public function toWebm(array $options = []): self
    {
        $this->outputFormat('webm')
            ->videoCodec($options['video_codec'] ?? 'libvpx-vp9')
            ->audioCodec($options['audio_codec'] ?? 'libopus');

        // Add GOP settings for reliable indexing (unless explicitly disabled)
        if (! isset($options['skip_gop_settings']) || ! $options['skip_gop_settings']) {
            $this->gopSize($options['gop_size'] ?? 60)
                ->keyframeInterval($options['keyframe_interval'] ?? 60)
                ->sceneChangeThreshold($options['scene_threshold'] ?? 0);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        return $this;
    }

    /**
     * Convert to AVI video
     */
    public function toAvi(array $options = []): self
    {
        $this->outputFormat('avi')
            ->videoCodec($options['video_codec'] ?? 'mpeg4')
            ->audioCodec($options['audio_codec'] ?? 'mp3');

        // Add GOP settings for reliable indexing (unless explicitly disabled)
        if (! isset($options['skip_gop_settings']) || ! $options['skip_gop_settings']) {
            $this->gopSize($options['gop_size'] ?? 60)
                ->keyframeInterval($options['keyframe_interval'] ?? 60)
                ->sceneChangeThreshold($options['scene_threshold'] ?? 0);
        }

        return $this;
    }

    /**
     * Convert to MOV video (QuickTime)
     */
    public function toMov(array $options = []): self
    {
        $this->outputFormat('mov')
            ->videoCodec($options['video_codec'] ?? 'libx264')
            ->audioCodec($options['audio_codec'] ?? 'aac');

        // Add GOP settings for reliable indexing (unless explicitly disabled)
        if (! isset($options['skip_gop_settings']) || ! $options['skip_gop_settings']) {
            $this->gopSize($options['gop_size'] ?? 60)
                ->keyframeInterval($options['keyframe_interval'] ?? 60)
                ->sceneChangeThreshold($options['scene_threshold'] ?? 0);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        return $this;
    }

    /**
     * Convert to FLV video (Flash Video)
     */
    public function toFlv(array $options = []): self
    {
        $this->outputFormat('flv')
            ->videoCodec($options['video_codec'] ?? 'flv')
            ->audioCodec($options['audio_codec'] ?? 'mp3');

        // Add GOP settings for reliable indexing (unless explicitly disabled)
        if (! isset($options['skip_gop_settings']) || ! $options['skip_gop_settings']) {
            $this->gopSize($options['gop_size'] ?? 60)
                ->keyframeInterval($options['keyframe_interval'] ?? 60)
                ->sceneChangeThreshold($options['scene_threshold'] ?? 0);
        }

        return $this;
    }

    /**
     * Convert to MKV video (Matroska)
     */
    public function toMkv(array $options = []): self
    {
        $this->outputFormat('matroska')
            ->videoCodec($options['video_codec'] ?? 'libx264')
            ->audioCodec($options['audio_codec'] ?? 'aac');

        // Add GOP settings for reliable indexing (unless explicitly disabled)
        if (! isset($options['skip_gop_settings']) || ! $options['skip_gop_settings']) {
            $this->gopSize($options['gop_size'] ?? 60)
                ->keyframeInterval($options['keyframe_interval'] ?? 60)
                ->sceneChangeThreshold($options['scene_threshold'] ?? 0);
        }

        if (isset($options['quality'])) {
            $this->quality($options['quality']);
        }

        return $this;
    }

    /**
     * Convert to WAV audio
     */
    public function toWav(int $sampleRate = 44100): self
    {
        return $this->outputFormat('wav')
            ->audioCodec('pcm_s16le')
            ->audioSampleRate($sampleRate)
            ->removeVideo();
    }

    /**
     * Convert to AAC audio
     */
    public function toAac(int $bitrate = 192): self
    {
        return $this->outputFormat('adts')
            ->audioCodec('aac')
            ->audioBitrate("{$bitrate}k")
            ->removeVideo();
    }

    /**
     * Convert to OGG audio
     */
    public function toOgg(int $quality = 5): self
    {
        return $this->outputFormat('ogg')
            ->audioCodec('libvorbis')
            ->audioQuality($quality)
            ->removeVideo();
    }

    /**
     * Convert to M4A audio (AAC in MP4 container)
     */
    public function toM4a(int $bitrate = 192): self
    {
        return $this->outputFormat('mp4')
            ->audioCodec('aac')
            ->audioBitrate("{$bitrate}k")
            ->removeVideo();
    }
}
