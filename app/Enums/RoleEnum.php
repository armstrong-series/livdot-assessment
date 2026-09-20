<?php

namespace App\Enums;

enum RoleEnum: string
{

    case ADMIN = 'admin';
    case HOST = 'host';
    case VIEWER = 'viewer';
    case CREW = 'crew';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
