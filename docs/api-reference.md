# API Reference

This document provides a comprehensive reference for all available methods in the `FFmpegBuilder` class and its traits.

## Core Methods (`FFmpegBuilder`)

| Method                                         | Description                                                            |
| ---------------------------------------------- | ---------------------------------------------------------------------- |
| `fromPath(string\|array $path)`                | Set input file(s) from path.                                           |
| `fromPaths(array $paths)`                      | Set multiple input files from paths.                                   |
| `addInput(string $path)`                       | Add an additional input file.                                          |
| `fromDisk(string $disk, string $path)`         | Set input file from a Laravel disk (supports S3 streaming).            |
| `fromUrl(string $url)`                         | Set input file from a URL.                                             |
| `fromUploadedFile(UploadedFile $file)`         | Set input file from an uploaded file.                                  |
| `fromDirectory(string $path, bool $recursive)` | Process all files in a directory. Use `allowExtensions()` to filter.   |
| `allowExtensions(array $extensions)`           | Filter directory files by extensions (e.g., `['mp4', 'mov']`).         |
| `eachFile(callable $callback)`                 | Set callback for customizing processing of each file in directory.     |
| `getCurrentFile()`                             | Get the current file being processed in directory mode.                |
| `save(string $path)`                           | Execute the command and save output to a local path.                   |
| `toDisk(string $disk, string $path)`           | Execute and save output to a Laravel disk (supports direct S3 upload). |
| `getCommand()`                                 | Get the generated FFmpeg command string without executing.             |
| `dryRun()`                                     | Alias for `getCommand()`.                                              |
| `ddCommand()`                                  | Dump the generated FFmpeg command and exit (`dd()`).                   |
| `onProgress(callable $callback)`               | Set a callback for progress updates.                                   |
| `onError(callable $callback)`                  | Set a callback for error handling.                                     |
| `broadcastProgress(string $channel)`           | Broadcast progress updates to a channel.                               |
| `addInputOption(string $key, mixed $value)`    | Add a raw input option (e.g., `-ss`).                                  |
| `addOutputOption(string $key, mixed $value)`   | Add a raw output option (e.g., `-c:v`).                                |
| `addOption(string $key, mixed $value)`         | Alias for `addOutputOption`.                                           |

## Video Options (`HasVideoOptions`)

| Method                                  | Description                                               |
| --------------------------------------- | --------------------------------------------------------- |
| `videoCodec(?string $codec)`            | Set the video codec (e.g., `libx264`).                    |
| `resolution(?int $width, ?int $height)` | Set the output resolution (e.g., `1920, 1080`).           |
| `aspectRatio(?string $ratio)`           | Set the aspect ratio (e.g., `16:9`).                      |
| `frameRate(?int $fps)`                  | Set the frame rate (e.g., `30`).                          |
| `videoBitrate(?string $bitrate)`        | Set the video bitrate (e.g., `5000k`).                    |
| `quality(?int $crf)`                    | Set the CRF quality value (lower is better).              |
| `encodingPreset(?string $preset)`       | Set the encoding preset (e.g., `fast`, `medium`, `slow`). |
| `pixelFormat(?string $format)`          | Set the pixel format (e.g., `yuv420p`).                   |

## Audio Options (`HasAudioOptions`)

| Method                           | Description                                   |
| -------------------------------- | --------------------------------------------- |
| `audioCodec(?string $codec)`     | Set the audio codec (e.g., `aac`).            |
| `audioBitrate(?string $bitrate)` | Set the audio bitrate (e.g., `128k`).         |
| `audioChannels(?int $channels)`  | Set the number of audio channels (e.g., `2`). |
| `audioSampleRate(?int $rate)`    | Set the audio sample rate (e.g., `44100`).    |
| `audioQuality(?int $quality)`    | Set the audio quality level.                  |
| `removeAudio()`                  | Remove the audio stream from the output.      |

## Audio Waveforms (`HasAudioPeaks`)

| Method                                                                                                                                                                                              | Description                                                                                                                                                                                                                                                                                                                                                                                      |
| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `withPeaks(int $samplesPerPixel = 512, ?array $normalizeRange = null, bool $only = false, string $format = 'simple', string\|callable\|null $peaksFilename = null, bool $useProcessedFile = false)` | Enable audio waveform generation. Set `$samplesPerPixel` for resolution, `$normalizeRange` for normalization (e.g., `[0, 1]` for wavesurfer.js), `$only` to skip transcoding, `$format` for output format ('simple' or 'full'), `$peaksFilename` for custom filename, and `$useProcessedFile` to generate peaks from processed output instead of original input (useful after trimming/filters). |
| `getPeaksConfig()`                                                                                                                                                                                  | Get the current peaks configuration array.                                                                                                                                                                                                                                                                                                                                                       |

### Waveform Generation Examples

```php
// Generate peaks with transcoding (default behavior)
FFmpeg::fromPath('audio.mp3')
    ->audioCodec('aac')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->save('output.m4a');
// Saves: output.m4a + output-peaks.json

// Peaks-only mode (no transcoding, lightweight)
FFmpeg::fromPath('audio.mp3')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1], only: true)
    ->save('waveform.json');

// Custom peaks filename
FFmpeg::fromPath('audio.mp3')
    ->audioCodec('aac')
    ->withPeaks(peaksFilename: 'custom-waveform.json')
    ->save('output.m4a');

// Full format with metadata
FFmpeg::fromPath('audio.mp3')
    ->withPeaks(format: 'full', normalizeRange: [0, 1])
    ->save('output.m4a');
// output-peaks.json contains: {version, channels, sample_rate, data, ...}

// Dynamic filename via callback
FFmpeg::fromPath('audio.mp3')
    ->withPeaks(peaksFilename: fn($output) => str_replace('.m4a', '.wave.json', $output))
    ->save('processed/audio.m4a');
// Saves: processed/audio.wave.json

// Direct S3 upload with peaks
FFmpeg::fromDisk('s3', 'uploads/audio.mp3')
    ->audioCodec('aac')
    ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
    ->toDisk('s3', 'processed/audio.m4a');
// Uses temporaryUploadUrl() for direct FFmpeg → S3 upload
// Peaks saved to: processed/audio-peaks.json
```

**Parameters:**

-   `$samplesPerPixel`: Higher values = less detail, smaller file (256 = high detail, 2048 = low detail)
-   `$normalizeRange`: `null` (raw PCM), `[0, 1]` (wavesurfer.js), `[-1, 1]` (signed), or custom range
-   `$only`: Skip transcoding, only generate peaks (lightweight mode)
-   `$format`: `'simple'` (array only) or `'full'` (with metadata)
-   `$peaksFilename`: Custom filename (string) or callback receiving output path

**See also:** [Audio Waveforms Guide](audio-waveforms.md)

## Subtitle Options (`HasSubtitleOptions`)

| Method                                        | Description                                        |
| --------------------------------------------- | -------------------------------------------------- |
| `subtitleCodec(string $codec)`                | Set the subtitle codec (e.g., `mov_text`, `copy`). |
| `burnSubtitles(string $path, array $options)` | Burn subtitles into the video.                     |
| `addSubtitle(string $path)`                   | Add a subtitle file as an input.                   |
| `extractSubtitles(?int $streamIndex)`         | Extract subtitles from the input.                  |

## Format Options (`HasFormatOptions`)

| Method                         | Description                                                         |
| ------------------------------ | ------------------------------------------------------------------- |
| `outputFormat(string $format)` | Set the output format (e.g., `mp4`, `hls`).                         |
| `hls(array $options)`          | Configure HLS output options.                                       |
| `dash(array $options)`         | Configure DASH output options.                                      |
| `gif(array $options)`          | Configure GIF output options.                                       |
| `toGif(array $options)`        | Convert to GIF (alias for `gif()`).                                 |
| `toMp3(int $bitrate)`          | Convert to MP3 audio (default 192k bitrate).                        |
| `toMp4(array $options)`        | Convert to MP4 video (H.264 + AAC).                                 |
| `toWebm(array $options)`       | Convert to WebM video (VP9 + Opus).                                 |
| `toAvi(array $options)`        | Convert to AVI video (MPEG4 + MP3).                                 |
| `toMov(array $options)`        | Convert to MOV/QuickTime video (H.264 + AAC).                       |
| `toFlv(array $options)`        | Convert to FLV/Flash video.                                         |
| `toMkv(array $options)`        | Convert to MKV/Matroska video (H.264 + AAC).                        |
| `toWav(int $sampleRate)`       | Convert to WAV audio (default 44100Hz).                             |
| `toAac(int $bitrate)`          | Convert to AAC audio (default 192k bitrate).                        |
| `toOgg(int $quality)`          | Convert to OGG Vorbis audio (quality 0-10, default 5).              |
| `toM4a(int $bitrate)`          | Convert to M4A audio - AAC in MP4 container (default 192k bitrate). |

## Filters & Effects (`HasFilters`)

| Method                                                      | Description                                  |
| ----------------------------------------------------------- | -------------------------------------------- |
| `addFilter(string $filter)`                                 | Add a custom filter string.                  |
| `crop(int $width, int $height, int $x, int $y)`             | Crop the video.                              |
| `scale(int $width, int $height, bool $maintainAspectRatio)` | Scale/resize the video.                      |
| `resize(int $width, int $height)`                           | Alias for `scale`.                           |
| `rotate(int $degrees)`                                      | Rotate the video (90, 180, 270).             |
| `flip(string $direction)`                                   | Flip the video (`horizontal` or `vertical`). |
| `fade(string $type, int $duration)`                         | Add fade effect (`in` or `out`).             |
| `fadeIn(int $duration)`                                     | Fade in from black.                          |
| `fadeOut(int $duration)`                                    | Fade out to black.                           |
| `thumbnail(string $outputPath, string $time)`               | Extract a single thumbnail.                  |
| `thumbnails(string $directory, int $count)`                 | Extract multiple thumbnails.                 |
| `blur(int $strength)`                                       | Apply blur effect.                           |
| `sharpen(int $strength)`                                    | Apply sharpen effect.                        |
| `grayscale()`                                               | Convert video to grayscale.                  |
| `sepia()`                                                   | Apply sepia tone effect.                     |
| `speed(float $multiplier)`                                  | Change playback speed (e.g., `0.5`, `2.0`).  |
| `reverse()`                                                 | Reverse video playback.                      |

## Time Options (`HasTimeOptions`)

| Method                       | Description                           |
| ---------------------------- | ------------------------------------- |
| `duration(string $duration)` | Set the output duration.              |
| `seek(string $time)`         | Seek to a specific time in the input. |
| `startFrom(string $time)`    | Alias for `seek`.                     |
| `stopAt(string $time)`       | Stop processing at a specific time.   |

## Clipping (`HasClipping`)

| Method                                            | Description                                                                                         |
| ------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `clip(string $start, string $end)`                | Extract a single clip between start and end times.                                                  |
| `clips(array $clips)`                             | Define multiple clips to extract with format: `[['start' => '00:00:10', 'end' => '00:00:20'], ...]` |
| `batchClips(array $clips, string $outputPattern)` | Extract multiple clips with custom output pattern using `{n}` placeholder.                          |

## Text Overlay (`HasTextOverlay`)

| Method                                                  | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `withText(string\|callable $text, array $options = [])` | Add text overlay to video. Can be called multiple times to add up to 50 overlays. Overlays are rendered in order (first = bottom, last = top). Options: `position` (string or `['x' => int, 'y' => int]`), `font_size` (default: 24), `font_color` (default: 'white'), `background_color` (default: 'black@0.5'), `border_width` (default: 0), `border_color` (default: 'black'), `padding` (default: 10), `font_file` (optional), `duration` (null = entire video), `start_time` (default: 0). Throws `RuntimeException` if more than 50 overlays are added. |
| `clearTextOverlays()`                                   | Remove all text overlays and reset to empty state.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| `removeTextOverlay(int $index)`                         | Remove a specific text overlay by its zero-based index. Array is automatically re-indexed after removal.                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| `getTextOverlays()`                                     | Get array of all configured text overlays with their options.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| `getTextOverlayCount()`                                 | Get the total number of configured text overlays (returns int).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |

### Text Overlay Examples

```php
// Single overlay
FFmpeg::fromPath('video.mp4')
    ->withText('Hello World', ['position' => 'bottom-center'])
    ->save('output.mp4');

// Multiple overlays
FFmpeg::fromPath('video.mp4')
    ->withText('Title', ['position' => 'top-center', 'font_size' => 36])
    ->withText('Subtitle', ['position' => 'center', 'font_size' => 24])
    ->withText('© 2024', ['position' => 'bottom-right', 'font_size' => 14])
    ->save('output.mp4');

// Managing overlays
$builder = FFmpeg::fromPath('video.mp4')
    ->withText('Text 1')
    ->withText('Text 2')
    ->withText('Text 3');

$count = $builder->getTextOverlayCount(); // 3
$builder->removeTextOverlay(1); // Remove 'Text 2'
$builder->clearTextOverlays(); // Remove all
```

**See also:** [Text Overlay Guide](text-overlay.md)

## Video Composition (`HasVideoComposition`)

| Method                                                                                       | Description                                                                                         |
| -------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `withIntro(string $introPath)`                                                               | Add an intro video to be prepended.                                                                 |
| `withOutro(string $outroPath)`                                                               | Add an outro video to be appended.                                                                  |
| `withWatermark(string $watermarkPath, string $position)`                                     | Add a watermark image. Positions: `top-left`, `top-right`, `bottom-left`, `bottom-right`, `center`. |
| `overlay(array $options)`                                                                    | Overlay video or image (Picture-in-Picture). Options: `x`, `y`, `width`, `height`.                  |
| `concat(array $inputs)`                                                                      | Concatenate multiple videos. Accepts array of input file paths.                                     |
| `addIntroOutro(string $videoPath, string $outputPath, ?string $intro, ?string $outro)`       | Manually add intro/outro to a specific video.                                                       |
| `addWatermark(string $videoPath, string $outputPath, ?string $watermark, ?string $position)` | Manually add watermark to a specific video.                                                         |

## Metadata (`HasMetadata`)

| Method                     | Description                         |
| -------------------------- | ----------------------------------- |
| `addMetadata(array $data)` | Set multiple metadata fields.       |
| `title(string $title)`     | Set the video title.                |
| `artist(string $artist)`   | Set the artist name.                |
| `comment(string $comment)` | Set a comment.                      |
| `album(string $album)`     | Set the album name.                 |
| `year(int $year)`          | Set the year.                       |
| `getMetadata()`            | Get metadata from the input file.   |
| `getTitle()`               | Get the title from the input file.  |
| `getArtist()`              | Get the artist from the input file. |
| `getDuration()`            | Get the duration of the input file. |
| `getFormat()`              | Get the format of the input file.   |

## Compatibility (`HasCompatibilityOptions`)

| Method                                        | Description                                      |
| --------------------------------------------- | ------------------------------------------------ |
| `webOptimized()`                              | Optimize for web playback (H.264 Baseline, AAC). |
| `mobileOptimized()`                           | Optimize for mobile devices (H.264 Main).        |
| `universalCompatibility()`                    | Maximize compatibility across all devices.       |
| `iosOptimized()`                              | Optimize for iOS devices (H.264 High).           |
| `androidOptimized()`                          | Optimize for Android devices (H.264 Main).       |
| `fastStart()`                                 | Enable fast start (progressive download).        |
| `h264Profile(string $profile, string $level)` | Set specific H.264 profile and level.            |

## Advanced Options (`HasAdvancedOptions`)

| Method                       | Description                         |
| ---------------------------- | ----------------------------------- |
| `threads(?int $count)`       | Set the number of threads to use.   |
| `overwrite(bool $overwrite)` | Overwrite output file if it exists. |
| `priority(int $priority)`    | Set process priority (nice value).  |
| `timeout(int $seconds)`      | Set process timeout.                |
| `validate()`                 | Validate options before execution.  |

## Helper Methods (`HasHelperMethods`)

| Method                                        | Description                                     |
| --------------------------------------------- | ----------------------------------------------- |
| `replaceAudio()`                              | Replace audio track with one from second input. |
| `extractAudio()`                              | Extract audio track (remove video).             |
| `fromImages(string $pattern, array $options)` | Create video from image sequence.               |
| `waveform(array $options)`                    | Generate a waveform image from audio.           |
| `preset(string\|array $preset)`               | Apply a named or custom preset.                 |

## HLS Support (`HasHlsSupport`)

| Method           | Description                                  |
| ---------------- | -------------------------------------------- |
| `exportForHLS()` | Start an HLS export (returns `HlsExporter`). |

## Queue Support (`HasQueueSupport`)

| Method                             | Description                   |
| ---------------------------------- | ----------------------------- |
| `queue(string $outputPath)`        | Dispatch processing to queue. |
| `onQueue(string $queue)`           | Set the queue name.           |
| `onConnection(string $connection)` | Set the queue connection.     |
| `delay($delay)`                    | Set the job delay.            |
