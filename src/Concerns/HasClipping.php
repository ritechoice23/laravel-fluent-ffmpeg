<?php

namespace Ritechoice23\FluentFFmpeg\Concerns;

use Ritechoice23\FluentFFmpeg\Facades\FFmpeg;

/**
 * Clip extraction support (single and batch)
 */
trait HasClipping
{
    /**
     * Pending clips for batch processing
     */
    protected array $pendingClips = [];

    /**
     * Extract multiple clips and save them individually
     *
     * @param  array  $clips  Array of clips with format: [['start' => '00:00:10', 'end' => '00:00:20'], ...]
     * @param  string  $outputPattern  Pattern for output files (use {n} for index): 'clip_{n}.mp4'
     * @return array Array of output file paths
     */
    public function batchClips(array $clips, string $outputPattern): array
    {
        $outputs = [];
        $inputFile = $this->getInputs()[0] ?? null;

        if (! $inputFile) {
            throw new \RuntimeException('No input file specified. Use fromPath() first.');
        }

        foreach ($clips as $index => $clip) {
            $start = $clip['start'] ?? throw new \InvalidArgumentException("Clip {$index}: 'start' is required");
            $end = $clip['end'] ?? throw new \InvalidArgumentException("Clip {$index}: 'end' is required");
            $output = str_replace('{n}', (string) ($index + 1), $outputPattern);

            // Extract the clip
            $tempClip = sys_get_temp_dir().'/'.uniqid('clip_').'_temp.mp4';

            FFmpeg::fromPath($inputFile)
                ->clip($start, $end)
                ->videoCodec('libx264')
                ->audioCodec('aac')
                ->save($tempClip);

            // Apply composition (intro/outro/watermark) if any
            $this->applyComposition($tempClip, $output);

            if (file_exists($tempClip) && $tempClip !== $output) {
                unlink($tempClip);
            }

            $outputs[] = $output;
        }

        return $outputs;
    }

    /**
     * Fluent helper: Define multiple clips to extract
     *
     * @param  array  $clips  Array of clips: [['start' => '00:00:10', 'end' => '00:00:20'], ...]
     */
    public function clips(array $clips): self
    {
        $this->pendingClips = $clips;

        return $this;
    }

    /**
     * Check if this is a batch clip operation
     */
    protected function hasPendingClips(): bool
    {
        return ! empty($this->pendingClips);
    }

    /**
     * Get pending clips
     */
    protected function getPendingClips(): array
    {
        return $this->pendingClips;
    }

    /**
     * Extract clip between start and end times
     */
    public function clip(string $start, string $end): self
    {
        return $this->seek($start)->stopAt($end);
    }
}
