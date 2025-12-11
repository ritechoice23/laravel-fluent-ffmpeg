<?php

namespace Ritechoice23\FluentFFmpeg\Enums;

enum ContainerFormat: string
{
    case MP4 = 'mp4';
    case M4A = 'm4a';
    case M4V = 'm4v';
    case MP3 = 'mp3';
    case WEBM = 'webm';
    case OGG = 'ogg';
    case WAV = 'wav';
    case FLAC = 'flac';
    case MKV = 'mkv';
    case AVI = 'avi';
    case MOV = 'mov';
    case FLV = 'flv';
    case GIF = 'gif';

    /**
     * Get FFmpeg format name for this container
     */
    public function getFFmpegFormat(): string
    {
        return match ($this) {
            self::MP4, self::M4A, self::M4V => 'mp4',
            self::MP3 => 'mp3',
            self::WEBM => 'webm',
            self::OGG => 'ogg',
            self::WAV => 'wav',
            self::FLAC => 'flac',
            self::MKV => 'matroska',
            self::AVI => 'avi',
            self::MOV => 'mov',
            self::FLV => 'flv',
            self::GIF => 'gif',
        };
    }

    /**
     * Get format from file extension
     */
    public static function fromExtension(string $extension): ?self
    {
        $extension = strtolower(ltrim($extension, '.'));

        return match ($extension) {
            'mp4' => self::MP4,
            'm4a' => self::M4A,
            'm4v' => self::M4V,
            'mp3' => self::MP3,
            'webm' => self::WEBM,
            'ogg' => self::OGG,
            'wav' => self::WAV,
            'flac' => self::FLAC,
            'mkv' => self::MKV,
            'avi' => self::AVI,
            'mov' => self::MOV,
            'flv' => self::FLV,
            'gif' => self::GIF,
            default => null,
        };
    }

    /**
     * Check if this is an audio-only format
     */
    public function isAudioOnly(): bool
    {
        return in_array($this, [self::MP3, self::M4A, self::OGG, self::WAV, self::FLAC]);
    }

    /**
     * Check if this is a video format
     */
    public function isVideo(): bool
    {
        return ! $this->isAudioOnly();
    }
}
