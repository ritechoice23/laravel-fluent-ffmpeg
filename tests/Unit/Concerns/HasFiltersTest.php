<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can add custom filter', function () {
    $builder = new FFmpegBuilder;
    $builder->addFilter('custom_filter');

    expect($builder->getFilters())->toContain('custom_filter');
});

it('can crop video', function () {
    $builder = new FFmpegBuilder;
    $builder->crop(1920, 1080, 0, 0);

    expect($builder->getFilters())->toContain('crop=1920:1080:0:0');
});

it('can scale video', function () {
    $builder = new FFmpegBuilder;
    $builder->scale(1280, 720);

    expect($builder->getFilters()[0])->toContain('scale=1280:720');
});

it('can rotate video', function () {
    $builder = new FFmpegBuilder;
    $builder->rotate(90);

    expect($builder->getFilters())->toContain('transpose=1');
});

it('can flip video horizontally', function () {
    $builder = new FFmpegBuilder;
    $builder->flip('horizontal');

    expect($builder->getFilters())->toContain('hflip');
});

it('can flip video vertically', function () {
    $builder = new FFmpegBuilder;
    $builder->flip('vertical');

    expect($builder->getFilters())->toContain('vflip');
});

it('can add fade in', function () {
    $builder = new FFmpegBuilder;
    $builder->fadeIn(2);

    expect($builder->getFilters())->toContain('fade=in:d=2');
});

it('can add fade out', function () {
    $builder = new FFmpegBuilder;
    $builder->fadeOut(3);

    expect($builder->getFilters())->toContain('fade=out:d=3');
});

it('can apply blur', function () {
    $builder = new FFmpegBuilder;
    $builder->blur(10);

    expect($builder->getFilters())->toContain('boxblur=10:10');
});

it('can convert to grayscale', function () {
    $builder = new FFmpegBuilder;
    $builder->grayscale();

    expect($builder->getFilters())->toContain('hue=s=0');
});

it('can apply sepia tone', function () {
    $builder = new FFmpegBuilder;
    $builder->sepia();

    expect($builder->getFilters()[0])->toContain('colorchannelmixer');
});

it('can chain filter methods', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->scale(1920, 1080)
        ->fadeIn(1)
        ->fadeOut(1)
        ->grayscale();

    expect($result)->toBe($builder)
        ->and(count($builder->getFilters()))->toBe(4);
});

it('can add watermark', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('video.mp4')
        ->watermark('logo.png');

    expect($builder->getFilters())->not->toBeEmpty();
});

it('can overlay video', function () {
    $builder = new FFmpegBuilder;
    $builder->overlay(['x' => 10, 'y' => 10, 'width' => 320, 'height' => 180]);

    $filters = $builder->getFilters();
    expect($filters)->toHaveCount(2)
        ->and($filters[0])->toContain('scale=320:180')
        ->and($filters[1])->toContain('overlay=10:10');
});
