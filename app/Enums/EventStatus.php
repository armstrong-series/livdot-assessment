<?php

namespace App\Enums;

enum EventStatus: string
{
    case SCHEDULED = 'scheduled';
    case DRAFT = 'draft';
    case LIVE = 'live';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
