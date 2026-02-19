<?php

namespace App\Enums;

enum ImageQuality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low (Faster)',
            self::Medium => 'Medium',
            self::High => 'High (Slower)',
        };
    }
}
