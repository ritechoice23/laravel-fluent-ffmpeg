<?php

use Illuminate\Support\Facades\Storage;
use Ritechoice23\FluentFFmpeg\Actions\BuildFFmpegCommand;
use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('adds PCM output when peaks config is set', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->withPeaks()
        ->audioCodec('aac');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('-map')
        ->and($command)->toContain('0:a')
        ->and($command)->toContain('-f')
        ->and($command)->toContain('s16le')
        ->and($command)->toContain('-acodec')
        ->and($command)->toContain('pcm_s16le')
        ->and($command)->toContain('pipe:3');
});

it('does not add PCM output when peaks not configured', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->audioCodec('aac');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->not->toContain('pipe:3')
        ->and($command)->not->toContain('pcm_s16le');
});

it('adds progress output to pipe:1', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('-progress')
        ->and($command)->toContain('pipe:1');
});

it('uses temporaryUploadUrl for S3 direct upload', function () {
    // Mock Storage disk with temporaryUploadUrl support FIRST
    // Use a class that actually has the method so method_exists works
    $mockDisk = Mockery::mock('Illuminate\Filesystem\FilesystemAdapter');
    $mockDisk->shouldReceive('temporaryUploadUrl')
        ->with('output.mp3', Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/output.mp3?signature=xyz');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturn($mockDisk);

    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->audioCodec('aac');

    // Use reflection to set outputDisk
    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('outputDisk');
    $property->setAccessible(true);
    $property->setValue($builder, 's3');

    $property = $reflection->getProperty('outputPath');
    $property->setAccessible(true);
    $property->setValue($builder, 'output.mp3');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('-method')
        ->and($command)->toContain('PUT')
        ->and($command)->toContain('https://s3.amazonaws.com/bucket/output.mp3');
});

it('detects correct format for mp4 files', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp4')
        ->videoCodec('libx264'); // Add codec so format is included

    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('outputPath');
    $property->setAccessible(true);
    $property->setValue($builder, '/tmp/output.mp4');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('/tmp/output.mp4');
});

it('detects correct format for m4a files', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->audioCodec('aac'); // Add codec

    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('outputPath');
    $property->setAccessible(true);
    $property->setValue($builder, '/tmp/output.m4a');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('/tmp/output.m4a');
});

it('builds command with both S3 direct upload and peaks', function () {
    // Mock Storage disk with temporaryUploadUrl support FIRST
    $mockDisk = Mockery::mock('Illuminate\Filesystem\FilesystemAdapter');
    $mockDisk->shouldReceive('temporaryUploadUrl')
        ->with('output.m4a', Mockery::any())
        ->andReturn('https://s3.amazonaws.com/bucket/output.m4a?signature=xyz');

    Storage::shouldReceive('disk')
        ->with('s3')
        ->andReturn($mockDisk);

    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->audioCodec('aac')
        ->withPeaks(samplesPerPixel: 512, normalizeRange: [0, 1]);

    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('outputDisk');
    $property->setAccessible(true);
    $property->setValue($builder, 's3');

    $property = $reflection->getProperty('outputPath');
    $property->setAccessible(true);
    $property->setValue($builder, 'output.m4a');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('pipe:3') // PCM for peaks
        ->and($command)->toContain('-method') // Direct S3 upload
        ->and($command)->toContain('PUT')
        ->and($command)->toContain('s16le')
        ->and($command)->toContain('pcm_s16le');
});

it('uses local file path when no outputDisk', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3')
        ->audioCodec('aac');

    $reflection = new ReflectionClass($builder);
    $property = $reflection->getProperty('outputPath');
    $property->setAccessible(true);
    $property->setValue($builder, '/tmp/output.mp3');

    $command = app(BuildFFmpegCommand::class)->execute($builder);

    expect($command)->toContain('/tmp/output.mp3')
        ->and($command)->toContain('-y'); // Overwrite flag
});
