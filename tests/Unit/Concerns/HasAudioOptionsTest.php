<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set audio codec with default', function () {
    $builder = new FFmpegBuilder;
    $builder->audioCodec();

    expect($builder->getOutputOptions())->toHaveKey('c:a');
});

it('can set audio codec with custom value', function () {
    $builder = new FFmpegBuilder;
    $builder->audioCodec('mp3');

    expect($builder->getOutputOptions()['c:a'])->toBe('mp3');
});

it('can set audio bitrate with default', function () {
    $builder = new FFmpegBuilder;
    $builder->audioBitrate();

    expect($builder->getOutputOptions())->toHaveKey('b:a');
});

it('can set audio channels with default', function () {
    $builder = new FFmpegBuilder;
    $builder->audioChannels();

    expect($builder->getOutputOptions())->toHaveKey('ac');
});

it('can set audio sample rate with default', function () {
    $builder = new FFmpegBuilder;
    $builder->audioSampleRate();

    expect($builder->getOutputOptions())->toHaveKey('ar');
});

it('can remove audio', function () {
    $builder = new FFmpegBuilder;
    $builder->removeAudio();

    expect($builder->getOutputOptions()['an'])->toBeTrue();
});

it('can chain audio options', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4')
        ->audioCodec('aac')
        ->audioBitrate('128k')
        ->audioChannels(2);

    expect($builder->getOutputOptions())->toHaveKey('c:a', 'aac')
        ->and($builder->getOutputOptions())->toHaveKey('b:a', '128k')
        ->and($builder->getOutputOptions())->toHaveKey('ac', 2);
});
