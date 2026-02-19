<?php

namespace App\Enums;

enum ContentType: string
{
    case Text = 'text';
    case Image = 'image';
    case Video = 'video';
    case Carousel = 'carousel';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text Only',
            self::Image => 'Image',
            self::Video => 'Video',
            self::Carousel => 'Carousel (Multiple Images)',
        };
    }
}
