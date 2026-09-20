<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class HostUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'host@livdot.io',
            ],
            [
                'name'              => 'Joan Sanders',
                'password'          => Hash::make('password'),
                'role'              => 'host',
                'email_verified_at' => now(),
            ]
        );
    }
}
