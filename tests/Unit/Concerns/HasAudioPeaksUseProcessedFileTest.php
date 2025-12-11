<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

test('it defaults to using original input file for peaks', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1]);

    $config = $builder->getPeaksConfig();

    expect($config)->toBeArray()
        ->and($config['use_processed_file'])->toBeFalse();
});

test('it can be configured to use processed file for peaks', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(
        samplesPerPixel: 512,
        normalizeRange: [0, 1],
        useProcessedFile: true
    );

    $config = $builder->getPeaksConfig();

    expect($config)->toBeArray()
        ->and($config['use_processed_file'])->toBeTrue();
});

test('it stores useProcessedFile setting in config', function () {
    $builder = new FFmpegBuilder;

    // Test false
    $builder->withPeaks(useProcessedFile: false);
    expect($builder->getPeaksConfig()['use_processed_file'])->toBeFalse();

    // Test true
    $builder->withPeaks(useProcessedFile: true);
    expect($builder->getPeaksConfig()['use_processed_file'])->toBeTrue();
});

test('useProcessedFile works with other parameters', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(
        samplesPerPixel: 1024,
        normalizeRange: [-1, 1],
        only: false,
        format: 'full',
        peaksFilename: 'custom.json',
        useProcessedFile: true
    );

    $config = $builder->getPeaksConfig();

    expect($config['samples_per_pixel'])->toBe(1024)
        ->and($config['normalize_range'])->toBe([-1, 1])
        ->and($config['only'])->toBeFalse()
        ->and($config['format'])->toBe('full')
        ->and($config['peaks_filename'])->toBe('custom.json')
        ->and($config['use_processed_file'])->toBeTrue();
});

test('it can chain with useProcessedFile parameter', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->fromPath('test.mp3')
        ->withPeaks(useProcessedFile: true)
        ->audioCodec('aac');

    expect($result)->toBeInstanceOf(FFmpegBuilder::class)
        ->and($builder->getPeaksConfig()['use_processed_file'])->toBeTrue();
});
