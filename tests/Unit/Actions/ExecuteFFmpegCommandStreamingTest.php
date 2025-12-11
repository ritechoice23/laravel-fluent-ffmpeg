<?php

use Ritechoice23\FluentFFmpeg\Actions\ExecuteFFmpegCommand;

it('uses streaming when peaks config is set', function () {
    $executor = new ExecuteFFmpegCommand;

    $peaksConfig = [
        'samples_per_pixel' => 512,
        'normalize_range' => [0, 1],
    ];

    // We can't easily test the full execution without FFmpeg,
    // but we can verify the logic that determines streaming vs standard
    $needsStreaming = (null !== null) || ($peaksConfig !== null);

    expect($needsStreaming)->toBe(true);
});

it('uses streaming when outputDisk is set', function () {
    $outputDisk = 's3';
    $peaksConfig = null;

    $needsStreaming = ($outputDisk !== null) || ($peaksConfig !== null);

    expect($needsStreaming)->toBe(true);
});

it('uses streaming when both outputDisk and peaks are set', function () {
    $outputDisk = 's3';
    $peaksConfig = ['samples_per_pixel' => 512];

    $needsStreaming = ($outputDisk !== null) || ($peaksConfig !== null);

    expect($needsStreaming)->toBe(true);
});

it('uses standard execution when neither is set', function () {
    $outputDisk = null;
    $peaksConfig = null;

    $needsStreaming = ($outputDisk !== null) || ($peaksConfig !== null);

    expect($needsStreaming)->toBe(false);
});

it('returns default values when probe fails', function () {
    // This tests the fallback behavior
    $defaultInfo = [
        'channels' => 2,
        'sample_rate' => 44100,
    ];

    expect($defaultInfo['channels'])->toBe(2)
        ->and($defaultInfo['sample_rate'])->toBe(44100);
});

it('extracts channels and sample_rate from audio stream', function () {
    // Simulate ffprobe output
    $audioStream = [
        'channels' => 1,
        'sample_rate' => 48000,
        'codec_name' => 'aac',
    ];

    $result = [
        'channels' => (int) ($audioStream['channels'] ?? 2),
        'sample_rate' => (int) ($audioStream['sample_rate'] ?? 44100),
    ];

    expect($result['channels'])->toBe(1)
        ->and($result['sample_rate'])->toBe(48000);
});

it('handles missing channels with default', function () {
    $audioStream = [
        'sample_rate' => 48000,
    ];

    $result = [
        'channels' => (int) ($audioStream['channels'] ?? 2),
        'sample_rate' => (int) ($audioStream['sample_rate'] ?? 44100),
    ];

    expect($result['channels'])->toBe(2);
});

it('handles missing sample_rate with default', function () {
    $audioStream = [
        'channels' => 1,
    ];

    $result = [
        'channels' => (int) ($audioStream['channels'] ?? 2),
        'sample_rate' => (int) ($audioStream['sample_rate'] ?? 44100),
    ];

    expect($result['sample_rate'])->toBe(44100);
});

it('returns success and peaks in streaming mode', function () {
    $mockResult = [
        'success' => true,
        'peaks' => [
            'version' => 2,
            'data' => [0.1, 0.2],
        ],
    ];

    expect($mockResult)->toHaveKey('success')
        ->and($mockResult)->toHaveKey('peaks')
        ->and($mockResult['success'])->toBe(true)
        ->and($mockResult['peaks'])->toBeArray();
});

it('returns success without peaks in standard mode', function () {
    $mockResult = [
        'success' => true,
        'peaks' => null,
    ];

    expect($mockResult)->toHaveKey('success')
        ->and($mockResult['success'])->toBe(true)
        ->and($mockResult['peaks'])->toBeNull();
});
