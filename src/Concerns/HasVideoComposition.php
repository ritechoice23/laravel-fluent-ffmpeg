<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

/**
 * Video composition: intro, outro, and watermark support
 * Available globally for any video processing
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
    protected string $watermarkPosition = 'top-right';

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
     * @param  string  $position  Position: top-left, top-right, bottom-left, bottom-right, center
     */
    public function withWatermark(string $watermarkPath, string $position = 'top-right'): self
    {
        $this->watermarkPath = $watermarkPath;
        $this->watermarkPosition = $position;

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

        $positions = [
            'top-left' => '10:10',
            'top-right' => 'W-w-10:10',
            'bottom-left' => '10:H-h-10',
            'bottom-right' => 'W-w-10:H-h-10',
            'center' => '(W-w)/2:(H-h)/2',
        ];

        $overlay = $positions[$position] ?? $positions['top-right'];

        FFmpeg::fromPath($videoPath)
            ->addInput($watermark)
            ->addFilter("overlay={$overlay}")
            ->videoCodec('libx264')
            ->audioCodec('copy')
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

        // No composition needed
        if (! $hasIntro && ! $hasOutro && ! $hasWatermark) {
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
            $this->addWatermark($tempFile, $outputPath);
            if ($tempFile !== $inputPath) {
                unlink($tempFile);
            }
        } else {
            if ($tempFile !== $outputPath) {
                rename($tempFile, $outputPath);
            }
        }
    }
}
