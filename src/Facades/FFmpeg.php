<?php

namespace Ritechoice23\FluentFFmpeg\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;
use Ritechoice23\FluentFFmpeg\Actions\ProbeMediaFile;
use Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder;
use Ritechoice23\FluentFFmpeg\MediaInfo\MediaInfo;

/**
 * @method static FFmpegBuilder fromPath(string|array $path)
 * @method static FFmpegBuilder fromPaths(array $paths)
 * @method static FFmpegBuilder addInput(string $path)
 * @method static FFmpegBuilder fromDisk(string $disk, string $path)
 * @method static FFmpegBuilder fromUrl(string $url)
 * @method static FFmpegBuilder fromUploadedFile(UploadedFile $file)
 * @method static MediaInfo probe(string $filePath)
 *
 * @see \Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder
 */
class FFmpeg extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ffmpeg';
    }

    /**
     * Resolve a new FFmpegBuilder instance
     */
    protected static function resolveFacadeInstance($name)
    {
        return new FFmpegBuilder;
    }

    /**
     * Probe a media file to get information
     */
    public static function probe(string $filePath): MediaInfo
    {
        return app(ProbeMediaFile::class)->execute($filePath);
    }
}
