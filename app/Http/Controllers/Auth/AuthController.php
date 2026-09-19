<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticateUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private AuthenticationService $authenticationService
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        return livdotResponse(
            $this->authenticationService->register($request->validatedAttributes()),
            201,
            'signup sucessfully!',
            true,
            app('url')->current(),
            [],
            'auth'
        );
    }

    public function authenticate(AuthenticateUserRequest $request): JsonResponse
    {
        return livdotResponse(
            $this->authenticationService->authenticate(
                $request->string('email')->toString(),
                $request->string('password')->toString()
            ),
            200,
            'authenticated!',
            true,
            app('url')->current(),
            [],
            'auth'
        );
    }

    public function logout(): JsonResponse
    {
        $this->authenticationService->invalidateCurrentToken();

        return livdotResponse([], 200, 'logout');
    }
}
