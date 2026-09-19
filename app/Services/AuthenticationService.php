<?php

namespace App\Services;

use App\Actions\InvalidateJwtAction;
use App\Actions\LoginUserAction;
use App\Actions\RegisterUserAction;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthenticationService
{
    public function __construct(
        private LoginUserAction $loginUser,
        private RegisterUserAction $registerUser,
        private InvalidateJwtAction $invalidateJwt
    ) {}



    public function register(array $attributes): array
    {
        $user = $this->registerUser->execute($attributes);

        return [
            'user'       => $user,
            'token'      => auth('api')->login($user),
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

    public function authenticate(string $email, string $password): array
    {
        $user = $this->loginUser->execute($email, $password);

        return [
            'user'       => $user,
            'token'      => auth('api')->login($user),
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ];
    }

    public function invalidateCurrentToken(): void
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $this->invalidateJwt->execute($guard);
    }
}
