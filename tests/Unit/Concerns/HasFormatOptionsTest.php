<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set format', function () {
    $builder = new FFmpegBuilder;
    $builder->outputFormat('mp4');

    expect($builder->getOutputOptions()['f'])->toBe('mp4');
});

it('can configure HLS output', function () {
    $builder = new FFmpegBuilder;
    $builder->hls(['segment_time' => 5]);

    expect($builder->getOutputOptions()['f'])->toBe('hls')
        ->and($builder->getOutputOptions()['hls_time'])->toBe(5);
});

it('can configure DASH output', function () {
    $builder = new FFmpegBuilder;
    $builder->dash(['segment_duration' => 8]);

    expect($builder->getOutputOptions()['f'])->toBe('dash')
        ->and($builder->getOutputOptions()['seg_duration'])->toBe(8);
});

it('can configure GIF output', function () {
    $builder = new FFmpegBuilder;
    $builder->gif(['fps' => 15, 'width' => 480]);

    expect($builder->getOutputOptions()['f'])->toBe('gif')
        ->and($builder->getOutputOptions()['vf'])->toContain('fps=15');
});

it('can use toGif helper', function () {
    $builder = new FFmpegBuilder;
    $builder->toGif();

    expect($builder->getOutputOptions()['f'])->toBe('gif');
});
