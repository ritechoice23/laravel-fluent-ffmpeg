<?php

use Ritechoice23\FluentFFmpeg\Actions\PeaksStreamProcessor;

it('initializes with config', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 512,
        'normalize_range' => [0, 1],
    ]);

    expect($processor)->toBeInstanceOf(PeaksStreamProcessor::class);
});

it('sets audio info', function () {
    $processor = new PeaksStreamProcessor(['samples_per_pixel' => 512]);
    $processor->setAudioInfo(2, 44100);

    $result = $processor->finalize();

    expect($result['channels'])->toBe(2)
        ->and($result['sample_rate'])->toBe(44100);
});

it('processes PCM data and generates peaks', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 4,
        'normalize_range' => null,
    ]);
    $processor->setAudioInfo(1, 44100);

    // Create 4 samples of 16-bit PCM data (mono)
    // Values: 100, -200, 300, -400
    $pcmData = pack('s*', 100, -200, 300, -400);

    $processor->processPcmChunk($pcmData);
    $result = $processor->finalize();

    expect($result['data'])->toBeArray()
        ->and(count($result['data']))->toBe(2) // 1 min/max pair for mono
        ->and($result['data'][0])->toBe(-400) // min
        ->and($result['data'][1])->toBe(300); // max
});

it('normalizes peaks to 0-1 range', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 2,
        'normalize_range' => [0, 1],
    ]);
    $processor->setAudioInfo(1, 44100);

    // Create 2 samples: 0 and 32767 (max positive 16-bit value)
    $pcmData = pack('s*', 0, 32767);

    $processor->processPcmChunk($pcmData);
    $result = $processor->finalize();

    expect($result['data'][0])->toBeGreaterThan(0.499) // 0 normalized ~0.5
        ->and($result['data'][0])->toBeLessThan(0.501)
        ->and($result['data'][1])->toBe(1.0); // 32767 normalized to 1.0
});

it('normalizes peaks to -1 to 1 range', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 2,
        'normalize_range' => [-1, 1],
    ]);
    $processor->setAudioInfo(1, 44100);

    // Create 2 samples: -32768 (min) and 32767 (max)
    $pcmData = pack('s*', -32768, 32767);

    $processor->processPcmChunk($pcmData);
    $result = $processor->finalize();

    expect($result['data'][0])->toBe(-1.0) // -32768 normalized
        ->and($result['data'][1])->toBeGreaterThan(0.99); // ~1.0
});

it('handles stereo audio correctly', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 2,
        'normalize_range' => null,
    ]);
    $processor->setAudioInfo(2, 44100);

    // Create 2 frames of stereo: [L1, R1, L2, R2]
    $pcmData = pack('s*', 100, 200, 300, 400);

    $processor->processPcmChunk($pcmData);
    $result = $processor->finalize();

    // Should have 4 values: min/max for L, min/max for R
    expect(count($result['data']))->toBe(4)
        ->and($result['data'][0])->toBe(100) // L min
        ->and($result['data'][1])->toBe(300) // L max
        ->and($result['data'][2])->toBe(200) // R min
        ->and($result['data'][3])->toBe(400); // R max
});

it('handles incomplete frames in buffer', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 1,
        'normalize_range' => null,
    ]);
    $processor->setAudioInfo(1, 44100);

    // Send 1 complete sample
    $pcmData = pack('s', 100);
    $processor->processPcmChunk($pcmData);

    // Send 1 byte (incomplete sample)
    $pcmData = pack('C', 0x64);
    $processor->processPcmChunk($pcmData);

    // Send remaining byte to complete the sample (200 = 0xC8 0x00 in LE)
    $pcmData = pack('C', 0x00);
    $processor->processPcmChunk($pcmData);

    $result = $processor->finalize();

    expect($result['data'])->toBeArray()
        ->and(count($result['data']))->toBe(4); // 2 samples = 2 min/max pairs = 4 values
});

it('processes multiple chunks', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 4,
        'normalize_range' => null,
    ]);
    $processor->setAudioInfo(1, 44100);

    // Send data in multiple chunks
    $processor->processPcmChunk(pack('s*', 100, 200));
    $processor->processPcmChunk(pack('s*', 300, 400));

    $result = $processor->finalize();

    expect($result['data'])->toBeArray()
        ->and(count($result['data']))->toBe(2); // 1 min/max pair
});

it('finalizes remaining samples', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 10,
        'normalize_range' => null,
    ]);
    $processor->setAudioInfo(1, 44100);

    // Send only 5 samples (less than samplesPerPixel)
    $pcmData = pack('s*', 100, 200, 300, 400, 500);

    $processor->processPcmChunk($pcmData);
    $result = $processor->finalize();

    // Should still output peaks for the partial chunk
    expect($result['data'])->toBeArray()
        ->and(count($result['data']))->toBe(2); // 1 min/max pair
});

it('returns correct metadata in result', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 512,
        'normalize_range' => [0, 1],
    ]);
    $processor->setAudioInfo(2, 48000);

    $result = $processor->finalize();

    expect($result['version'])->toBe(2)
        ->and($result['channels'])->toBe(2)
        ->and($result['sample_rate'])->toBe(48000)
        ->and($result['samples_per_pixel'])->toBe(512)
        ->and($result['bits'])->toBe(32) // 32-bit float for normalized
        ->and($result['length'])->toBe(0) // No data processed
        ->and($result['data'])->toBeArray();
});

it('sets bits to 16 for unnormalized peaks', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 512,
        'normalize_range' => null,
    ]);
    $processor->setAudioInfo(1, 44100);

    $result = $processor->finalize();

    expect($result['bits'])->toBe(16);
});

it('handles zero values correctly', function () {
    $processor = new PeaksStreamProcessor([
        'samples_per_pixel' => 2,
        'normalize_range' => [0, 1],
    ]);
    $processor->setAudioInfo(1, 44100);

    // All zeros
    $pcmData = pack('s*', 0, 0);

    $processor->processPcmChunk($pcmData);
    $result = $processor->finalize();

    expect($result['data'][0])->toBeGreaterThan(0.499) // 0 normalized to mid-range
        ->and($result['data'][0])->toBeLessThan(0.501)
        ->and($result['data'][1])->toBeGreaterThan(0.499)
        ->and($result['data'][1])->toBeLessThan(0.501);
});
