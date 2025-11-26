<?php

namespace Ritechoice23\FluentFFmpeg\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ritechoice23\FluentFFmpeg\FluentFFmpegServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            FluentFFmpegServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up test configuration
        config()->set('fluent-ffmpeg.ffmpeg_path', '/usr/bin/ffmpeg');
        config()->set('fluent-ffmpeg.ffprobe_path', '/usr/bin/ffprobe');
        config()->set('fluent-ffmpeg.timeout', 3600);
    }
}
