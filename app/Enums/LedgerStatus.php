<?php

namespace App\Enums;

enum LedgerStatus: string
{
    case REVOKED = 'revoked';
    case REFUND = 'refund';
    case PENDING = 'pending';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
