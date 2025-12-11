<?php

namespace Ritechoice23\FluentFFmpeg\Enums;

enum VideoCodec: string
{
    case H264 = 'libx264';
    case H265 = 'libx265';
    case VP8 = 'libvpx';
    case VP9 = 'libvpx-vp9';
    case AV1 = 'libaom-av1';
    case MPEG4 = 'mpeg4';
    case THEORA = 'libtheora';
    case COPY = 'copy';
}
