<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Twitter = 'twitter';
    case LinkedIn = 'linkedin';
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';
    case Pinterest = 'pinterest';
    case Telegram = 'telegram';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Twitter => 'X / Twitter',
            self::LinkedIn => 'LinkedIn',
            self::Instagram => 'Instagram',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
            self::Pinterest => 'Pinterest',
            self::Telegram => 'Telegram',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Facebook => 'info',
            self::Twitter => 'gray',
            self::LinkedIn => 'info',
            self::Instagram => 'danger',
            self::TikTok => 'warning',
            self::YouTube => 'danger',
            self::Pinterest => 'danger',
            self::Telegram => 'info',
        };
    }

    public function supportsImages(): bool
    {
        return match ($this) {
            self::TikTok => false,
            default => true,
        };
    }

    public function supportsVideo(): bool
    {
        return match ($this) {
            self::Pinterest => false,
            default => true,
        };
    }
}
