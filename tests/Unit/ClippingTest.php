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
    // Clean up generated clips
    $packageRoot = dirname(__DIR__, 2);
    $clips = glob($packageRoot.'/storage/framework/testing/clip*.mp4');
    foreach ($clips as $clip) {
        @unlink($clip);
    }
});

test('can extract single clip', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/clip.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->clip('00:00:00', '00:00:05')
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    $info = FFmpeg::probe($output);
    expect($info->duration())->toBeBetween(4, 6);

    unlink($output);
});

test('can extract multiple clips with auto-numbering', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $outputs = FFmpeg::fromPath($this->testVideo)
        ->clips([
            ['start' => '00:00:00', 'end' => '00:00:02'],
            ['start' => '00:00:03', 'end' => '00:00:05'],
        ])
        ->save($packageRoot.'/storage/framework/testing/clip.mp4');

    expect($outputs)->toBeArray();
    expect($outputs)->toHaveCount(2);
    expect($outputs[0])->toContain('clip_1.mp4');
    expect($outputs[1])->toContain('clip_2.mp4');

    foreach ($outputs as $output) {
        expect($output)->toBeFile();
        unlink($output);
    }
});

test('clip method uses correct time options', function () {
    $builder = FFmpeg::fromPath('test.mp4')
        ->clip('00:00:10', '00:00:20');

    $inputOptions = $builder->getInputOptions();
    $outputOptions = $builder->getOutputOptions();

    expect($inputOptions)->toHaveKey('ss');
    expect($inputOptions['ss'])->toBe('00:00:10');
    expect($outputOptions)->toHaveKey('to');
    expect($outputOptions['to'])->toBe('00:00:20');
});

test('batch clips throw error if start/end missing', function () {
    FFmpeg::fromPath('test.mp4')
        ->clips([
            ['start' => '00:00:10'], // Missing 'end'
        ])
        ->save('output.mp4');
})->throws(\InvalidArgumentException::class);

test('can extract clips with different output pattern', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $outputs = FFmpeg::fromPath($this->testVideo)
        ->clips([
            ['start' => '00:00:00', 'end' => '00:00:02'],
            ['start' => '00:00:03', 'end' => '00:00:05'],
        ])
        ->save($packageRoot.'/storage/framework/testing/highlight.mp4');

    expect($outputs[0])->toContain('highlight_1.mp4');
    expect($outputs[1])->toContain('highlight_2.mp4');

    foreach ($outputs as $output) {
        unlink($output);
    }
});

test('single clip with composition applies all features', function () {
    if (! file_exists($this->testVideo) || ! file_exists($this->testWatermark)) {
        $this->markTestSkipped('Test files not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/composed-clip.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->clip('00:00:00', '00:00:05')
        ->withWatermark($this->testWatermark, 'top-right')
        ->save($output);

    expect($result)->toBeTrue();
    expect($output)->toBeFile();

    unlink($output);
});

test('clips are processed sequentially', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $startTime = microtime(true);
    $packageRoot = dirname(__DIR__, 2);

    $outputs = FFmpeg::fromPath($this->testVideo)
        ->clips([
            ['start' => '00:00:00', 'end' => '00:00:01'],
            ['start' => '00:00:02', 'end' => '00:00:03'],
            ['start' => '00:00:04', 'end' => '00:00:05'],
        ])
        ->save($packageRoot.'/storage/framework/testing/clip.mp4');

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    expect($duration)->toBeGreaterThan(0.1);
    expect($outputs)->toHaveCount(3);

    foreach ($outputs as $output) {
        unlink($output);
    }
});

test('save returns array for batch clips', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $result = FFmpeg::fromPath($this->testVideo)
        ->clips([
            ['start' => '00:00:00', 'end' => '00:00:02'],
        ])
        ->save($packageRoot.'/storage/framework/testing/clip.mp4');

    expect($result)->toBeArray();

    foreach ($result as $output) {
        unlink($output);
    }
});

test('save returns bool for single clip', function () {
    if (! file_exists($this->testVideo)) {
        $this->markTestSkipped('Test video not found');
    }

    $packageRoot = dirname(__DIR__, 2);
    $output = $packageRoot.'/storage/framework/testing/clip.mp4';

    $result = FFmpeg::fromPath($this->testVideo)
        ->clip('00:00:00', '00:00:02')
        ->save($output);

    expect($result)->toBeBool();
    expect($result)->toBeTrue();

    unlink($output);
});
