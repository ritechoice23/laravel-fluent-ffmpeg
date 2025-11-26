<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FFmpeg Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the FFmpeg binary. If FFmpeg is in your system PATH,
    | you can just use 'ffmpeg'. Otherwise, provide the full path.
    |
    */
    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    /*
    |--------------------------------------------------------------------------
    | FFprobe Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the FFprobe binary. FFprobe is typically bundled with FFmpeg.
    | If it's in your system PATH, you can just use 'ffprobe'.
    |
    */
    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),

    /*
    |--------------------------------------------------------------------------
    | Execution Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time (in seconds) to wait for FFmpeg processes to complete.
    | Set to null for no timeout. Default is 1 hour (3600 seconds).
    |
    */
    'timeout' => env('FFMPEG_TIMEOUT', 3600),

    /*
    |--------------------------------------------------------------------------
    | Temporary Disk
    |--------------------------------------------------------------------------
    |
    | The Laravel disk to use for temporary files during processing.
    | This should be a local disk for best performance.
    |
    */
    'temp_disk' => env('FFMPEG_TEMP_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | The Laravel log channel to use for FFmpeg operations.
    | Set to null to disable logging.
    |
    */
    'log_channel' => env('FFMPEG_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Default Options
    |--------------------------------------------------------------------------
    |
    | Default values used when option methods are called without parameters.
    | These provide sensible defaults for common video/audio processing tasks.
    |
    */
    'defaults' => [
        'video' => [
            'codec' => 'libx264',
            'preset' => 'medium',
            'crf' => 23,
            'pixel_format' => 'yuv420p',
            'frame_rate' => 30,
            'aspect_ratio' => '16:9',
        ],
        'audio' => [
            'codec' => 'aac',
            'bitrate' => '128k',
            'channels' => 2,
            'sample_rate' => 44100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Predefined configurations for common output formats.
    | You can reference these by name using the preset() method.
    |
    */
    'presets' => [
        '1080p' => [
            'resolution' => [1920, 1080],
            'video_bitrate' => '5000k',
            'audio_bitrate' => '192k',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ],
        '720p' => [
            'resolution' => [1280, 720],
            'video_bitrate' => '2500k',
            'audio_bitrate' => '128k',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ],
        '480p' => [
            'resolution' => [854, 480],
            'video_bitrate' => '1000k',
            'audio_bitrate' => '96k',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ],
        '360p' => [
            'resolution' => [640, 360],
            'video_bitrate' => '500k',
            'audio_bitrate' => '64k',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ],

        // Web-optimized preset (maximum compatibility)
        'web' => [
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
            'profile' => 'baseline',
            'level' => '3.0',
            'pixel_format' => 'yuv420p',
            'movflags' => '+faststart',
        ],

        // Universal compatibility preset
        'universal' => [
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
            'profile' => 'baseline',
            'level' => '3.0',
            'pixel_format' => 'yuv420p',
            'audio_channels' => 2,
            'audio_sample_rate' => 44100,
            'movflags' => '+faststart',
        ],
    ],
];
