# Laravel Fluent FFmpeg

![Laravel Fluent FFmpeg Thumbnail](assets/thumbnail.png)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ritechoice23/laravel-fluent-ffmpeg.svg?style=flat-square)](https://packagist.org/packages/ritechoice23/laravel-fluent-ffmpeg)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ritechoice23/laravel-fluent-ffmpeg/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/ritechoice23/laravel-fluent-ffmpeg/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ritechoice23/laravel-fluent-ffmpeg/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/ritechoice23/laravel-fluent-ffmpeg/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/ritechoice23/laravel-fluent-ffmpeg.svg?style=flat-square)](https://packagist.org/packages/ritechoice23/laravel-fluent-ffmpeg)

A fluent, chainable API for working with FFmpeg in Laravel applications. Process videos and audio with an elegant, expressive syntax.

## Installation

```bash
composer require ritechoice23/laravel-fluent-ffmpeg
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=fluent-ffmpeg-config
```

## Quick Start

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

// Basic video conversion
FFmpeg::fromPath('video.mp4')
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->resolution(1920, 1080)
    ->save('output.mp4');

// Or using the global helper
ffmpeg()->fromPath('video.mp4')
    ->videoCodec('libx264')
    ->save('output.mp4');

// Extract audio
FFmpeg::fromPath('video.mp4')
    ->extractAudio()
    ->save('audio.mp3');

// Create GIF
FFmpeg::fromPath('video.mp4')
    ->clip('00:00:05', '00:00:10')
    ->toGif(['fps' => 15, 'width' => 480])
    ->save('animation.gif');

// Advanced HLS Streaming (Multi-bitrate)
FFmpeg::fromPath('video.mp4')
    ->exportForHLS()
    ->addFormat('1080p')
    ->addFormat('720p')
    ->addFormat('480p')
    ->save('stream.m3u8');
```

## Features

-   **Fluent API** - Chainable, expressive syntax
-   **20+ Filters** - Watermarks, effects, transformations
-   **Multiple Formats** - MP4, HLS, DASH, GIF, and more
-   **Laravel Disks** - Save to S3, local, or any disk
-   **Progress Tracking** - Real-time progress with broadcasting
-   **Queue Support** - Process videos in background
-   **Smart Defaults** - Sensible defaults from config
-   **Events** - Track processing lifecycle
-   **Fully Tested** - 65+ passing tests

## Documentation

-   [Installation & Configuration](docs/installation.md)
-   [Basic Usage](docs/basic-usage.md)
-   [Video Options](docs/video-options.md)
-   [Audio Options](docs/audio-options.md)
-   [Subtitle Options](docs/subtitle-options.md)
-   [Filters & Effects](docs/filters.md)
-   [Formats & Streaming](docs/formats.md)
-   [Advanced HLS](docs/hls.md)
-   [Cross-Platform Compatibility](docs/compatibility.md)
-   [Laravel Integration](docs/laravel-integration.md)
-   [Queue Processing](docs/queue.md)
-   [Events & Broadcasting](docs/events.md)
-   [Helper Methods](docs/helpers.md)
-   [Package Lifecycle](docs/lifecycle.md)
-   [API Reference](docs/api-reference.md)

## Requirements

-   PHP 8.2+
-   Laravel 10.0+
-   FFmpeg 4.0+

## Testing

```bash
composer test
```

## Credits

-   [Daramola Babatunde Ebenezer](https://github.com/ritechoice23)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
