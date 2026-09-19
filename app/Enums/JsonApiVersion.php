<?php

namespace App\Enums;

enum JsonApiVersion: string
{
    case v1 = '1.1';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
