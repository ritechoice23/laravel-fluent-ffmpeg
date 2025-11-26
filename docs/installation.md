# Installation & Configuration

## Requirements

- PHP 8.2+
- Laravel 10.0+
- FFmpeg 4.0+
- FFprobe (bundled with FFmpeg)

## Installation

```bash
composer require ritechoice23/laravel-fluent-ffmpeg
```

Publish configuration:

```bash
php artisan vendor:publish --tag=fluent-ffmpeg-config
```

## Configuration

Edit `config/fluent-ffmpeg.php`:

### Binary Paths
```php
'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),
'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),
```

### Defaults
```php
'defaults' => [
    'video' => ['codec' => 'libx264', 'preset' => 'medium'],
    'audio' => ['codec' => 'aac', 'bitrate' => '128k'],
],
```

### Presets
```php
'presets' => [
    '1080p' => ['resolution' => [1920, 1080], 'video_bitrate' => '5000k'],
],
```

## Verify Installation

```bash
ffmpeg -version
```
