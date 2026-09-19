<?php

namespace App\Enums;

enum TicketStatus: string
{
    case REVOKED = 'revoked';
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
