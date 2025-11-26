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

        // Fast start for QuickTime/iOS
        $this->addOutputOption('movflags', '+faststart');

        return $this;
    }

    /**
     * Optimize for Android devices
     */
    public function androidOptimized(): self
    {
        // H.264 main profile
        $this->videoCodec('libx264')
            ->addOutputOption('profile:v', 'main')
            ->addOutputOption('level', '3.1')
            ->pixelFormat('yuv420p');

        // AAC audio
        $this->audioCodec('aac')
            ->audioChannels(2);

        // Fast start
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
}
