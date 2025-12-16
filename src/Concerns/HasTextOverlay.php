<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Enums\Position;
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

/**
 * Text overlay support for videos
 * Add styled text overlays with positioning, timing, and customization
 */
trait HasTextOverlay
{
    /**
     * Text overlay configuration
     *
     * @var array<array{position: string, font_size: int, font_color: string, background_color: string, border_width: int, border_color: string, padding: int, font_file: string|null, duration: int|null, start_time: int}>
     */
    protected array $textOverlays = [];

    /**
     * Add text overlay to video
     *
     * Multiple text overlays can be added by calling this method multiple times.
     * Each overlay is rendered in the order added (first added = bottom layer, last added = top layer).
     *
     * @param  string|callable  $text  Text to display, or callback that receives current file path
     * @param  array  $options  Styling options
     *
     * @throws \RuntimeException If maximum number of overlays (50) is exceeded
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
        if (count($this->textOverlays) >= 50) {
            throw new \RuntimeException('Maximum of 50 text overlays allowed. Performance may degrade with too many overlays.');
        }

        $this->textOverlays[] = [
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
     * Clear all text overlays
     */
    public function clearTextOverlays(): self
    {
        $this->textOverlays = [];

        return $this;
    }

    /**
     * Remove a specific text overlay by index
     *
     * @param  int  $index  Zero-based index of overlay to remove
     */
    public function removeTextOverlay(int $index): self
    {
        if (isset($this->textOverlays[$index])) {
            unset($this->textOverlays[$index]);
            $this->textOverlays = array_values($this->textOverlays); // Re-index array
        }

        return $this;
    }

    /**
     * Get all configured text overlays
     */
    public function getTextOverlays(): array
    {
        return $this->textOverlays;
    }

    /**
     * Get the count of text overlays
     */
    public function getTextOverlayCount(): int
    {
        return count($this->textOverlays);
    }

    /**
     * Add text overlay to a video
     */
    protected function addTextOverlay(string $videoPath, string $outputPath): void
    {
        if ($this->textOverlays === []) {
            copy($videoPath, $outputPath);

            return;
        }

        $ffmpeg = FFmpeg::fromPath($videoPath);

        foreach ($this->textOverlays as $textOverlay) {
            $text = $textOverlay['text'];
            $options = $textOverlay['options'];

            // If text is a callback, call it with the current file being processed
            if (is_callable($text)) {
                $currentFile = $this->getCurrentFile() ?? $videoPath;
                $text = call_user_func($text, $currentFile);
            }

            // Escape text for FFmpeg
            $text = $this->escapeDrawText($text);

            // Build drawtext filter
            $filter = $this->buildDrawTextFilter($text, $options);

            // Add filter
            $ffmpeg = $ffmpeg->addFilter($filter);
        }

        $ffmpeg
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

        // Try to use enum for position lookup
        $positionEnum = Position::tryFrom($position);

        return $positionEnum?->getCoordinates($padding) ?? Position::BOTTOM_CENTER->getCoordinates($padding);
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
