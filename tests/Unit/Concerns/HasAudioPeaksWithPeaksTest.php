<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('sets peaks config with default normalization', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks();

    $config = $builder->getPeaksConfig();

    expect($config)->toBeArray()
        ->and($config['samples_per_pixel'])->toBe(512)
        ->and($config['normalize_range'])->toBeNull();
});

it('sets peaks config with custom samples per pixel', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(samplesPerPixel: 1024);

    $config = $builder->getPeaksConfig();

    expect($config['samples_per_pixel'])->toBe(1024);
});

it('sets peaks config with normalization range', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(normalizeRange: [0, 1]);

    $config = $builder->getPeaksConfig();

    expect($config['normalize_range'])->toBe([0, 1]);
});

it('accepts different normalization ranges', function () {
    $builder = new FFmpegBuilder;

    $builder->withPeaks(normalizeRange: [-1, 1]);
    expect($builder->getPeaksConfig()['normalize_range'])->toBe([-1, 1]);

    $builder->withPeaks(normalizeRange: [0, 255]);
    expect($builder->getPeaksConfig()['normalize_range'])->toBe([0, 255]);
});

it('throws exception for invalid normalization range', function () {
    $builder = new FFmpegBuilder;

    expect(fn () => $builder->withPeaks(normalizeRange: [0]))
        ->toThrow(InvalidArgumentException::class, 'normalizeRange must be an array with exactly 2 values');
});

it('throws exception for normalization range with more than 2 values', function () {
    $builder = new FFmpegBuilder;

    expect(fn () => $builder->withPeaks(normalizeRange: [0, 1, 2]))
        ->toThrow(InvalidArgumentException::class, 'normalizeRange must be an array with exactly 2 values');
});

it('returns self for method chaining', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->withPeaks();

    expect($result)->toBe($builder);
});

it('can be chained with other methods', function () {
    $builder = new FFmpegBuilder;

    $result = $builder->fromPath('input.mp3')
        ->audioCodec('aac')
        ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1])
        ->audioBitrate('128k');

    expect($result)->toBe($builder)
        ->and($builder->getPeaksConfig())->toBeArray();
});

it('returns null when peaks not configured', function () {
    $builder = new FFmpegBuilder;

    expect($builder->getPeaksConfig())->toBeNull();
});

it('returns config after withPeaks is called', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(samplesPerPixel: 256, normalizeRange: [0, 1]);

    $config = $builder->getPeaksConfig();

    expect($config)->toBeArray()
        ->and($config['samples_per_pixel'])->toBe(256)
        ->and($config['normalize_range'])->toBe([0, 1])
        ->and($config['only'])->toBeFalse()
        ->and($config['format'])->toBe('simple')
        ->and($config['peaks_filename'])->toBeNull();
});

it('includes all new parameters in config', function () {
    $builder = new FFmpegBuilder;
    $callback = fn ($output) => 'custom.json';

    $builder->withPeaks(
        samplesPerPixel: 1024,
        normalizeRange: [-1, 1],
        only: true,
        format: 'full',
        peaksFilename: $callback
    );

    $config = $builder->getPeaksConfig();

    expect($config)->toBeArray()
        ->and($config['samples_per_pixel'])->toBe(1024)
        ->and($config['normalize_range'])->toBe([-1, 1])
        ->and($config['only'])->toBeTrue()
        ->and($config['format'])->toBe('full')
        ->and($config['peaks_filename'])->toBe($callback);
});
