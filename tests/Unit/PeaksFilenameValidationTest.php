<?php

use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;

it('allows valid alphanumeric filenames', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    // Use reflection to test the validation method
    $reflection = new ReflectionClass($builder);
    $method = $reflection->getMethod('validatePeaksFilename');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($builder, 'valid-filename_123.json'))->not->toThrow(InvalidArgumentException::class);
});

it('allows filenames with paths', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    $reflection = new ReflectionClass($builder);
    $method = $reflection->getMethod('validatePeaksFilename');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($builder, 'path/to/file.json'))->not->toThrow(InvalidArgumentException::class);
});

it('rejects filenames with null bytes', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    $reflection = new ReflectionClass($builder);
    $method = $reflection->getMethod('validatePeaksFilename');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($builder, "file\0name.json"))
        ->toThrow(InvalidArgumentException::class, 'Invalid peaks filename');
});

it('rejects filenames with invalid characters', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    $reflection = new ReflectionClass($builder);
    $method = $reflection->getMethod('validatePeaksFilename');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($builder, 'file<>name.json'))
        ->toThrow(InvalidArgumentException::class, 'Invalid peaks filename');
});

it('rejects filenames with spaces', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    $reflection = new ReflectionClass($builder);
    $method = $reflection->getMethod('validatePeaksFilename');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($builder, 'file name.json'))
        ->toThrow(InvalidArgumentException::class, 'Invalid peaks filename');
});

it('rejects filenames with special characters', function () {
    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp3');

    $reflection = new ReflectionClass($builder);
    $method = $reflection->getMethod('validatePeaksFilename');
    $method->setAccessible(true);

    expect(fn () => $method->invoke($builder, 'file@name.json'))
        ->toThrow(InvalidArgumentException::class, 'Invalid peaks filename');
});
