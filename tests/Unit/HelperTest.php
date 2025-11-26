<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can resolve ffmpeg builder using helper', function () {
    $builder = ffmpeg();

    expect($builder)->toBeInstanceOf(FFmpegBuilder::class);
});

it('can chain methods using helper', function () {
    $builder = ffmpeg()->fromPath('video.mp4');

    expect($builder)->toBeInstanceOf(FFmpegBuilder::class)
        ->and($builder->getInputs())->toContain('video.mp4');
});
