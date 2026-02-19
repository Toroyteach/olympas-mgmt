<?php

namespace App\Enums;

enum ImageAspectRatio: string
{
    case Square = 'square';
    case Landscape = 'landscape';
    case Portrait = 'portrait';

    public function label(): string
    {
        return match ($this) {
            self::Square => 'Square (1:1)',
            self::Landscape => 'Landscape (16:9)',
            self::Portrait => 'Portrait (9:16)',
        };
    }
}
