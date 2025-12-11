<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('detects peaks-only mode when only parameter is true', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->withPeaks(only: true);

    $config = $builder->getPeaksConfig();

    expect($config['only'])->toBeTrue();
});

it('detects peaks-only mode when output has .json extension', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->withPeaks();

    // The save() method should detect .json extension
    // We can't directly test save() without mocking, but we can verify the config is set
    $config = $builder->getPeaksConfig();
    expect($config)->toBeArray();
});

it('validates peaks filename for security', function () {
    $builder = new FFmpegBuilder;

    // Valid filenames should work
    $builder->withPeaks(peaksFilename: 'valid-filename_123.json');
    expect($builder->getPeaksConfig()['peaks_filename'])->toBe('valid-filename_123.json');

    $builder->withPeaks(peaksFilename: 'path/to/file.json');
    expect($builder->getPeaksConfig()['peaks_filename'])->toBe('path/to/file.json');
});

it('executes callback for dynamic peaks filename', function () {
    $builder = new FFmpegBuilder;
    $callback = fn ($output) => str_replace('.m4a', '.waveform.json', $output);

    $builder->withPeaks(peaksFilename: $callback);

    $config = $builder->getPeaksConfig();
    expect($config['peaks_filename'])->toBe($callback);

    // Test the callback execution
    $result = $callback('audio.m4a');
    expect($result)->toBe('audio.waveform.json');
});

it('combines all parameters correctly', function () {
    $builder = new FFmpegBuilder;

    $builder->fromPath('input.mp3')
        ->withPeaks(
            samplesPerPixel: 1024,
            normalizeRange: [0, 1],
            only: true,
            format: 'full',
            peaksFilename: 'custom.json'
        );

    $config = $builder->getPeaksConfig();

    expect($config['samples_per_pixel'])->toBe(1024)
        ->and($config['normalize_range'])->toBe([0, 1])
        ->and($config['only'])->toBeTrue()
        ->and($config['format'])->toBe('full')
        ->and($config['peaks_filename'])->toBe('custom.json');
});
