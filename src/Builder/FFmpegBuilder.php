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
use Ritechoice23\FluentFFmpeg\Concerns\HasTextOverlay;
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
    use HasTextOverlay;
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
     * Directory processing mode enabled
     */
    protected bool $directoryMode = false;

    /**
     * File extensions to include when processing directories
     */
    protected array $allowedExtensions = ['mp4', 'avi', 'mkv', 'mov', 'flv', 'wmv', 'webm', 'm4v'];

    /**
     * Callback for processing each file in directory
     */
    protected $directoryFileCallback = null;

    /**
     * Current file being processed (for callback context)
     */
    protected ?string $currentProcessingFile = null;

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
     * Process all video files from a directory
     *
     * @param  string  $directoryPath  Path to the directory
     * @param  bool  $recursive  Whether to search subdirectories recursively
     */
    public function fromDirectory(string $directoryPath, bool $recursive = false): self
    {
        if (!is_dir($directoryPath)) {
            throw new \InvalidArgumentException("Directory not found: {$directoryPath}");
        }

        $this->directoryMode = true;
        $files = $this->scanDirectory($directoryPath, $recursive);
        $this->inputs = array_merge($this->inputs, $files);

        return $this;
    }

    /**
     * Set allowed file extensions for directory processing
     *
     * @param  array  $extensions  Array of file extensions (without dots)
     */
    public function allowExtensions(array $extensions): self
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);

        return $this;
    }

    /**
     * Set a callback to be executed for each file when processing directory
     * The callback receives the current file path being processed
     *
     * @param  callable  $callback  Function that receives current file path
     */
    public function eachFile(callable $callback): self
    {
        $this->directoryFileCallback = $callback;

        return $this;
    }

    /**
     * Scan directory for video files
     *
     * @param  string  $directory  Directory path
     * @param  bool  $recursive  Whether to scan recursively
     * @return array  Array of file paths
     */
    protected function scanDirectory(string $directory, bool $recursive = false): array
    {
        $files = [];
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        $iterator = $recursive
            ? new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS))
            : new \DirectoryIterator($directory);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $this->allowedExtensions)) {
                    $files[] = $file->getRealPath();
                }
            }
        }

        return $files;
    }

    /**
     * Get the current file being processed (for use in callbacks)
     *
     * @return string|null
     */
    public function getCurrentFile(): ?string
    {
        return $this->currentProcessingFile;
    }

    /**
     * Execute and save to local path
     */
    public function save(string $path): bool|array
    {
        // Check if this is a directory processing operation
        if ($this->directoryMode && count($this->inputs) > 1) {
            return $this->processDirectory($path);
        }

        // Check if this is a batch clip operation
        if ($this->hasPendingClips()) {
            // Parse the output path to insert numbers before extension
            $pathInfo = pathinfo($path);
            $dir = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

            // Create pattern: filename_1.ext, filename_2.ext, etc.
            $outputPattern = ($dir ? $dir . DIRECTORY_SEPARATOR : '') . $filename . '_{n}' . $extension;

            return $this->batchClips($this->getPendingClips(), $outputPattern);
        }

        // Normal single video save
        $this->outputPath = $path;

        // Apply intro/outro/watermark if specified
        if ($this->introPath || $this->outroPath || $this->watermarkPath) {
            $tempOutput = sys_get_temp_dir() . '/' . uniqid('ffmpeg_') . '_temp.mp4';
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
     * Process directory of files
     *
     * @param  string  $outputPath  Output directory or pattern
     * @return array  Array of results for each file
     */
    protected function processDirectory(string $outputPath): array
    {
        $results = [];
        $isOutputDirectory = is_dir($outputPath);

        // Ensure output directory exists if it's a directory
        if ($isOutputDirectory) {
            $outputPath = rtrim($outputPath, DIRECTORY_SEPARATOR);
        } else {
            // Create output directory from path pattern
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
        }

        foreach ($this->inputs as $index => $inputFile) {
            // Set current file for callback context
            $this->currentProcessingFile = $inputFile;

            // Clone the current builder to avoid state pollution
            $builder = clone $this;
            $builder->inputs = [$inputFile];
            $builder->directoryMode = false;

            // Call file callback if provided
            if ($this->directoryFileCallback) {
                call_user_func($this->directoryFileCallback, $builder, $inputFile);
            }

            // Determine output file path
            if ($isOutputDirectory) {
                // Save to output directory with same filename
                $filename = basename($inputFile);
                $outputFile = $outputPath . DIRECTORY_SEPARATOR . $filename;
            } else {
                // Use pattern with {n}, {name}, {ext} placeholders
                $pathInfo = pathinfo($inputFile);
                $outputFile = str_replace(
                    ['{n}', '{name}', '{ext}', '{index}'],
                    [$index + 1, $pathInfo['filename'], $pathInfo['extension'], $index],
                    $outputPath
                );
            }

            try {
                $result = $builder->save($outputFile);
                $results[] = [
                    'input' => $inputFile,
                    'output' => $outputFile,
                    'success' => $result,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'input' => $inputFile,
                    'output' => $outputFile,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Reset current file
        $this->currentProcessingFile = null;

        return $results;
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
