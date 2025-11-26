# Advanced HLS Streaming

## Overview

The package provides a powerful `exportForHLS()` method that automates the creation of multi-bitrate HLS streams. It handles:
- Generating multiple variant streams (1080p, 720p, etc.)
- Setting correct H.264 profiles and levels
- Calculating appropriate bitrates
- Creating the master playlist
- Segmenting and naming files

## Quick Start

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

FFmpeg::fromPath('video.mp4')
    ->exportForHLS()
    ->addFormat('1080p')
    ->addFormat('720p')
    ->addFormat('480p')
    ->save('videos/stream.m3u8');
```

This single command will generate:
- `stream.m3u8` (Master playlist)
- `stream_1080p.m3u8` & segments
- `stream_720p.m3u8` & segments
- `stream_480p.m3u8` & segments

## Customizing Formats

You can customize the bitrate for each format:

```php
FFmpeg::fromPath('video.mp4')
    ->exportForHLS()
    ->addFormat('1080p', '6000k', '192k') // Custom video/audio bitrate
    ->addFormat('720p', '3000k', '128k')
    ->save('stream.m3u8');
```

## Advanced Configuration

### Segment Length

Set the duration of each segment (default: 10 seconds):

```php
FFmpeg::fromPath('video.mp4')
    ->exportForHLS()
    ->setSegmentLength(4) // 4 seconds
    ->addFormat('1080p')
    ->save('stream.m3u8');
```

### Supported Resolutions

The exporter supports standard resolutions with smart defaults:

| Resolution | Default Bitrate | Profile | Level |
|------------|-----------------|---------|-------|
| 2160p (4K) | 8000k | High | 5.1 |
| 1440p (2K) | 5000k | High | 5.0 |
| 1080p | 2500k | High | 4.2 |
| 720p | 1200k | Main | 3.1 |
| 480p | 800k | Main | 3.1 |
| 360p | 500k | Main | 3.0 |

## How It Works

The exporter uses a robust multi-pass approach:
1. It analyzes your requested formats.
2. It generates a separate FFmpeg command for each variant to ensure optimal encoding settings (profile, level, GOP size).
3. It creates a master playlist (`.m3u8`) that references all variants with correct bandwidth and resolution metadata.

This ensures maximum compatibility with HLS players (AVPlayer, ExoPlayer, hls.js) and avoids common issues with single-pass complex filter graphs.

## Example: Controller Implementation

```php
public function processVideo(Request $request)
{
    $video = $request->file('video');
    $path = $video->store('uploads');
    
    // Queue the HLS generation
    ProcessHlsJob::dispatch($path);
    
    return response()->json(['message' => 'Processing started']);
}
```

**ProcessHlsJob.php**:
```php
public function handle()
{
    FFmpeg::fromPath(storage_path("app/{$this->path}"))
        ->exportForHLS()
        ->addFormat('1080p')
        ->addFormat('720p')
        ->addFormat('480p')
        ->setSegmentLength(6)
        ->save(storage_path('app/public/videos/stream.m3u8'));
}
```
