# Laravel Integration

## Disk Integration

### Input from Disk
```php
FFmpeg::fromDisk('s3', 'videos/input.mp4')
    ->resolution(1920, 1080)
    ->save('output.mp4');
```

### Output to Disk
```php
FFmpeg::fromPath('video.mp4')
    ->resolution(1920, 1080)
    ->toDisk('s3', 'videos/output.mp4');
```

### Mix and Match
```php
// S3 to local
FFmpeg::fromDisk('s3', 'input.mp4')
    ->save('local/output.mp4');

// Local to S3
FFmpeg::fromPath('local/input.mp4')
    ->toDisk('s3', 'output.mp4');
```

## Progress Tracking

### Callback
```php
FFmpeg::fromPath('video.mp4')
    ->onProgress(function ($progress) {
        echo "Progress: {$progress['time_processed']}s\n";
        echo "Speed: {$progress['speed']}x\n";
    })
    ->save('output.mp4');
```

### Broadcasting
```php
FFmpeg::fromPath('video.mp4')
    ->broadcastProgress('video-processing-channel')
    ->save('output.mp4');
```

Frontend (Laravel Echo):
```javascript
Echo.channel('video-processing-channel')
    .listen('FFmpegProgressUpdated', (e) => {
        console.log(e.progress);
    });
```

## Events

### Listening to Events
```php
use Ritechoice23\FluentFFmpeg\Events\FFmpegProcessCompleted;

Event::listen(FFmpegProcessCompleted::class, function ($event) {
    Log::info("Completed in {$event->duration}s");
    // Notify user, update database, etc.
});
```

### Available Events
- `FFmpegProcessStarted`
- `FFmpegProcessCompleted`
- `FFmpegProcessFailed`
- `FFmpegProgressUpdated` (broadcastable)

## Error Handling

```php
FFmpeg::fromPath('video.mp4')
    ->onError(function ($error) {
        Log::error('FFmpeg failed', ['error' => $error]);
    })
    ->save('output.mp4');
```
