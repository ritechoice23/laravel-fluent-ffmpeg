<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set video codec with default', function () {
    $builder = new FFmpegBuilder;
    $builder->videoCodec();

    expect($builder->getOutputOptions())->toHaveKey('c:v');
});

it('can set video codec with custom value', function () {
    $builder = new FFmpegBuilder;
    $builder->videoCodec('libx265');

    expect($builder->getOutputOptions()['c:v'])->toBe('libx265');
});

it('can set resolution', function () {
    $builder = new FFmpegBuilder;
    $builder->resolution(1920, 1080);

    expect($builder->getOutputOptions()['s'])->toBe('1920x1080');
});

it('can set frame rate with default', function () {
    $builder = new FFmpegBuilder;
    $builder->frameRate();

    expect($builder->getOutputOptions())->toHaveKey('r');
});

it('can set quality with default', function () {
    $builder = new FFmpegBuilder;
    $builder->quality();

    expect($builder->getOutputOptions())->toHaveKey('crf');
});

it('can set encoding preset with default', function () {
    $builder = new FFmpegBuilder;
    $builder->encodingPreset();

    expect($builder->getOutputOptions())->toHaveKey('preset');
});

it('can chain video options', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->videoCodec('libx264')
        ->resolution(1920, 1080)
        ->frameRate(30)
        ->quality(23);

    expect($result)->toBe($builder)
        ->and($builder->getOutputOptions())->toHaveKeys(['c:v', 's', 'r', 'crf']);
});
