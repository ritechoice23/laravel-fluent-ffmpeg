<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

trait HasAdvancedOptions
{
    /**
     * Set thread count
     */
    public function threads(?int $count = null): self
    {
        if ($count === null) {
            // Use CPU cores count
            $count = function_exists('shell_exec')
                ? (int) shell_exec('nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null || echo 4')
                : 4;
        }

        return $this->addOutputOption('threads', $count);
    }

    /**
     * Overwrite existing files
     */
    public function overwrite(bool $overwrite = true): self
    {
        if ($overwrite) {
            $this->addOutputOption('y', true);
        }

        return $this;
    }

    /**
     * Set process priority (nice value on Unix)
     */
    public function priority(int $priority): self
    {
        // Store for later use in execution
        $this->addOutputOption('nice', $priority);

        return $this;
    }

    /**
     * Override timeout for this operation
     */
    public function timeout(int $seconds): self
    {
        // Store for later use in execution
        $this->addOutputOption('timeout', $seconds);

        return $this;
    }

    /**
     * Validate all options before execution
     */
    public function validate(): self
    {
        // Basic validation
        if (empty($this->inputs)) {
            throw new \InvalidArgumentException('No input files specified');
        }

        return $this;
    }
}
