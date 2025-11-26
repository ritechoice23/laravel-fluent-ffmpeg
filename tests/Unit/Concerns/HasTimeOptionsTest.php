<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set duration', function () {
    $builder = new FFmpegBuilder;
    $builder->duration('00:00:10');

    expect($builder->getOutputOptions()['t'])->toBe('00:00:10');
});

it('can seek to position', function () {
    $builder = new FFmpegBuilder;
    $builder->seek('00:00:05');

    expect($builder->getInputOptions()['ss'])->toBe('00:00:05');
});

it('can use startFrom as alias for seek', function () {
    $builder = new FFmpegBuilder;
    $builder->startFrom('00:00:05');

    expect($builder->getInputOptions()['ss'])->toBe('00:00:05');
});

it('can set end time', function () {
    $builder = new FFmpegBuilder;
    $builder->stopAt('00:00:30');

    expect($builder->getOutputOptions()['to'])->toBe('00:00:30');
});

it('can extract clip', function () {
    $builder = new FFmpegBuilder;
    $builder->clip('00:00:05', '00:00:15');

    expect($builder->getInputOptions()['ss'])->toBe('00:00:05')
        ->and($builder->getOutputOptions()['to'])->toBe('00:00:15');
});

it('can chain time options', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->seek('00:00:05')
        ->duration('00:00:10');

    expect($result)->toBe($builder);
});
