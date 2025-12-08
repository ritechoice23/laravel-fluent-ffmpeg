<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

/**
 * Text overlay support for videos
 * Add styled text overlays with positioning, timing, and customization
 */
trait HasTextOverlay
{
    /**
     * Text overlay configuration
     */
    protected ?array $textOverlay = null;

    /**
     * Add text overlay to video
     *
     * @param  string|callable  $text  Text to display, or callback that receives current file path
     * @param  array  $options  Styling options
     * @return self
     *
     * Options:
     * - position: 'top-left', 'top-center', 'top-right', 'center', 'bottom-left', 'bottom-center', 'bottom-right', or ['x' => int, 'y' => int]
     * - font_size: Font size in pixels (default: 24)
     * - font_color: Text color in hex format (default: 'white')
     * - background_color: Background color in hex format with optional alpha (default: 'black@0.5')
     * - border_width: Border width in pixels (default: 0)
     * - border_color: Border color in hex format (default: 'black')
     * - padding: Padding around text in pixels (default: 10)
     * - font_file: Path to custom font file (optional)
     * - duration: Duration to show text (null = entire video)
     * - start_time: When to start showing text in seconds (default: 0)
     */
    public function withText(string|callable $text, array $options = []): self
    {
        $this->textOverlay = [
            'text' => $text,
            'options' => array_merge([
                'position' => 'bottom-center',
                'font_size' => 24,
                'font_color' => 'white',
                'background_color' => 'black@0.5',
                'border_width' => 0,
                'border_color' => 'black',
                'padding' => 10,
                'font_file' => null,
                'duration' => null,
                'start_time' => 0,
            ], $options),
        ];

        return $this;
    }

    /**
     * Add text overlay to a video
     */
    protected function addTextOverlay(string $videoPath, string $outputPath): void
    {
        if (! $this->textOverlay) {
            copy($videoPath, $outputPath);

            return;
        }

        $text = $this->textOverlay['text'];
        $options = $this->textOverlay['options'];

        // If text is a callback, call it with the current file being processed
        if (is_callable($text)) {
            $currentFile = $this->getCurrentFile() ?? $videoPath;
            $text = call_user_func($text, $currentFile);
        }

        // Escape text for FFmpeg
        $text = $this->escapeDrawText($text);

        // Build drawtext filter
        $filter = $this->buildDrawTextFilter($text, $options);

        FFmpeg::fromPath($videoPath)
            ->addFilter($filter)
            ->videoCodec('libx264')
            ->audioCodec('copy')
            ->gopSize(60)
            ->keyframeInterval(60)
            ->sceneChangeThreshold(0)
            ->save($outputPath);
    }

    /**
     * Build drawtext filter string
     */
    protected function buildDrawTextFilter(string $text, array $options): string
    {
        $parts = ["text='{$text}'"];

        // Font settings
        if ($options['font_file']) {
            $parts[] = "fontfile='{$options['font_file']}'";
        }
        $parts[] = "fontsize={$options['font_size']}";
        $parts[] = "fontcolor={$this->formatColor($options['font_color'])}";

        // Position
        $position = $this->resolveTextPosition($options['position'], $options['padding']);
        $parts[] = "x={$position['x']}";
        $parts[] = "y={$position['y']}";

        // Background box
        if ($options['background_color']) {
            $parts[] = 'box=1';
            $parts[] = "boxcolor={$this->formatColor($options['background_color'])}";
            $parts[] = "boxborderw={$options['padding']}";
        }

        // Border
        if ($options['border_width'] > 0) {
            $parts[] = "borderw={$options['border_width']}";
            $parts[] = "bordercolor={$this->formatColor($options['border_color'])}";
        }

        // Timing
        if ($options['duration'] !== null || $options['start_time'] > 0) {
            $enable = '';
            if ($options['start_time'] > 0) {
                $enable .= "gte(t,{$options['start_time']})";
            }
            if ($options['duration'] !== null) {
                $endTime = $options['start_time'] + $options['duration'];
                if ($enable) {
                    $enable .= '*';
                }
                $enable .= "lte(t,{$endTime})";
            }
            if ($enable) {
                $parts[] = "enable='{$enable}'";
            }
        }

        return 'drawtext='.implode(':', $parts);
    }

    /**
     * Resolve text position to x/y coordinates
     */
    protected function resolveTextPosition(string|array $position, int $padding): array
    {
        if (is_array($position)) {
            return [
                'x' => $position['x'] ?? 0,
                'y' => $position['y'] ?? 0,
            ];
        }

        $positions = [
            'top-left' => ['x' => $padding, 'y' => $padding],
            'top-center' => ['x' => '(w-text_w)/2', 'y' => $padding],
            'top-right' => ['x' => "w-text_w-{$padding}", 'y' => $padding],
            'center' => ['x' => '(w-text_w)/2', 'y' => '(h-text_h)/2'],
            'bottom-left' => ['x' => $padding, 'y' => "h-text_h-{$padding}"],
            'bottom-center' => ['x' => '(w-text_w)/2', 'y' => "h-text_h-{$padding}"],
            'bottom-right' => ['x' => "w-text_w-{$padding}", 'y' => "h-text_h-{$padding}"],
        ];

        return $positions[$position] ?? $positions['bottom-center'];
    }

    /**
     * Format color for FFmpeg (handles hex colors and alpha)
     */
    protected function formatColor(string $color): string
    {
        // If color already contains @, it has alpha, return as-is
        if (strpos($color, '@') !== false) {
            return $color;
        }

        // If color starts with #, remove it for FFmpeg
        if (strpos($color, '#') === 0) {
            $color = substr($color, 1);
        }

        // Check if it's a named color or hex, FFmpeg accepts both
        return $color;
    }

    /**
     * Escape text for drawtext filter
     */
    protected function escapeDrawText(string $text): string
    {
        // Escape special characters for FFmpeg drawtext
        $text = str_replace(['\\', '\'', ':', '[', ']', ',', ';'], ['\\\\', '\\\'', '\\:', '\\[', '\\]', '\\,', '\\;'], $text);

        return $text;
    }
}
