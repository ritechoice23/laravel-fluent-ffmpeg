<?php

namespace Ritechoice23\FluentFFmpeg;

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FluentFFmpegServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-fluent-ffmpeg')
            ->hasConfigFile('fluent-ffmpeg');
    }

    public function packageRegistered(): void
    {
        // Register the FFmpeg facade
        $this->app->bind('ffmpeg', function () {
            return new FFmpegBuilder;
        });
    }
}
