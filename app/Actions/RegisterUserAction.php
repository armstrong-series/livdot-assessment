<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\RoleService;

use App\Enums\RoleEnum;

class RegisterUserAction
{

    public function __construct(
        private RoleService $roleService
    ) {}


    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create(
                [
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                ]
            );

            $this->roleService->assignRole(
                $user,
                RoleEnum::VIEWER->value
            );

            return $user->refresh()->load('role');
        });
    }
}
