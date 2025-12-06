<?php

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

test('can add directory as input', function () {
    $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ffmpeg_test_' . uniqid();
    mkdir($testDir);

    // Create test files
    touch($testDir . DIRECTORY_SEPARATOR . 'video1.mp4');
    touch($testDir . DIRECTORY_SEPARATOR . 'video2.mp4');
    touch($testDir . DIRECTORY_SEPARATOR . 'video3.avi');
    touch($testDir . DIRECTORY_SEPARATOR . 'document.txt'); // Should be ignored

    $builder = FFmpeg::fromDirectory($testDir);

    $inputs = $builder->getInputs();

    expect($inputs)->toHaveCount(3);

    // Normalize paths for comparison
    $inputBasenames = array_map('basename', $inputs);
    expect($inputBasenames)->toContain('video1.mp4');
    expect($inputBasenames)->toContain('video2.mp4');
    expect($inputBasenames)->toContain('video3.avi');

    // Cleanup
    array_map('unlink', glob($testDir . DIRECTORY_SEPARATOR . '*'));
    rmdir($testDir);
});

test('can scan directory recursively', function () {
    $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ffmpeg_test_' . uniqid();
    $subDir = $testDir . DIRECTORY_SEPARATOR . 'subdir';
    mkdir($subDir, 0777, true);

    // Create test files
    touch($testDir . DIRECTORY_SEPARATOR . 'video1.mp4');
    touch($subDir . DIRECTORY_SEPARATOR . 'video2.mp4');

    $builder = FFmpeg::fromDirectory($testDir, true);

    $inputs = $builder->getInputs();

    expect($inputs)->toHaveCount(2);

    // Cleanup
    unlink($testDir . DIRECTORY_SEPARATOR . 'video1.mp4');
    unlink($subDir . DIRECTORY_SEPARATOR . 'video2.mp4');
    rmdir($subDir);
    rmdir($testDir);
});

test('can filter by allowed extensions', function () {
    $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ffmpeg_test_' . uniqid();
    mkdir($testDir);

    // Create test files
    touch($testDir . DIRECTORY_SEPARATOR . 'video1.mp4');
    touch($testDir . DIRECTORY_SEPARATOR . 'video2.avi');
    touch($testDir . DIRECTORY_SEPARATOR . 'video3.mkv');

    $builder = FFmpeg::allowExtensions(['mp4', 'mkv'])
        ->fromDirectory($testDir);

    $inputs = $builder->getInputs();

    expect($inputs)->toHaveCount(2);

    // Normalize paths for comparison
    $inputBasenames = array_map('basename', $inputs);
    expect($inputBasenames)->toContain('video1.mp4');
    expect($inputBasenames)->toContain('video3.mkv');
    expect($inputBasenames)->not->toContain('video2.avi');

    // Cleanup
    array_map('unlink', glob($testDir . DIRECTORY_SEPARATOR . '*'));
    rmdir($testDir);
});

test('throws exception for non-existent directory', function () {
    FFmpeg::fromDirectory('/path/that/does/not/exist');
})->throws(\InvalidArgumentException::class);

test('can set callback for each file', function () {
    $testDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ffmpeg_test_' . uniqid();
    mkdir($testDir);

    touch($testDir . DIRECTORY_SEPARATOR . 'video1.mp4');
    touch($testDir . DIRECTORY_SEPARATOR . 'video2.mp4');

    $processedFiles = [];

    $builder = FFmpeg::fromDirectory($testDir)
        ->eachFile(function ($builder, $file) use (&$processedFiles) {
            $processedFiles[] = $file;
        });

    expect($builder->getInputs())->toHaveCount(2);

    // Cleanup
    array_map('unlink', glob($testDir . DIRECTORY_SEPARATOR . '*'));
    rmdir($testDir);
});
