<?php

namespace App\Enums;

enum LedgerEntryType: string
{

    case REFUND_RESERVE = 'refund_reserve';
    case TICKET_SALE = 'ticket_sale';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
