<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasCompatibilityOptions
{
    /**
     * Optimize for web playback (maximum compatibility)
     */
    public function webOptimized(): self
    {
        // H.264 baseline profile for maximum compatibility
        $this->videoCodec('libx264')
            ->addOutputOption('profile:v', 'baseline')
            ->addOutputOption('level', '3.0')
            ->pixelFormat('yuv420p');  // Compatible with all devices

        // AAC audio for universal support
        $this->audioCodec('aac')
            ->audioChannels(2);

        // GOP and keyframe settings for reliable streaming
        $this->addOutputOption('g', 60)
            ->addOutputOption('keyint_min', 60)
            ->addOutputOption('sc_threshold', 0);

        // Enable fast start for web streaming
        $this->addOutputOption('movflags', '+faststart');

        return $this;
    }

    /**
     * Optimize for mobile devices
     */
    public function mobileOptimized(): self
    {
        // H.264 main profile, level 3.1 (iOS/Android compatible)
        $this->videoCodec('libx264')
            ->addOutputOption('profile:v', 'main')
            ->addOutputOption('level', '3.1')
            ->pixelFormat('yuv420p');

        // AAC-LC audio (universal mobile support)
        $this->audioCodec('aac')
            ->audioChannels(2)
            ->audioSampleRate(44100);

        // GOP and keyframe settings for mobile playback
        $this->addOutputOption('g', 60)
            ->addOutputOption('keyint_min', 60)
            ->addOutputOption('sc_threshold', 0);

        // Fast start for progressive download
        $this->addOutputOption('movflags', '+faststart');

        return $this;
    }

    /**
     * Universal compatibility (plays everywhere)
     */
    public function universalCompatibility(): self
    {
        // Most compatible H.264 settings
        $this->videoCodec('libx264')
            ->addOutputOption('profile:v', 'baseline')
            ->addOutputOption('level', '3.0')
            ->pixelFormat('yuv420p')
            ->frameRate(30);

        // AAC audio with conservative settings
        $this->audioCodec('aac')
            ->audioBitrate('128k')
            ->audioChannels(2)
            ->audioSampleRate(44100);

        // GOP and keyframe settings for proper indexing
        // GOP size of 60 = 2 seconds at 30fps (ensures regular keyframes)
        $this->addOutputOption('g', 60)
            ->addOutputOption('keyint_min', 60)
            ->addOutputOption('sc_threshold', 0);  // Disable scene change detection

        // MP4 container with fast start
        $this->outputFormat('mp4')
            ->addOutputOption('movflags', '+faststart');

        return $this;
    }

    /**
     * Optimize for iOS devices (iPhone, iPad)
     */
    public function iosOptimized(): self
    {
        // H.264 high profile (iOS 4+)
        $this->videoCodec('libx264')
            ->addOutputOption('profile:v', 'high')
            ->addOutputOption('level', '4.0')
            ->pixelFormat('yuv420p');

        // AAC audio
        $this->audioCodec('aac')
            ->audioChannels(2);

        // GOP and keyframe settings for iOS
        $this->addOutputOption('g', 60)
            ->addOutputOption('keyint_min', 60)
            ->addOutputOption('sc_threshold', 0);

        // Fast start for QuickTime/iOS
        $this->addOutputOption('movflags', '+faststart');

        return $this;
    }

    /**
     * Optimize for Android devices
     */
    public function androidOptimized(): self
    {
        // H.264 main profile (Android 3.0+)
        $this->videoCodec('libx264')
            ->addOutputOption('profile:v', 'main')
            ->addOutputOption('level', '3.1')
            ->pixelFormat('yuv420p');

        // AAC audio
        $this->audioCodec('aac')
            ->audioChannels(2);

        // GOP and keyframe settings for Android
        $this->addOutputOption('g', 60)
            ->addOutputOption('keyint_min', 60)
            ->addOutputOption('sc_threshold', 0);

        // Fast start for Android MediaPlayer
        $this->addOutputOption('movflags', '+faststart');

        return $this;
    }

    /**
     * Enable fast start (for web streaming)
     */
    public function fastStart(): self
    {
        return $this->addOutputOption('movflags', '+faststart');
    }

    /**
     * Set H.264 profile and level
     */
    public function h264Profile(string $profile = 'high', string $level = '4.0'): self
    {
        $this->addOutputOption('profile:v', $profile);
        $this->addOutputOption('level', $level);

        return $this;
    }

    /**
     * Set GOP (Group of Pictures) size - controls keyframe interval
     *
     * @param  int  $gopSize  Number of frames between keyframes (e.g., 60 = 2s at 30fps)
     */
    public function gopSize(int $gopSize): self
    {
        return $this->addOutputOption('g', $gopSize);
    }

    /**
     * Set keyframe interval (minimum distance between keyframes)
     *
     * @param  int  $interval  Minimum keyframe interval
     */
    public function keyframeInterval(int $interval): self
    {
        return $this->addOutputOption('keyint_min', $interval);
    }

    /**
     * Control scene change detection threshold
     * Set to 0 to disable automatic keyframe insertion on scene changes
     *
     * @param  int  $threshold  Scene change threshold (0 = disabled)
     */
    public function sceneChangeThreshold(int $threshold): self
    {
        return $this->addOutputOption('sc_threshold', $threshold);
    }

    /**
     * Set complete GOP and keyframe settings for reliable indexing
     * Recommended for video editing and streaming
     *
     * @param  int  $gopSize  GOP size (default: 60 frames)
     * @param  bool  $disableSceneDetection  Disable scene change detection (default: true)
     */
    public function reliableKeyframes(int $gopSize = 60, bool $disableSceneDetection = true): self
    {
        $this->addOutputOption('g', $gopSize)
            ->addOutputOption('keyint_min', $gopSize);

        if ($disableSceneDetection) {
            $this->addOutputOption('sc_threshold', 0);
        }

        return $this;
    }
}
