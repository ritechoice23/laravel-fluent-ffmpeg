# Subtitle Options

## Overview

The package provides methods to handle subtitles, including adding subtitle tracks, burning subtitles into the video, and extracting subtitles.

## Adding Subtitles

To add a subtitle file as an input (e.g., for soft subs):

```php
FFmpeg::fromPath('video.mp4')
    ->addSubtitle('subs.srt')
    ->subtitleCodec('mov_text') // Optional: convert to MP4 compatible format
    ->save('output.mp4');
```

## Burning Subtitles

To burn subtitles into the video (hard subs):

```php
FFmpeg::fromPath('video.mp4')
    ->burnSubtitles('subs.srt')
    ->save('output.mp4');
```

> **Note:** This uses the `subtitles` filter. The path to the subtitle file is automatically escaped for FFmpeg.

## Extracting Subtitles

To extract subtitles from a video file:

```php
FFmpeg::fromPath('video.mkv')
    ->extractSubtitles()
    ->save('subs.srt');
```

You can also specify the stream index if there are multiple subtitle tracks:

```php
// Extract the second subtitle stream (index 1)
FFmpeg::fromPath('video.mkv')
    ->extractSubtitles(1)
    ->save('subs.srt');
```

## Copying Subtitles

To simply copy subtitles from input to output without re-encoding:

```php
FFmpeg::fromPath('video.mkv')
    ->subtitleCodec('copy')
    ->save('output.mkv');
```
