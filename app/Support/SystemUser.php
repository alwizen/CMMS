<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SystemUser
{
    public const EMAIL = 'system@internal.local';

    public static function id(): int
    {
        $user = User::query()->firstOrCreate(
            ['email' => static::EMAIL],
            [
                'name' => 'System',
                'password' => Str::random(40),
                'status' => true,
            ],
        );

        if (! $user->roles()->exists() && Role::query()->where('name', 'super_admin')->exists()) {
            $user->assignRole('super_admin');
        }

        return $user->id;
    }
}
