<?php

namespace Ritechoice23\FluentFFmpeg\Builder;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ritechoice23\FluentFFmpeg\Actions\BuildFFmpegCommand;
use Ritechoice23\FluentFFmpeg\Actions\ExecuteFFmpegCommand;
use Ritechoice23\FluentFFmpeg\Concerns\HasAdvancedOptions;
use Ritechoice23\FluentFFmpeg\Concerns\HasAudioOptions;
use Ritechoice23\FluentFFmpeg\Concerns\HasClipping;
use Ritechoice23\FluentFFmpeg\Concerns\HasCompatibilityOptions;
use Ritechoice23\FluentFFmpeg\Concerns\HasFilters;
use Ritechoice23\FluentFFmpeg\Concerns\HasFormatOptions;
use Ritechoice23\FluentFFmpeg\Concerns\HasHelperMethods;
use Ritechoice23\FluentFFmpeg\Concerns\HasHlsSupport;
use Ritechoice23\FluentFFmpeg\Concerns\HasMetadata;
use Ritechoice23\FluentFFmpeg\Concerns\HasQueueSupport;
use Ritechoice23\FluentFFmpeg\Concerns\HasSubtitleOptions;
use Ritechoice23\FluentFFmpeg\Concerns\HasTimeOptions;
use Ritechoice23\FluentFFmpeg\Concerns\HasVideoComposition;
use Ritechoice23\FluentFFmpeg\Concerns\HasVideoOptions;

class FFmpegBuilder
{
    use HasAdvancedOptions;
    use HasAudioOptions;
    use HasClipping;
    use HasCompatibilityOptions;
    use HasFilters;
    use HasFormatOptions;
    use HasHelperMethods;
    use HasHlsSupport;
    use HasMetadata;
    use HasQueueSupport;
    use HasSubtitleOptions;
    use HasTimeOptions;
    use HasVideoComposition;
    use HasVideoOptions;

    /**
     * Input file paths
     */
    protected array $inputs = [];

    /**
     * Input options
     */
    protected array $inputOptions = [];

    /**
     * Output options
     */
    protected array $outputOptions = [];

    /**
     * Filters to apply
     */
    protected array $filters = [];

    /**
     * Metadata to set
     */
    protected array $metadata = [];

    /**
     * Output path
     */
    protected ?string $outputPath = null;

    /**
     * Output disk (for Laravel filesystem)
     */
    protected ?string $outputDisk = null;

    /**
     * Progress callback
     */
    protected $progressCallback = null;

    /**
     * Error callback
     */
    protected $errorCallback = null;

    /**
     * Broadcast channel for progress updates
     */
    protected ?string $broadcastChannel = null;

    /**
     * Pending clips for batch processing
     */
    protected array $pendingClips = [];

    /**
     * Options for clip processing (intro, outro, watermark)
     */
    protected array $clipOptions = [];

    /**
     * Broadcast progress to a channel
     */
    public function broadcastProgress(string $channel): self
    {
        $this->broadcastChannel = $channel;

        return $this;
    }

    /**
     * Set input file from path
     */
    public function fromPath(string|array $path): self
    {
        if (is_array($path)) {
            $this->inputs = array_merge($this->inputs, $path);
        } else {
            $this->inputs[] = $path;
        }

        return $this;
    }

    /**
     * Set multiple inputs at once
     */
    public function fromPaths(array $paths): self
    {
        $this->inputs = array_merge($this->inputs, $paths);

        return $this;
    }

    /**
     * Add additional input
     */
    public function addInput(string $path): self
    {
        $this->inputs[] = $path;

        return $this;
    }

    /**
     * Input from Laravel disk
     */
    public function fromDisk(string $disk, string $path): self
    {
        $fullPath = Storage::disk($disk)->path($path);
        $this->inputs[] = $fullPath;

        return $this;
    }

    /**
     * Input from URL
     */
    public function fromUrl(string $url): self
    {
        $this->inputs[] = $url;

        return $this;
    }

    /**
     * Input from uploaded file
     */
    public function fromUploadedFile(UploadedFile $file): self
    {
        $this->inputs[] = $file->getRealPath();

        return $this;
    }

    /**
     * Execute and save to local path
     */
    public function save(string $path): bool|array
    {
        // Check if this is a batch clip operation
        if ($this->hasPendingClips()) {
            // Parse the output path to insert numbers before extension
            $pathInfo = pathinfo($path);
            $dir = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = isset($pathInfo['extension']) ? '.'.$pathInfo['extension'] : '';

            // Create pattern: filename_1.ext, filename_2.ext, etc.
            $outputPattern = ($dir ? $dir.DIRECTORY_SEPARATOR : '').$filename.'_{n}'.$extension;

            return $this->batchClips($this->getPendingClips(), $outputPattern);
        }

        // Normal single video save
        $this->outputPath = $path;

        // Apply intro/outro/watermark if specified
        if ($this->introPath || $this->outroPath || $this->watermarkPath) {
            $tempOutput = sys_get_temp_dir().'/'.uniqid('ffmpeg_').'_temp.mp4';
            $this->outputPath = $tempOutput;

            // Execute FFmpeg command to create temp file
            $result = $this->execute();

            if ($result) {
                // Apply composition (intro/outro/watermark)
                $this->applyComposition($tempOutput, $path);

                if (file_exists($tempOutput)) {
                    unlink($tempOutput);
                }

                return true;
            }

            return false;
        }

        return $this->execute();
    }

    /**
     * Save to Laravel disk
     */
    public function toDisk(string $disk, string $path): bool
    {
        $this->outputDisk = $disk;
        $this->outputPath = $path;

        return $this->execute();
    }

    /**
     * Get FFmpeg command without executing
     */
    public function getCommand(): string
    {
        return app(BuildFFmpegCommand::class)->execute($this);
    }

    /**
     * Dump FFmpeg command and exit
     */
    public function ddCommand(): string
    {
        dd($this->getCommand());
    }

    /**
     * Alias for getCommand
     */
    public function dryRun(): string
    {
        return $this->getCommand();
    }

    /**
     * Set progress callback
     */
    public function onProgress(callable $callback): self
    {
        $this->progressCallback = $callback;

        return $this;
    }

    /**
     * Set error callback
     */
    public function onError(callable $callback): self
    {
        $this->errorCallback = $callback;

        return $this;
    }

    /**
     * Add input option
     */
    public function addInputOption(string $key, mixed $value = null): self
    {
        $this->inputOptions[$key] = $value;

        return $this;
    }

    /**
     * Add output option
     */
    public function addOutputOption(string $key, mixed $value = null): self
    {
        $this->outputOptions[$key] = $value;

        return $this;
    }

    /**
     * Add custom option
     */
    public function addOption(string $key, mixed $value = null): self
    {
        return $this->addOutputOption($key, $value);
    }

    /**
     * Execute the FFmpeg command
     */
    protected function execute(): bool
    {
        $command = app(BuildFFmpegCommand::class)->execute($this);

        // Wrap progress callback to handle broadcasting
        $progressCallback = $this->progressCallback;
        if ($this->broadcastChannel) {
            $channel = $this->broadcastChannel;
            $progressCallback = function ($progress) use ($channel, $progressCallback) {
                // Broadcast progress
                event(new \Ritechoice23\FluentFFmpeg\Events\FFmpegProgressUpdated($channel, $progress));

                // Call original callback if exists
                if ($progressCallback) {
                    call_user_func($progressCallback, $progress);
                }
            };
        }

        return app(ExecuteFFmpegCommand::class)->execute(
            $command,
            $progressCallback,
            $this->errorCallback,
            $this->outputDisk,
            $this->outputPath,
            $this->inputs
        );
    }

    /**
     * Get inputs
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Get input options
     */
    public function getInputOptions(): array
    {
        return $this->inputOptions;
    }

    /**
     * Get output options
     */
    public function getOutputOptions(): array
    {
        return $this->outputOptions;
    }

    /**
     * Get filters
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get output path
     */
    public function getOutputPath(): ?string
    {
        return $this->outputPath;
    }

    /**
     * Get output disk
     */
    public function getOutputDisk(): ?string
    {
        return $this->outputDisk;
    }
}
