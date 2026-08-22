<?php

namespace App\Support;

use App\Models\User;

class Faces
{
    public static function of(string $name): string
    {
        $u = User::query()->where('name', $name)->first();
        if ($u && $u->avatar) {
            return $u->avatar;
        }
        $pool = ['/realms/luffy.jpg', '/realms/zoro.jpg', '/realms/nami.jpg', '/realms/sanji.jpg', '/realms/chopper.jpg', '/realms/actor-hero.jpg'];
        return $pool[abs(crc32($name)) % count($pool)];
    }
}
