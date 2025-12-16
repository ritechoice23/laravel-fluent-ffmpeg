<?php

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

test('can add multiple text overlays', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Hello World 1')
        ->withText('Hello World 2')
        ->withText('Hello World 3');

    expect($builder->getTextOverlayCount())->toBe(3);
    expect($builder->getTextOverlays())->toHaveCount(3);
});

test('text overlays are indexed correctly', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('First')
        ->withText('Second')
        ->withText('Third');

    $overlays = $builder->getTextOverlays();

    expect($overlays[0]['text'])->toBe('First');
    expect($overlays[1]['text'])->toBe('Second');
    expect($overlays[2]['text'])->toBe('Third');
});

test('can clear all text overlays', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Text 1')
        ->withText('Text 2')
        ->clearTextOverlays();

    expect($builder->getTextOverlayCount())->toBe(0);
    expect($builder->getTextOverlays())->toBeEmpty();
});

test('can remove specific text overlay by index', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('First')
        ->withText('Second')
        ->withText('Third')
        ->removeTextOverlay(1); // Remove 'Second'

    expect($builder->getTextOverlayCount())->toBe(2);
    expect($builder->getTextOverlays()[0]['text'])->toBe('First');
    expect($builder->getTextOverlays()[1]['text'])->toBe('Third');
});

test('removing non-existent overlay index does nothing', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Text 1')
        ->removeTextOverlay(999);

    expect($builder->getTextOverlayCount())->toBe(1);
});

test('array is re-indexed after removing overlay', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('First')
        ->withText('Second')
        ->withText('Third')
        ->removeTextOverlay(0); // Remove 'First'

    $overlays = $builder->getTextOverlays();

    // Should be re-indexed starting from 0
    expect(array_keys($overlays))->toBe([0, 1]);
    expect($overlays[0]['text'])->toBe('Second');
    expect($overlays[1]['text'])->toBe('Third');
});

test('can chain overlay management methods', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Text 1')
        ->withText('Text 2')
        ->withText('Text 3')
        ->removeTextOverlay(1)
        ->withText('Text 4')
        ->clearTextOverlays()
        ->withText('Final Text');

    expect($builder->getTextOverlayCount())->toBe(1);
    expect($builder->getTextOverlays()[0]['text'])->toBe('Final Text');
});

test('throws exception when exceeding maximum overlays', function () {
    $builder = FFmpeg::fromPath('input.mp4');

    // Add 50 overlays (maximum allowed)
    for ($i = 1; $i <= 50; $i++) {
        $builder->withText("Text $i");
    }

    expect($builder->getTextOverlayCount())->toBe(50);

    // Attempting to add 51st should throw exception
    $builder->withText('Text 51');
})->throws(\RuntimeException::class, 'Maximum of 50 text overlays allowed');

test('multiple overlays with different positions', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Top Left', ['position' => 'top-left'])
        ->withText('Top Center', ['position' => 'top-center'])
        ->withText('Bottom Right', ['position' => 'bottom-right']);

    $overlays = $builder->getTextOverlays();

    expect($overlays[0]['options']['position'])->toBe('top-left');
    expect($overlays[1]['options']['position'])->toBe('top-center');
    expect($overlays[2]['options']['position'])->toBe('bottom-right');
});

test('multiple overlays with different timing', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Start Text', ['start_time' => 0, 'duration' => 5])
        ->withText('Mid Text', ['start_time' => 5, 'duration' => 5])
        ->withText('End Text', ['start_time' => 10, 'duration' => 5]);

    $overlays = $builder->getTextOverlays();

    expect($overlays[0]['options']['start_time'])->toBe(0);
    expect($overlays[0]['options']['duration'])->toBe(5);
    expect($overlays[1]['options']['start_time'])->toBe(5);
    expect($overlays[2]['options']['start_time'])->toBe(10);
});

test('multiple overlays with callbacks', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText(fn ($file) => "File: $file")
        ->withText(fn ($file) => 'Path: '.dirname($file))
        ->withText('Static Text');

    $overlays = $builder->getTextOverlays();

    expect($overlays[0]['text'])->toBeCallable();
    expect($overlays[1]['text'])->toBeCallable();
    expect($overlays[2]['text'])->toBe('Static Text');
});

test('empty overlays returns empty array', function () {
    $builder = FFmpeg::fromPath('input.mp4');

    expect($builder->getTextOverlays())->toBeEmpty();
    expect($builder->getTextOverlayCount())->toBe(0);
});

test('overlays persist across method chains', function () {
    $builder = FFmpeg::fromPath('input.mp4')
        ->withText('Text 1')
        ->videoCodec('libx264')
        ->withText('Text 2')
        ->audioCodec('aac')
        ->withText('Text 3');

    expect($builder->getTextOverlayCount())->toBe(3);
});
