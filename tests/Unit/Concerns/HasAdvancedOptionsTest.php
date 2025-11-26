<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set threads', function () {
    $builder = new FFmpegBuilder;
    $builder->threads(4);

    expect($builder->getOutputOptions()['threads'])->toBe(4);
});

it('can set overwrite', function () {
    $builder = new FFmpegBuilder;
    $builder->overwrite();

    expect($builder->getOutputOptions()['y'])->toBeTrue();
});

it('can validate inputs', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4');

    $result = $builder->validate();

    expect($result)->toBe($builder);
});

it('throws exception when validating without inputs', function () {
    $builder = new FFmpegBuilder;

    expect(fn () => $builder->validate())
        ->toThrow(\InvalidArgumentException::class, 'No input files specified');
});

it('can chain advanced options', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->threads(8)
        ->overwrite();

    expect($result)->toBe($builder);
});
