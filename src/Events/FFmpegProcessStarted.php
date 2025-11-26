<?php

namespace Ritechoice23\FluentFFmpeg\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FFmpegProcessStarted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $command,
        public array $inputs,
        public ?string $outputPath = null
    ) {}
}
