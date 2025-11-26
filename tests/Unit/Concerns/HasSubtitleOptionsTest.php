<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('can set subtitle codec', function () {
    $builder = new FFmpegBuilder;
    $builder->subtitleCodec('mov_text');

    expect($builder->getOutputOptions())->toBe(['c:s' => 'mov_text']);
});

it('can burn subtitles', function () {
    $builder = new FFmpegBuilder;
    $builder->burnSubtitles('subs.srt');

    expect($builder->getFilters())->toBe(["subtitles='subs.srt'"]);
});

it('escapes subtitle path for burn', function () {
    $builder = new FFmpegBuilder;
    $builder->burnSubtitles('C:\path\to\subs.srt');

    // Should replace backslashes with forward slashes and escape colons
    expect($builder->getFilters())->toBe(["subtitles='C\:/path/to/subs.srt'"]);
});

it('can add subtitle input', function () {
    $builder = new FFmpegBuilder;
    $builder->addSubtitle('subs.srt');

    expect($builder->getInputs())->toBe(['subs.srt']);
});

it('can extract subtitles', function () {
    $builder = new FFmpegBuilder;
    $builder->extractSubtitles();

    expect($builder->getOutputOptions())->toMatchArray([
        'vn' => true,
        'an' => true,
        'map' => '0:s:0',
        'f' => 'srt',
    ]);
});

it('can extract specific subtitle stream', function () {
    $builder = new FFmpegBuilder;
    $builder->extractSubtitles(2);

    expect($builder->getOutputOptions())->toMatchArray([
        'map' => '0:s:2',
    ]);
});
