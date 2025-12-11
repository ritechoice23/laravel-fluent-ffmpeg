<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can generate peaks only mode with only parameter', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->withPeaks(only: true);

    $config = $builder->getPeaksConfig();

    expect($config['only'])->toBeTrue();
});

it('can set custom peaks format', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(format: 'full');

    $config = $builder->getPeaksConfig();

    expect($config['format'])->toBe('full');
});

it('defaults to simple format', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks();

    $config = $builder->getPeaksConfig();

    expect($config['format'])->toBe('simple');
});

it('can set custom peaks filename as string', function () {
    $builder = new FFmpegBuilder;
    $builder->withPeaks(peaksFilename: 'custom-peaks.json');

    $config = $builder->getPeaksConfig();

    expect($config['peaks_filename'])->toBe('custom-peaks.json');
});

it('can set custom peaks filename as callback', function () {
    $builder = new FFmpegBuilder;
    $callback = fn ($output) => str_replace('.m4a', '.waveform.json', $output);
    $builder->withPeaks(peaksFilename: $callback);

    $config = $builder->getPeaksConfig();

    expect($config['peaks_filename'])->toBe($callback);
});
