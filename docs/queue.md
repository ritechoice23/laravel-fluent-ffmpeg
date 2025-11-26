# Queue Processing

## Using ProcessVideoJob

```php
use Ritechoice23\FluentFFmpeg\Jobs\ProcessVideoJob;

ProcessVideoJob::dispatch(
    'input.mp4',
    'output.mp4',
    [
        'resolution' => [1920, 1080],
        'videoCodec' => ['libx264'],
        'preset' => ['1080p']
    ]
)->onQueue('video-processing');
```

## With Output Disk

```php
ProcessVideoJob::dispatch(
    'input.mp4',
    'videos/output.mp4',
    ['resolution' => [1920, 1080]],
    's3'  // Output disk
);
```

## Controller Example

```php
public function processVideo(Request $request)
{
    $request->validate([
        'video' => 'required|file|mimes:mp4,mov|max:512000'
    ]);

    $file = $request->file('video');
    $userId = auth()->id();
    
    // Queue processing for multiple qualities
    foreach (['1080p', '720p', '480p'] as $quality) {
        ProcessVideoJob::dispatch(
            $file->getRealPath(),
            "videos/{$userId}/{$quality}.mp4",
            ['preset' => [$quality]],
            's3'
        )->onQueue('video-processing');
    }

    return response()->json(['message' => 'Processing started']);
}
```

## Custom Queue Configuration

```php
ProcessVideoJob::dispatch(...)
    ->onQueue('high-priority')
    ->onConnection('redis')
    ->delay(now()->addMinutes(5));
```
