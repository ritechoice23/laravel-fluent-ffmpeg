<?php

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

beforeEach(function () {
    // Use actual test assets from package root
    $packageRoot = dirname(__DIR__, 2);
    $this->testVideo = $packageRoot.'/assets/test-clip.mp4';
    $this->testIntro = $packageRoot.'/assets/test-clip.mp4'; // Reuse same video as intro
    $this->testOutro = $packageRoot.'/assets/test-clip.mp4'; // Reuse same video as outro
    $this->testWatermark = $packageRoot.'/assets/thumbnail.png';

    // Ensure test directory exists for outputs
    $testDir = $packageRoot.'/storage/framework/testing';
    @mkdir($testDir, 0755, true);
});

afterEach(function () {
    // Clean up test outputs
    $packageRoot = dirname(__DIR__, 2);
    $outputs = glob($packageRoot.'/storage/framework/testing/composed*.mp4');
    foreach ($outputs as $output) {
        @unlink($output);
    }
});

test('can add intro to video', function () {
    if (! file_exists($this->testVideo) || ! file_exists($this->testIntro)) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/composed-with-intro.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->withIntro($this->testIntro)
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    $originalInfo = FFmpeg::probe($this->testVideo);
    $introInfo = FFmpeg::probe($this->testIntro);
    $composedInfo = FFmpeg::probe($output);

    expect($composedInfo->duration())->toBeGreaterThan($originalInfo->duration());

    unlink($output);
});

test('can add outro to video', function () {
    if (! file_exists($this->testVideo) || ! file_exists($this->testOutro)) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/composed-with-outro.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->withOutro($this->testOutro)
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    unlink($output);
});

test('can add watermark to video', function () {
    if (! file_exists($this->testVideo) || ! file_exists($this->testWatermark)) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/composed-with-watermark.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->withWatermark($this->testWatermark, 'bottom-right')
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    unlink($output);
});

test('can combine intro outro and watermark', function () {
    if (
        ! file_exists($this->testVideo) || ! file_exists($this->testIntro) ||
        ! file_exists($this->testOutro) || ! file_exists($this->testWatermark)
    ) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/composed-full.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->withIntro($this->testIntro)
        ->withOutro($this->testOutro)
        ->withWatermark($this->testWatermark)
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    unlink($output);
});

test('composition works with clips', function () {
    if (! file_exists($this->testVideo) || ! file_exists($this->testWatermark)) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $outputs = FFmpeg::fromPath($this->testVideo)
        ->clips([
            ['start' => '00:00:00', 'end' => '00:00:02'],
            ['start' => '00:00:03', 'end' => '00:00:05'],
        ])
        ->withWatermark($this->testWatermark)
        ->save($packageRoot.'/storage/framework/testing/composed-clip.mp4');

    expect($outputs)->toBeArray();
    expect($outputs)->toHaveCount(2);

    foreach ($outputs as $output) {
        expect($output)->toBeFile();
        unlink($output);
    }
});

test('composition applies to all clips individually', function () {
    if (! file_exists($this->testVideo) || ! file_exists($this->testWatermark)) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $outputs = FFmpeg::fromPath($this->testVideo)
        ->clips([
            ['start' => '00:00:00', 'end' => '00:00:01'],
            ['start' => '00:00:02', 'end' => '00:00:03'],
        ])
        ->withWatermark($this->testWatermark)
        ->save($packageRoot.'/storage/framework/testing/composed-clip.mp4');

    expect($outputs)->toHaveCount(2);

    foreach ($outputs as $output) {
        expect($output)->toBeFile();
        expect(filesize($output))->toBeGreaterThan(0);
        unlink($output);
    }
});

test('applyComposition does nothing if no composition set', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/no-composition.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    unlink($output);
});

test('composition methods return builder for chaining', function () {
    $builder = FFmpeg::fromPath('test.mp4');

    $result1 = $builder->withIntro('intro.mp4');
    $result2 = $builder->withOutro('outro.mp4');
    $result3 = $builder->withWatermark('logo.png');

    expect($result1)->toBe($builder);
    expect($result2)->toBe($builder);
    expect($result3)->toBe($builder);
});
