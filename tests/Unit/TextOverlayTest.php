<?php

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

test('can add text overlay with default options', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World');

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can add text overlay with custom position', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World', [
            'position' => 'top-left',
        ]);

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can add text overlay with custom colors', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World', [
            'font_color' => 'red',
            'background_color' => 'blue@0.8',
            'border_color' => 'yellow',
        ]);

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can add text overlay with custom font size and border', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World', [
            'font_size' => 36,
            'border_width' => 2,
            'padding' => 20,
        ]);

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can add text overlay with timing', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World', [
            'start_time' => 5,
            'duration' => 10,
        ]);

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can add text overlay with custom coordinates', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World', [
            'position' => ['x' => 100, 'y' => 200],
        ]);

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can add text overlay with callback', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText(function ($file) {
            return 'Processing: ' . basename($file);
        });

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can combine text overlay with watermark', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withWatermark('logo.png', 'top-right')
        ->withText('Copyright 2024', [
            'position' => 'bottom-right',
            'font_size' => 16,
        ]);

    expect($builder)->toBeInstanceOf(\Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder::class);
});

test('can use text overlay with directory processing', function () {
    $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ffmpeg_test_' . uniqid();
    mkdir($testDir);

    touch($testDir . DIRECTORY_SEPARATOR . 'video1.mp4');
    touch($testDir . DIRECTORY_SEPARATOR . 'video2.mp4');

    $builder = FFmpeg::fromDirectory($testDir)
        ->withText(function ($file) {
            return basename($file);
        }, [
            'position' => 'top-center',
        ]);

    expect($builder->getInputs())->toHaveCount(2);

    // Cleanup
    array_map('unlink', glob($testDir . DIRECTORY_SEPARATOR . '*'));
    rmdir($testDir);
});

test('text position presets resolve correctly', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Test');

    $reflection = new \ReflectionClass($builder);
    $method = $reflection->getMethod('resolveTextPosition');
    $method->setAccessible(true);

    $positions = [
        'top-left',
        'top-center',
        'top-right',
        'center',
        'bottom-left',
        'bottom-center',
        'bottom-right',
    ];

    foreach ($positions as $position) {
        $result = $method->invoke($builder, $position, 10);
        expect($result)->toBeArray();
        expect($result)->toHaveKeys(['x', 'y']);
    }
});

test('custom position coordinates are used', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Test');

    $reflection = new \ReflectionClass($builder);
    $method = $reflection->getMethod('resolveTextPosition');
    $method->setAccessible(true);

    $result = $method->invoke($builder, ['x' => 250, 'y' => 350], 10);

    expect($result)->toBe(['x' => 250, 'y' => 350]);
});

test('drawtext escaping works correctly', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Test');

    $reflection = new \ReflectionClass($builder);
    $method = $reflection->getMethod('escapeDrawText');
    $method->setAccessible(true);

    $text = "Hello: [World] 'Test' \\ , ;";
    $escaped = $method->invoke($builder, $text);

    expect($escaped)->toContain('\\:');
    expect($escaped)->toContain('\\[');
    expect($escaped)->toContain('\\]');
    expect($escaped)->toContain('\\\\');
});
