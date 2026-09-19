<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUserAction
{
    public function execute(array $attributes): User
    {
        return DB::transaction(fn (): User => User::create($attributes));
    }
}
