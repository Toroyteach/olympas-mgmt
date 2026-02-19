<?php

namespace App\Enums;

enum DispatchStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Published = 'published';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Queued => 'Queued',
            self::Published => 'Published',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Queued => 'warning',
            self::Published => 'success',
            self::Failed => 'danger',
        };
    }
}
