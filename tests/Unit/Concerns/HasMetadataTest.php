<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set title', function () {
    $builder = new FFmpegBuilder;
    $builder->title('My Video');

    expect($builder->getMetadata()['title'])->toBe('My Video');
});

it('can set artist', function () {
    $builder = new FFmpegBuilder;
    $builder->artist('John Doe');

    expect($builder->getMetadata()['artist'])->toBe('John Doe');
});

it('can set comment', function () {
    $builder = new FFmpegBuilder;
    $builder->comment('Test comment');

    expect($builder->getMetadata()['comment'])->toBe('Test comment');
});

it('can set album', function () {
    $builder = new FFmpegBuilder;
    $builder->album('Greatest Hits');

    expect($builder->getMetadata()['album'])->toBe('Greatest Hits');
});

it('can set year', function () {
    $builder = new FFmpegBuilder;
    $builder->year(2024);

    expect($builder->getMetadata()['date'])->toBe('2024');
});

it('can set multiple metadata at once', function () {
    $builder = new FFmpegBuilder;
    $builder->addMetadata([
        'title' => 'My Video',
        'artist' => 'John Doe',
        'year' => '2024',
    ]);

    expect($builder->getMetadata())->toHaveKeys(['title', 'artist', 'year']);
});

it('can chain metadata methods', function () {
    $builder = new FFmpegBuilder;

    $result = $builder
        ->title('My Video')
        ->artist('John Doe')
        ->year(2024);

    expect($result)->toBe($builder);
});
