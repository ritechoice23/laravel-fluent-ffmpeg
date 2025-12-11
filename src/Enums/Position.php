<?php

namespace Ritechoice23\FluentFFmpeg\Enums;

enum Position: string
{
    case TOP_LEFT = 'top-left';
    case TOP_CENTER = 'top-center';
    case TOP_RIGHT = 'top-right';
    case CENTER = 'center';
    case BOTTOM_LEFT = 'bottom-left';
    case BOTTOM_CENTER = 'bottom-center';
    case BOTTOM_RIGHT = 'bottom-right';

    /**
     * Get overlay coordinates for this position
     */
    public function getCoordinates(int $padding = 10): array
    {
        return match ($this) {
            self::TOP_LEFT => ['x' => $padding, 'y' => $padding],
            self::TOP_CENTER => ['x' => '(w-text_w)/2', 'y' => $padding],
            self::TOP_RIGHT => ['x' => "w-text_w-{$padding}", 'y' => $padding],
            self::CENTER => ['x' => '(w-text_w)/2', 'y' => '(h-text_h)/2'],
            self::BOTTOM_LEFT => ['x' => $padding, 'y' => "h-text_h-{$padding}"],
            self::BOTTOM_CENTER => ['x' => '(w-text_w)/2', 'y' => "h-text_h-{$padding}"],
            self::BOTTOM_RIGHT => ['x' => "w-text_w-{$padding}", 'y' => "h-text_h-{$padding}"],
        };
    }

    /**
     * Get watermark overlay coordinates (using W/H for main video, w/h for overlay)
     */
    public function getWatermarkCoordinates(): array
    {
        return match ($this) {
            self::TOP_LEFT => ['x' => 10, 'y' => 10],
            self::TOP_RIGHT => ['x' => 'W-w-10', 'y' => 10],
            self::BOTTOM_LEFT => ['x' => 10, 'y' => 'H-h-10'],
            self::BOTTOM_RIGHT => ['x' => 'W-w-10', 'y' => 'H-h-10'],
            self::CENTER => ['x' => '(W-w)/2', 'y' => '(H-h)/2'],
            self::TOP_CENTER => ['x' => '(W-w)/2', 'y' => 10],
            self::BOTTOM_CENTER => ['x' => '(W-w)/2', 'y' => 'H-h-10'],
        };
    }
}
