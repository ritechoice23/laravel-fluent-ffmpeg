# Audio Waveform Generation

Generate audio waveforms for visualization with wavesurfer.js and similar libraries.

**Note:** When using `withPeaks()` with transcoding, the conversion runs first, then peaks are generated from the original input file. This ensures optimal quality and performance.

## Basic Usage

### With Transcoding

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

FFmpeg::fromPath('input.mp3')
    ->audioCodec('aac')
    ->audioBitrate('128k')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->save('output.m4a');
// 1. Converts input.mp3 → output.m4a
// 2. Generates peaks from input.mp3 → output-peaks.json
```

### Peaks Only

```php
// Using 'only' parameter
FFmpeg::fromPath('input.mp3')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1], only: true)
    ->save('peaks.json');

// Auto-detected by .json extension
FFmpeg::fromPath('input.mp3')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->save('audio-peaks.json');
```

### With Video

```php
FFmpeg::fromPath('input.mp4')
    ->videoCodec('libx264')
    ->audioCodec('aac')
    ->withPeaks(normalizeRange: [0, 1])
    ->save('output.mp4');
// Peaks saved to: output-peaks.json
```

### With S3 Storage

```php
FFmpeg::fromDisk('s3', 'uploads/audio.mp3')
    ->audioCodec('aac')
    ->audioBitrate('128k')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->onProgress(fn($progress) => broadcast(new TranscodeProgress($progress)))
    ->toDisk('s3', 'processed/audio.m4a');
// Direct FFmpeg → S3 upload, peaks saved to: processed/audio-peaks.json
```

## wavesurfer.js Integration

### Backend (Laravel)

```php
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AudioController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('audio');
        $inputPath = $file->store('uploads', 's3');

        FFmpeg::fromDisk('s3', $inputPath)
            ->audioCodec('aac')
            ->audioBitrate('128k')
            ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
            ->toDisk('s3', 'processed/audio.m4a');

        return response()->json([
            'audio_url' => Storage::disk('s3')->url('processed/audio.m4a'),
            'peaks_url' => Storage::disk('s3')->url('processed/audio-peaks.json'),
        ]);
    }
}
```

### Frontend (JavaScript)

```javascript
// Fetch the auto-generated peaks file
const response = await fetch("/storage/processed/audio-peaks.json");
const peaksData = await response.json();

// If using 'simple' format (default):
const wavesurfer = WaveSurfer.create({
    container: "#waveform",
    waveColor: "rgb(200, 0, 200)",
    progressColor: "rgb(100, 0, 100)",
    url: "/storage/processed/audio.m4a",
    peaks: [peaksData], // peaksData is already an array
});

// If using 'full' format:
const wavesurfer = WaveSurfer.create({
    container: "#waveform",
    waveColor: "rgb(200, 0, 200)",
    progressColor: "rgb(100, 0, 100)",
    url: "/storage/processed/audio.m4a",
    peaks: [peaksData.data], // Extract the data array
});
```

## Configuration

### Output Format

```php
// Simple format (default)
FFmpeg::fromPath('input.mp3')
    ->withPeaks(format: 'simple')
    ->save('output.m4a');

// Full format with metadata
FFmpeg::fromPath('input.mp3')
    ->withPeaks(format: 'full')
    ->save('output.m4a');
```

**Simple Format (default):** `output-peaks.json`

```json
[0.1, 0.3, 0.2, 0.4, 0.5, 0.6, ...]
```

**Full Format:** `output-peaks.json`

```json
{
    "version": 2,
    "channels": 2,
    "sample_rate": 44100,
    "samples_per_pixel": 512,
    "bits": 32,
    "length": 1000,
    "data": [0.1, 0.3, 0.2, 0.4, 0.5, 0.6, ...]
}
```

**Simple:** Direct use with wavesurfer.js, minimal payload
**Full:** Includes metadata (channels, sample rate, etc.)

### Custom Filename

```php
// String
FFmpeg::fromPath('input.mp3')
    ->withPeaks(peaksFilename: 'custom-waveform.json')
    ->save('output.m4a');

// Callback
FFmpeg::fromPath('input.mp3')
    ->withPeaks(peaksFilename: fn($output) => str_replace('.m4a', '.waveform.json', $output))
    ->save('processed/audio.m4a');
```

Filenames are validated to prevent directory traversal attacks.

### Normalization

```php
->withPeaks(normalizeRange: null)      // Raw PCM values
->withPeaks(normalizeRange: [0, 1])    // 0 to 1 (best for wavesurfer.js)
->withPeaks(normalizeRange: [-1, 1])   // -1 to 1 (preserves direction)
->withPeaks(normalizeRange: [0, 255])  // Custom range
```

### Resolution

```php
->withPeaks(samplesPerPixel: 256)   // High detail
->withPeaks(samplesPerPixel: 512)   // Default (balanced)
->withPeaks(samplesPerPixel: 2048)  // Low detail (for long files)
```

### Source File Selection

```php
// Use original input (default - better quality)
->withPeaks(useProcessedFile: false)

// Use processed output (useful after trimming/filters)
->withPeaks(useProcessedFile: true)

// Example: Peaks match trimmed video
FFmpeg::fromPath('long-video.mp4')
    ->seek('00:01:00')
    ->stopAt('00:02:00')
    ->withPeaks(useProcessedFile: true)  // Peaks only for 1-minute segment
    ->save('clip.mp4');
```

## Progress Tracking

```php
FFmpeg::fromDisk('s3', 'large-file.mp3')
    ->audioCodec('aac')
    ->withPeaks(normalizeRange: [0, 1])
    ->onProgress(function($progress) {
        broadcast(new AudioProcessingProgress($progress));
    })
    ->toDisk('s3', 'output.m4a');
```

### With Laravel Broadcasting

```php
// app/Events/AudioProcessingProgress.php
class AudioProcessingProgress implements ShouldBroadcast
{
    public function __construct(public array $progress) {}

    public function broadcastOn()
    {
        return new Channel('audio-processing');
    }
}
```

```javascript
// Frontend
Echo.channel("audio-processing").listen("AudioProcessingProgress", (e) => {
    console.log(`Progress: ${e.progress.time_processed}s`);
});
```

## Performance

-   Pre-generate peaks on upload (not on-demand)
-   Use higher `samplesPerPixel` for longer files
-   Enable S3 streaming to avoid temp files
-   Use queues for large files:

```php
dispatch(function() use ($inputPath, $outputPath) {
    FFmpeg::fromDisk('s3', $inputPath)
        ->audioCodec('aac')
        ->withPeaks(samplesPerPixel: 1024, normalizeRange: [0, 1])
        ->toDisk('s3', $outputPath);
});
```

## How It Works

**Two-pass approach for local files:**

1. **Conversion:** FFmpeg transcodes the input file to the output format
2. **Peaks Generation:** Analyzes either the original input (default) or processed output based on `useProcessedFile` setting

**Default behavior (`useProcessedFile: false`):** Uses original input for better quality
**With trimming/filters (`useProcessedFile: true`):** Uses processed output so peaks match the final result

**For S3/cloud storage:** Uses FFmpeg's streaming output to upload both converted file and peaks simultaneously.

## Without Peaks

```php
FFmpeg::fromPath('input.mp3')
    ->audioCodec('aac')
    ->save('output.m4a');
// Works as normal, no peaks generated
```

## Multi-Channel Audio

```php
FFmpeg::fromPath('stereo.mp3')
    ->withPeaks(normalizeRange: [0, 1])
    ->save('output.m4a');
// Peaks saved as interleaved: [ch1_min, ch1_max, ch2_min, ch2_max, ...]
```

```javascript
const response = await fetch("/storage/output-peaks.json");
const peaks = await response.json();

const channelPeaks = [
    peaks.filter((_, i) => i % 4 < 2),
    peaks.filter((_, i) => i % 4 >= 2),
];

WaveSurfer.create({
    container: "#waveform",
    url: "/storage/output.m4a",
    peaks: channelPeaks,
    splitChannels: true,
});
```

## Troubleshooting

**Large files slow:** Increase `samplesPerPixel` to 2048
**S3 streaming fails:** Set `FFMPEG_S3_STREAMING=false`
**Memory issues:** Increase PHP memory limit
