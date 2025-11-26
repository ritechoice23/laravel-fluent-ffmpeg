# Basic Usage

## Simple Conversion

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

FFmpeg::fromPath('video.mp4')
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->save('output.mp4');

// Or using the helper
ffmpeg()->fromPath('video.mp4')
    ->videoCodec('libx264')
    ->save('output.mp4');
```

## Using Smart Defaults

```php
// Methods without parameters use config defaults
FFmpeg::fromPath('video.mp4')
    ->videoCodec()      // Uses 'libx264' from config
    ->audioCodec()      // Uses 'aac' from config
    ->save('output.mp4');
```

## Input Sources

```php
// Local file
FFmpeg::fromPath('video.mp4')

// Laravel disk
FFmpeg::fromDisk('s3', 'videos/input.mp4')

// URL
FFmpeg::fromUrl('https://example.com/video.mp4')

// Uploaded file
FFmpeg::fromUploadedFile($request->file('video'))
```

## Output Destinations

```php
// Local path
->save('output.mp4')

// Laravel disk
->toDisk('s3', 'videos/output.mp4')

// Download
->download('video.mp4')

// Get command without executing
->getCommand()
```

## Method Chaining

```php
FFmpeg::fromPath('video.mp4')
    ->resolution(1920, 1080)
    ->frameRate(30)
    ->videoBitrate('5000k')
    ->audioBitrate('192k')
    ->save('output.mp4');
```

## Time Options

```php
FFmpeg::fromPath('video.mp4')
    ->startFrom('00:00:10')  // Start at 10 seconds (alias for seek)
    ->stopAt('00:00:20')     // Stop at 20 seconds
    ->duration('10')         // Duration of 10 seconds
    ->save('cut.mp4');
```

## Inspecting Media

```php
$info = FFmpeg::probe('video.mp4');

echo $info->duration();     // 120.5
echo $info->videoCodec();   // h264
print_r($info->resolution()); // ['width' => 1920, 'height' => 1080]
```
