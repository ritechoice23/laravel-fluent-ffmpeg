<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Enums\Position;
use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

/**
 * Video composition: intro, outro, and watermark support
 * Available globally for any video processing
 *
 * Note: Text overlay functionality is in HasTextOverlay trait
 */
trait HasVideoComposition
{
    /**
     * Intro video path
     */
    protected ?string $introPath = null;

    /**
     * Outro video path
     */
    protected ?string $outroPath = null;

    /**
     * Watermark image path
     */
    protected ?string $watermarkPath = null;

    /**
     * Watermark position
     */
    protected string $watermarkPosition = Position::TOP_RIGHT->value;

    /**
     * Add intro video
     */
    public function withIntro(string $introPath): self
    {
        $this->introPath = $introPath;

        return $this;
    }

    /**
     * Add outro video
     */
    public function withOutro(string $outroPath): self
    {
        $this->outroPath = $outroPath;

        return $this;
    }

    /**
     * Add watermark
     *
     * @param  string  $watermarkPath  Path to watermark image
     * @param  Position|string  $position  Position enum or legacy string value
     */
    public function withWatermark(string $watermarkPath, Position|string $position = Position::TOP_RIGHT): self
    {
        $this->watermarkPath = $watermarkPath;
        $this->watermarkPosition = $position instanceof Position ? $position->value : $position;

        return $this;
    }

    /**
     * Add intro and outro to a video
     */
    public function addIntroOutro(string $videoPath, string $outputPath, ?string $intro = null, ?string $outro = null): void
    {
        $intro = $intro ?? $this->introPath;
        $outro = $outro ?? $this->outroPath;

        if (! $intro && ! $outro) {
            copy($videoPath, $outputPath);

            return;
        }

        $inputs = [];
        $fileList = sys_get_temp_dir().'/'.uniqid('concat_').'_list.txt';

        // Build concat list
        if ($intro) {
            $inputs[] = "file '".str_replace("'", "'\\''", realpath($intro))."'";
        }

        $inputs[] = "file '".str_replace("'", "'\\''", realpath($videoPath))."'";

        if ($outro) {
            $inputs[] = "file '".str_replace("'", "'\\''", realpath($outro))."'";
        }

        file_put_contents($fileList, implode("\n", $inputs));

        try {
            FFmpeg::fromPath($fileList)
                ->addInputOption('f', 'concat')
                ->addInputOption('safe', '0')
                ->videoCodec('copy')
                ->audioCodec('copy')
                ->save($outputPath);
        } finally {
            unlink($fileList);
        }
    }

    /**
     * Add watermark to a video
     */
    public function addWatermark(string $videoPath, string $outputPath, ?string $watermark = null, ?string $position = null): void
    {
        $watermark = $watermark ?? $this->watermarkPath;
        $position = $position ?? $this->watermarkPosition;

        if (! $watermark) {
            copy($videoPath, $outputPath);

            return;
        }

        // Try to use enum for position lookup
        $positionEnum = Position::tryFrom($position);
        $coords = $positionEnum?->getWatermarkCoordinates() ?? ['x' => 'W-w-10', 'y' => 10];

        FFmpeg::fromPath($videoPath)
            ->addInput($watermark)
            ->overlay($coords)  // Use the overlay() API
            ->videoCodec('libx264')
            ->audioCodec('copy')
            ->gopSize(60)
            ->keyframeInterval(60)
            ->sceneChangeThreshold(0)
            ->save($outputPath);
    }

    /**
     * Apply all composition (intro/outro/watermark) to a video
     */
    protected function applyComposition(string $inputPath, string $outputPath): void
    {
        $hasIntro = $this->introPath !== null;
        $hasOutro = $this->outroPath !== null;
        $hasWatermark = $this->watermarkPath !== null;
        $hasText = $this->textOverlays !== [];

        // No composition needed
        if (! $hasIntro && ! $hasOutro && ! $hasWatermark && ! $hasText) {
            if ($inputPath !== $outputPath) {
                copy($inputPath, $outputPath);
            }

            return;
        }

        $tempFile = $inputPath;

        // Step 1: Add intro/outro
        if ($hasIntro || $hasOutro) {
            $tempWithIntroOutro = sys_get_temp_dir().'/'.uniqid('composed_').'_temp.mp4';
            $this->addIntroOutro($tempFile, $tempWithIntroOutro);

            if ($tempFile !== $inputPath) {
                unlink($tempFile);
            }
            $tempFile = $tempWithIntroOutro;
        }

        // Step 2: Add watermark
        if ($hasWatermark) {
            $tempWithWatermark = sys_get_temp_dir().'/'.uniqid('watermarked_').'_temp.mp4';
            $this->addWatermark($tempFile, $tempWithWatermark);
            if ($tempFile !== $inputPath) {
                unlink($tempFile);
            }
            $tempFile = $tempWithWatermark;
        }

        // Step 3: Add text overlay
        if ($hasText) {
            $this->addTextOverlay($tempFile, $outputPath);
            if ($tempFile !== $inputPath) {
                unlink($tempFile);
            }
        } else {
            if ($tempFile !== $outputPath) {
                rename($tempFile, $outputPath);
            }
        }
    }

    /**
     * Overlay video or image (Picture-in-Picture)
     *
     * @param  array  $options  Options with x, y, width, height positions
     */
    public function overlay(array $options = []): self
    {
        $x = $options['x'] ?? 10;
        $y = $options['y'] ?? 10;
        $width = $options['width'] ?? null;
        $height = $options['height'] ?? null;

        // If width/height specified, scale the overlay input (assumed to be the second input)
        if ($width && $height) {
            $this->addFilter("[1:v]scale={$width}:{$height}[overlay]");

            // Overlay it on the main input
            return $this->addFilter("[0:v][overlay]overlay={$x}:{$y}");
        }

        // No scaling, just overlay directly
        return $this->addFilter("overlay={$x}:{$y}");
    }

    /**
     * Concatenate multiple videos
     *
     * @param  array  $inputs  Additional input files to concatenate
     */
    public function concat(array $inputs = []): self
    {
        if (! empty($inputs)) {
            foreach ($inputs as $input) {
                $this->addInput($input);
            }
        }

        $count = count($this->getInputs());
        $this->addFilter("concat=n={$count}:v=1:a=1[outv][outa]");
        $this->addOutputOption('map', '[outv]');
        $this->addOutputOption('map', '[outa]');

        return $this;
    }
}
