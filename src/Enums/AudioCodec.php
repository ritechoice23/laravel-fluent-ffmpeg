<?php

namespace Ritechoice23\FluentFFmpeg\Enums;

enum AudioCodec: string
{
    case AAC = 'aac';
    case MP3 = 'libmp3lame';
    case OPUS = 'libopus';
    case VORBIS = 'libvorbis';
    case FLAC = 'flac';
    case AC3 = 'ac3';
    case EAC3 = 'eac3';
    case PCM_S16LE = 'pcm_s16le';
    case COPY = 'copy';
}
