<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Services\RoleService;
use App\Enums\RoleEnum;

class HostUserSeeder extends Seeder
{
    public function __construct(
        private RoleService $roleService
    ) {}

    public function run(): void
    {
        $host = User::updateOrCreate(
            ['email' => 'host@livdot.io',],
            [
                'name'              => 'Joan Sanders',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->roleService->assignRole(
            $host,
            RoleEnum::HOST->value
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@livdot.io',],
            [
                'name'              => 'LIV DOT Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $this->roleService->assignRole(
            $admin,
            RoleEnum::ADMIN->value
        );
    }
}
