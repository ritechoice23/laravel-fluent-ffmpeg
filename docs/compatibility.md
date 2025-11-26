# Cross-Platform Compatibility

## Overview

The package includes built-in optimizations to ensure videos play on all devices and platforms. These optimizations handle codec profiles, pixel formats, and container settings for maximum compatibility.

## Quick Start

### Universal Compatibility (Recommended)

```php
FFmpeg::fromPath('video.mp4')
    ->universalCompatibility()
    ->save('output.mp4');
```

This ensures the video plays on:
- ✅ All web browsers (Chrome, Firefox, Safari, Edge)
- ✅ iOS devices (iPhone, iPad)
- ✅ Android devices
- ✅ Smart TVs
- ✅ Desktop players (VLC, QuickTime, Windows Media Player)

## Compatibility Methods

### Web Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->webOptimized()
    ->save('output.mp4');
```

**Settings:**
- H.264 Baseline Profile (Level 3.0)
- YUV420P pixel format
- AAC audio (stereo)
- Fast start enabled (progressive download)

**Best for:** HTML5 video players, web streaming

### Mobile Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->mobileOptimized()
    ->save('output.mp4');
```

**Settings:**
- H.264 Main Profile (Level 3.1)
- Optimized for mobile bandwidth
- AAC-LC audio
- Fast start enabled

**Best for:** Mobile apps, responsive websites

### iOS Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->iosOptimized()
    ->save('output.mp4');
```

**Settings:**
- H.264 High Profile (Level 4.0)
- QuickTime-compatible
- AAC audio

**Best for:** iPhone, iPad, Safari

### Android Optimized

```php
FFmpeg::fromPath('video.mp4')
    ->androidOptimized()
    ->save('output.mp4');
```

**Settings:**
- H.264 Main Profile (Level 3.1)
- Wide device compatibility
- AAC audio

**Best for:** Android devices, Chrome

## Manual Settings

### H.264 Profile and Level

```php
FFmpeg::fromPath('video.mp4')
    ->h264Profile('high', '4.1')
    ->save('output.mp4');
```

**Profiles:**
- `baseline` - Maximum compatibility (older devices)
- `main` - Good balance (most devices)
- `high` - Best quality (modern devices)

**Levels:**
- `3.0` - SD quality, universal
- `3.1` - Mobile devices
- `4.0` - HD quality
- `4.1` - Full HD quality

### Fast Start

```php
FFmpeg::fromPath('video.mp4')
    ->fastStart()
    ->save('output.mp4');
```

Enables progressive download for web streaming. The video starts playing before fully downloaded.

## Compatibility Matrix

| Method | Web | iOS | Android | Smart TV | Desktop |
|--------|-----|-----|---------|----------|---------|
| `universalCompatibility()` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `webOptimized()` | ✅ | ✅ | ✅ | Limited | ✅ |
| `mobileOptimized()` | ✅ | ✅ | ✅ | Limited | ✅ |
| `iosOptimized()` | ✅ | ✅ | Limited | Limited | ✅ |
| `androidOptimized()` | ✅ | Limited | ✅ | Limited | ✅ |

✅ = Fully compatible | Limited = May have limitations

## Best Practices

### For Web Applications

```php
FFmpeg::fromPath('video.mp4')
    ->webOptimized()
    ->resolution(1920, 1080)
    ->videoBitrate('5000k')
    ->save('output.mp4');
```

### For Mobile Apps

```php
// Generate multiple qualities
foreach (['1080p', '720p', '480p'] as $quality) {
    FFmpeg::fromPath('video.mp4')
        ->mobileOptimized()
        ->preset($quality)
        ->save("output_{$quality}.mp4");
}
```

### For Maximum Reach

```php
FFmpeg::fromPath('video.mp4')
    ->universalCompatibility()
    ->resolution(1280, 720)  // Safe resolution
    ->videoBitrate('2500k')  // Moderate bitrate
    ->save('output.mp4');
```

## Technical Details

### Why YUV420P?

The `yuv420p` pixel format is used because:
- Supported by all H.264 decoders
- Required for QuickTime/iOS compatibility
- Smaller file sizes than yuv444p
- Standard for web video

### Why Baseline Profile?

Baseline profile ensures:
- Works on older devices
- No B-frames (simpler decoding)
- Lower CPU requirements
- Maximum compatibility

### Why Fast Start?

Fast start (`movflags +faststart`) moves metadata to the beginning of the file, enabling:
- Progressive download
- Faster playback start
- Better user experience
- Required for most web players

## Troubleshooting

### Video doesn't play on iPhone

```php
// Ensure YUV420P and fast start
FFmpeg::fromPath('video.mp4')
    ->pixelFormat('yuv420p')
    ->fastStart()
    ->save('output.mp4');
```

### Video doesn't play in browser

```php
// Use web-optimized settings
FFmpeg::fromPath('video.mp4')
    ->webOptimized()
    ->save('output.mp4');
```

### File size too large

```php
// Use lower profile and bitrate
FFmpeg::fromPath('video.mp4')
    ->h264Profile('baseline', '3.0')
    ->videoBitrate('1500k')
    ->save('output.mp4');
```
