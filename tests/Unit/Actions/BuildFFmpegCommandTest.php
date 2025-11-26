<?php

use Ritechoice23\FluentFFmpeg\Actions\BuildFFmpegCommand;
use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

beforeEach(function () {
    config(['fluent-ffmpeg.ffmpeg_path' => '/usr/bin/ffmpeg']);
});

it('can build basic command with single input', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('/usr/bin/ffmpeg')
        ->and($command)->toContain('-i')
        ->and($command)->toContain('video.mp4');
});

it('can build command with multiple inputs', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPaths(['video1.mp4', 'video2.mp4']);

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('video1.mp4')
        ->and($command)->toContain('video2.mp4');
});

it('can build command with input options', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4')
        ->addInputOption('f', 'mp4')
        ->addInputOption('r', '30');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('-f')
        ->and($command)->toContain('mp4')
        ->and($command)->toContain('-r')
        ->and($command)->toContain('30');
});

it('can build command with output options', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4')
        ->addOutputOption('c:v', 'libx264')
        ->addOutputOption('c:a', 'aac');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('-c:v')
        ->and($command)->toContain('libx264')
        ->and($command)->toContain('-c:a')
        ->and($command)->toContain('aac');
});

it('escapes shell arguments properly', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video with spaces.mp4');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    // Check that the filename is escaped (either single or double quotes depending on OS)
    expect($command)->toMatch('/(\'|")video with spaces\.mp4(\'|")/');
});

it('includes overwrite flag', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4');

    // Set output path via reflection to test command building
    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('outputPath');
    $property->setAccessible(true);
    $property->setValue($builder, 'output.mp4');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('-y');
});
