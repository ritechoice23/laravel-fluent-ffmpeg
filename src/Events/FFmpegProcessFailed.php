<?php

namespace Ritechoice23\FluentFFmpeg\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FFmpegProcessFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $command,
        public string $error,
        public int $exitCode
    ) {}
}
