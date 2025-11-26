<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Jobs\ProcessVideoJob;

trait HasQueueSupport
{
    protected ?string $queueName = null;

    protected ?string $queueConnection = null;

    protected $queueDelay = null;

    /**
     * Dispatch the processing to a queue
     */
    public function queue(string $outputPath): \Illuminate\Foundation\Bus\PendingDispatch
    {
        $options = [
            '__raw_input_options' => $this->inputOptions,
            '__raw_output_options' => $this->outputOptions,
            '__filters' => $this->filters,
        ];

        $job = ProcessVideoJob::dispatch(
            $this->inputs,
            $outputPath,
            $options,
            $this->outputDisk
        );

        if ($this->queueName) {
            $job->onQueue($this->queueName);
        }

        if ($this->queueConnection) {
            $job->onConnection($this->queueConnection);
        }

        if ($this->queueDelay) {
            $job->delay($this->queueDelay);
        }

        return $job;
    }

    /**
     * Set the queue name
     */
    public function onQueue(string $queue): self
    {
        $this->queueName = $queue;

        return $this;
    }

    /**
     * Set the queue connection
     */
    public function onConnection(string $connection): self
    {
        $this->queueConnection = $connection;

        return $this;
    }

    /**
     * Set the job delay
     */
    public function delay($delay): self
    {
        $this->queueDelay = $delay;

        return $this;
    }
}
