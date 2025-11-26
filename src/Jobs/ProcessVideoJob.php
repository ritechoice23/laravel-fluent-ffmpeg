<?php

namespace Ritechoice23\FluentFFmpeg\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string|array $inputPath,
        protected string $outputPath,
        protected array $options = [],
        protected ?string $outputDisk = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $builder = FFmpeg::fromPath($this->inputPath);

        // Apply raw input options
        if (isset($this->options['__raw_input_options'])) {
            foreach ($this->options['__raw_input_options'] as $key => $value) {
                $builder->addInputOption($key, $value);
            }
            unset($this->options['__raw_input_options']);
        }

        // Apply raw output options
        if (isset($this->options['__raw_output_options'])) {
            foreach ($this->options['__raw_output_options'] as $key => $value) {
                $builder->addOutputOption($key, $value);
            }
            unset($this->options['__raw_output_options']);
        }

        // Apply filters
        if (isset($this->options['__filters'])) {
            foreach ($this->options['__filters'] as $filter) {
                $builder->addFilter($filter);
            }
            unset($this->options['__filters']);
        }

        // Apply method-based options
        foreach ($this->options as $method => $params) {
            if (method_exists($builder, $method)) {
                $builder = is_array($params)
                    ? $builder->$method(...$params)
                    : $builder->$method($params);
            }
        }

        // Save to disk or path
        if ($this->outputDisk) {
            $builder->toDisk($this->outputDisk, $this->outputPath);
        } else {
            $builder->save($this->outputPath);
        }
    }
}
