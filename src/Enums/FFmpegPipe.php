<?php

namespace Ritechoice23\FluentFFmpeg\Enums;

enum FFmpegPipe: string
{
    case STDIN = 'pipe:0';
    case PROGRESS = 'pipe:1';
    case STDERR = 'pipe:2';
    case PEAKS_PCM = 'pipe:3';
    case OUTPUT = 'pipe:4';

    /**
     * Get the pipe number
     */
    public function getNumber(): int
    {
        return match ($this) {
            self::STDIN => 0,
            self::PROGRESS => 1,
            self::STDERR => 2,
            self::PEAKS_PCM => 3,
            self::OUTPUT => 4,
        };
    }

    /**
     * Create from pipe number
     */
    public static function fromNumber(int $number): ?self
    {
        return match ($number) {
            0 => self::STDIN,
            1 => self::PROGRESS,
            2 => self::STDERR,
            3 => self::PEAKS_PCM,
            4 => self::OUTPUT,
            default => null,
        };
    }
}
