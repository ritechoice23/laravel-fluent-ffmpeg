<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set web optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->webOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'baseline')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.0')
        ->and($builder->getOutputOptions())->toHaveKey('pix_fmt', 'yuv420p')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set mobile optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->mobileOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'main')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.1')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set universal compatibility settings', function () {
    $builder = new FFmpegBuilder;
    $builder->universalCompatibility();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'baseline')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.0')
        ->and($builder->getOutputOptions())->toHaveKey('f', 'mp4')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set ios optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->iosOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'high')
        ->and($builder->getOutputOptions())->toHaveKey('level', '4.0')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set android optimized settings', function () {
    $builder = new FFmpegBuilder;
    $builder->androidOptimized();

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'main')
        ->and($builder->getOutputOptions())->toHaveKey('level', '3.1')
        ->and($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set fast start', function () {
    $builder = new FFmpegBuilder;
    $builder->fastStart();

    expect($builder->getOutputOptions())->toHaveKey('movflags', '+faststart');
});

it('can set h264 profile and level', function () {
    $builder = new FFmpegBuilder;
    $builder->h264Profile('high', '4.2');

    expect($builder->getOutputOptions())->toHaveKey('profile:v', 'high')
        ->and($builder->getOutputOptions())->toHaveKey('level', '4.2');
});
