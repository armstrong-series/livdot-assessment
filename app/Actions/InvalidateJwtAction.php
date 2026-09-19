<?php

namespace App\Actions;

use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class InvalidateJwtAction
{
    public function execute(JWTGuard $guard): void
    {
        $guard->logout();
    }
}
