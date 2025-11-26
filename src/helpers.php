<?php

if (! function_exists('ffmpeg')) {
    /**
     * Get the FFmpeg builder instance.
     *
     * @return \Ritechoice23\FluentFFmpeg\Builder\FFmpegBuilder
     */
    function ffmpeg()
    {
        return app('ffmpeg');
    }
}
