<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Enums\RoleEnum;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate(
                ['name' => $role->value]
            );
        }
    }
}
