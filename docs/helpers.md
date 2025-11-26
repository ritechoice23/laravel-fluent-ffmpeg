# Helper Methods

## Extract Audio

```php
FFmpeg::fromPath('video.mp4')
    ->extractAudio()
    ->audioCodec('mp3')
    ->audioBitrate('320k')
    ->save('audio.mp3');
```

## Create GIF

```php
FFmpeg::fromPath('video.mp4')
    ->clip('00:00:05', '00:00:10')
    ->toGif(['fps' => 15, 'width' => 480])
    ->save('animation.gif');
```

## Video from Images

```php
FFmpeg::fromImages('images/%03d.png', ['framerate' => 24])
    ->duration(10)
    ->save('slideshow.mp4');
```

## Waveform Visualization

```php
FFmpeg::fromPath('audio.mp3')
    ->waveform([
        'width' => 1920,
        'height' => 1080,
        'color' => 'white'
    ])
    ->save('waveform.png');
```

## Using Presets

```php
// Built-in preset
FFmpeg::fromPath('video.mp4')
    ->preset('1080p')
    ->save('output.mp4');

// Custom preset
FFmpeg::fromPath('video.mp4')
    ->preset([
        'resolution' => [1920, 1080],
        'video_bitrate' => '5000k',
        'audio_bitrate' => '192k'
    ])
    ->save('output.mp4');
```

## HLS Streaming

```php
FFmpeg::fromPath('video.mp4')
    ->preset('1080p')
    ->hls([
        'segment_time' => 10,
        'playlist_type' => 'vod'
    ])
    ->save('stream.m3u8');
```

## DASH Streaming

```php
FFmpeg::fromPath('video.mp4')
    ->dash(['segment_duration' => 10])
    ->save('manifest.mpd');
```
