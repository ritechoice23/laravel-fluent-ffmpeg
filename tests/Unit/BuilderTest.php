<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can create a builder instance', function () {
    $builder = new FFmpegBuilder;

    expect($builder)->toBeInstanceOf(FFmpegBuilder::class);
});

it('can set input from path', function () {
    $builder = new FFmpegBuilder;
    $result = $builder->fromPath('video.mp4');

    expect($result)->toBe($builder)
        ->and($builder->getInputs())->toBe(['video.mp4']);
});

it('can set multiple inputs', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPaths(['video1.mp4', 'video2.mp4']);

    expect($builder->getInputs())->toBe(['video1.mp4', 'video2.mp4']);
});

it('can add additional input', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video1.mp4')
        ->addInput('video2.mp4');

    expect($builder->getInputs())->toBe(['video1.mp4', 'video2.mp4']);
});

it('can set input from array', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath(['video1.mp4', 'video2.mp4']);

    expect($builder->getInputs())->toBe(['video1.mp4', 'video2.mp4']);
});

it('can add input options', function () {
    $builder = new FFmpegBuilder;
    $builder->addInputOption('f', 'mp4')
        ->addInputOption('r', '30');

    expect($builder->getInputOptions())->toBe([
        'f' => 'mp4',
        'r' => '30',
    ]);
});

it('can add output options', function () {
    $builder = new FFmpegBuilder;
    $builder->addOutputOption('c:v', 'libx264')
        ->addOutputOption('c:a', 'aac');

    expect($builder->getOutputOptions())->toBe([
        'c:v' => 'libx264',
        'c:a' => 'aac',
    ]);
});

it('can set progress callback', function () {
    $builder = new FFmpegBuilder;
    $callback = fn ($progress) => null;

    $result = $builder->onProgress($callback);

    expect($result)->toBe($builder);
});

it('can set error callback', function () {
    $builder = new FFmpegBuilder;
    $callback = fn ($error) => null;

    $result = $builder->onError($callback);

    expect($result)->toBe($builder);
});

it('supports method chaining', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->fromPath('video.mp4')
        ->addInputOption('f', 'mp4')
        ->addOutputOption('c:v', 'libx264')
        ->onProgress(fn ($p) => null);

    expect($result)->toBe($builder);
});
