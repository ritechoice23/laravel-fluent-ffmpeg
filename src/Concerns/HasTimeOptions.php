<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasTimeOptions
{
    /**
     * Set output duration
     */
    public function duration(string $duration): self
    {
        return $this->addOutputOption('t', $duration);
    }

    /**
     * Seek to position before processing
     */
    public function seek(string $time): self
    {
        return $this->addInputOption('ss', $time);
    }

    /**
     * Start from specific time
     */
    public function startFrom(string $time): self
    {
        return $this->seek($time);
    }

    /**
     * End at specific time
     */
    public function stopAt(string $time): self
    {
        return $this->addOutputOption('to', $time);
    }

    /**
     * Extract clip between start and end times
     */
    public function clip(string $start, string $end): self
    {
        return $this->seek($start)->stopAt($end);
    }
}
