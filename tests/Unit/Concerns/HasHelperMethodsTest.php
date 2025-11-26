<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can replace audio', function () {
    $builder = new FFmpegBuilder;
    $builder->replaceAudio();

    $outputOptions = $builder->getOutputOptions();

    // Should map video from first input and audio from second
    expect($outputOptions['map'])->toBe(['0:v', '1:a']);
});

it('can extract audio', function () {
    $builder = new FFmpegBuilder;
    $builder->extractAudio();

    expect($builder->getOutputOptions())->toHaveKey('vn', true);
});
