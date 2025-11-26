<?php

use Illuminate\Support\Facades\Queue;
use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;
use Ritechoice23\FluentFFmpeg\Jobs\ProcessVideoJob;

it('can dispatch job to queue', function () {
    Queue::fake();

    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp4')
        ->videoCodec('libx264')
        ->queue('output.mp4');

    Queue::assertPushed(ProcessVideoJob::class, function ($job) {
        $inputPath = (new ReflectionClass($job))->getProperty('inputPath')->getValue($job);
        $outputPath = (new ReflectionClass($job))->getProperty('outputPath')->getValue($job);

        return $inputPath === ['input.mp4'] &&
            $outputPath === 'output.mp4';
    });
});

it('can set queue options', function () {
    Queue::fake();

    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp4')
        ->onQueue('high-priority')
        ->onConnection('redis')
        ->delay(60)
        ->queue('output.mp4');

    Queue::assertPushed(ProcessVideoJob::class, function ($job) {
        return $job->queue === 'high-priority' &&
            $job->connection === 'redis' &&
            $job->delay === 60;
    });
});

it('passes options to job', function () {
    Queue::fake();

    $builder = new FFmpegBuilder;
    $builder->fromPath('input.mp4')
        ->videoCodec('libx264')
        ->addFilter('scale=1280:720')
        ->queue('output.mp4');

    Queue::assertPushed(ProcessVideoJob::class, function ($job) {
        $options = (new ReflectionClass($job))->getProperty('options')->getValue($job);

        return isset($options['__raw_output_options']['c:v']) &&
            $options['__raw_output_options']['c:v'] === 'libx264' &&
            isset($options['__filters']) &&
            in_array('scale=1280:720', $options['__filters']);
    });
});
