# API Reference

This document provides a comprehensive reference for all available methods in the `FFmpegBuilder` class and its traits.

## Core Methods (`FFmpegBuilder`)

| Method | Description |
|--------|-------------|
| `fromPath(string\|array $path)` | Set input file(s) from path. |
| `fromPaths(array $paths)` | Set multiple input files from paths. |
| `addInput(string $path)` | Add an additional input file. |
| `fromDisk(string $disk, string $path)` | Set input file from a Laravel disk. |
| `fromUrl(string $url)` | Set input file from a URL. |
| `fromUploadedFile(UploadedFile $file)` | Set input file from an uploaded file. |
| `save(string $path)` | Execute the command and save output to a local path. |
| `toDisk(string $disk, string $path)` | Execute and save output to a Laravel disk. |
| `getCommand()` | Get the generated FFmpeg command string without executing. |
| `dryRun()` | Alias for `getCommand()`. |
| `onProgress(callable $callback)` | Set a callback for progress updates. |
| `onError(callable $callback)` | Set a callback for error handling. |
| `broadcastProgress(string $channel)` | Broadcast progress updates to a channel. |
| `addInputOption(string $key, mixed $value)` | Add a raw input option (e.g., `-ss`). |
| `addOutputOption(string $key, mixed $value)` | Add a raw output option (e.g., `-c:v`). |
| `addOption(string $key, mixed $value)` | Alias for `addOutputOption`. |

## Video Options (`HasVideoOptions`)

| Method | Description |
|--------|-------------|
| `videoCodec(?string $codec)` | Set the video codec (e.g., `libx264`). |
| `resolution(?int $width, ?int $height)` | Set the output resolution (e.g., `1920, 1080`). |
| `aspectRatio(?string $ratio)` | Set the aspect ratio (e.g., `16:9`). |
| `frameRate(?int $fps)` | Set the frame rate (e.g., `30`). |
| `videoBitrate(?string $bitrate)` | Set the video bitrate (e.g., `5000k`). |
| `quality(?int $crf)` | Set the CRF quality value (lower is better). |
| `encodingPreset(?string $preset)` | Set the encoding preset (e.g., `fast`, `medium`, `slow`). |
| `pixelFormat(?string $format)` | Set the pixel format (e.g., `yuv420p`). |

## Audio Options (`HasAudioOptions`)

| Method | Description |
|--------|-------------|
| `audioCodec(?string $codec)` | Set the audio codec (e.g., `aac`). |
| `audioBitrate(?string $bitrate)` | Set the audio bitrate (e.g., `128k`). |
| `audioChannels(?int $channels)` | Set the number of audio channels (e.g., `2`). |
| `audioSampleRate(?int $rate)` | Set the audio sample rate (e.g., `44100`). |
| `audioQuality(?int $quality)` | Set the audio quality level. |
| `removeAudio()` | Remove the audio stream from the output. |

## Subtitle Options (`HasSubtitleOptions`)

| Method | Description |
|--------|-------------|
| `subtitleCodec(string $codec)` | Set the subtitle codec (e.g., `mov_text`, `copy`). |
| `burnSubtitles(string $path, array $options)` | Burn subtitles into the video. |
| `addSubtitle(string $path)` | Add a subtitle file as an input. |
| `extractSubtitles(?int $streamIndex)` | Extract subtitles from the input. |

## Format Options (`HasFormatOptions`)

| Method | Description |
|--------|-------------|
| `outputFormat(string $format)` | Set the output format (e.g., `mp4`, `hls`). |
| `hls(array $options)` | Configure HLS output options. |
| `dash(array $options)` | Configure DASH output options. |
| `gif(array $options)` | Configure GIF output options. |
| `toGif(array $options)` | Helper to convert to GIF. |

## Filters & Effects (`HasFilters`)

| Method | Description |
|--------|-------------|
| `addFilter(string $filter)` | Add a custom filter string. |
| `overlay(array $options)` | Overlay video (Picture-in-Picture). |
| `watermark(string $path, array $options)` | Add a watermark image. |
| `crop(int $width, int $height, int $x, int $y)` | Crop the video. |
| `scale(int $width, int $height, bool $maintainAspectRatio)` | Scale/resize the video. |
| `resize(int $width, int $height)` | Alias for `scale`. |
| `rotate(int $degrees)` | Rotate the video (90, 180, 270). |
| `flip(string $direction)` | Flip the video (`horizontal` or `vertical`). |
| `fade(string $type, int $duration)` | Add fade effect (`in` or `out`). |
| `fadeIn(int $duration)` | Fade in from black. |
| `fadeOut(int $duration)` | Fade out to black. |
| `thumbnail(string $outputPath, string $time)` | Extract a single thumbnail. |
| `thumbnails(string $directory, int $count)` | Extract multiple thumbnails. |
| `blur(int $strength)` | Apply blur effect. |
| `sharpen(int $strength)` | Apply sharpen effect. |
| `grayscale()` | Convert video to grayscale. |
| `sepia()` | Apply sepia tone effect. |
| `speed(float $multiplier)` | Change playback speed (e.g., `0.5`, `2.0`). |
| `reverse()` | Reverse video playback. |
| `concat(array $inputs)` | Concatenate multiple inputs. |

## Time Options (`HasTimeOptions`)

| Method | Description |
|--------|-------------|
| `duration(string $duration)` | Set the output duration. |
| `seek(string $time)` | Seek to a specific time in the input. |
| `startFrom(string $time)` | Alias for `seek`. |
| `stopAt(string $time)` | Stop processing at a specific time. |
| `clip(string $start, string $end)` | Extract a clip between two times. |

## Metadata (`HasMetadata`)

| Method | Description |
|--------|-------------|
| `addMetadata(array $data)` | Set multiple metadata fields. |
| `title(string $title)` | Set the video title. |
| `artist(string $artist)` | Set the artist name. |
| `comment(string $comment)` | Set a comment. |
| `album(string $album)` | Set the album name. |
| `year(int $year)` | Set the year. |
| `getMetadata()` | Get metadata from the input file. |
| `getTitle()` | Get the title from the input file. |
| `getArtist()` | Get the artist from the input file. |
| `getDuration()` | Get the duration of the input file. |
| `getFormat()` | Get the format of the input file. |

## Compatibility (`HasCompatibilityOptions`)

| Method | Description |
|--------|-------------|
| `webOptimized()` | Optimize for web playback (H.264 Baseline, AAC). |
| `mobileOptimized()` | Optimize for mobile devices (H.264 Main). |
| `universalCompatibility()` | Maximize compatibility across all devices. |
| `iosOptimized()` | Optimize for iOS devices (H.264 High). |
| `androidOptimized()` | Optimize for Android devices (H.264 Main). |
| `fastStart()` | Enable fast start (progressive download). |
| `h264Profile(string $profile, string $level)` | Set specific H.264 profile and level. |

## Advanced Options (`HasAdvancedOptions`)

| Method | Description |
|--------|-------------|
| `threads(?int $count)` | Set the number of threads to use. |
| `overwrite(bool $overwrite)` | Overwrite output file if it exists. |
| `priority(int $priority)` | Set process priority (nice value). |
| `timeout(int $seconds)` | Set process timeout. |
| `validate()` | Validate options before execution. |

## Helper Methods (`HasHelperMethods`)

| Method | Description |
|--------|-------------|
| `replaceAudio()` | Replace audio track with one from second input. |
| `extractAudio()` | Extract audio track (remove video). |
| `fromImages(string $pattern, array $options)` | Create video from image sequence. |
| `waveform(array $options)` | Generate a waveform image from audio. |
| `preset(string\|array $preset)` | Apply a named or custom preset. |

## HLS Support (`HasHlsSupport`)

| Method | Description |
|--------|-------------|
| `exportForHLS()` | Start an HLS export (returns `HlsExporter`). |

## Queue Support (`HasQueueSupport`)

| Method | Description |
|--------|-------------|
| `queue(string $outputPath)` | Dispatch processing to queue. |
| `onQueue(string $queue)` | Set the queue name. |
| `onConnection(string $connection)` | Set the queue connection. |
| `delay($delay)` | Set the job delay. |
