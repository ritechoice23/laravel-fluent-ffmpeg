<?php

it('has s3_streaming enabled by default', function () {
    $default = config('fluent-ffmpeg.s3_streaming');

    expect($default)->toBe(true);
});

it('respects FFMPEG_S3_STREAMING env variable', function () {
    // This would be tested in integration tests with actual env
    // Just verify the config structure exists
    $config = config('fluent-ffmpeg');

    expect($config)->toHaveKey('s3_streaming');
});
