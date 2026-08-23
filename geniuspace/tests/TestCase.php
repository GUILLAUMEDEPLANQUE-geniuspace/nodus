<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsStaff(string $nodeId = 'lumen', string $role = 'owner'): static
    {
        $user = User::factory()->create();
        DB::table('node_staff')->insert([
            'node_id' => $nodeId,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $this->actingAs($user);
    }

    protected function asGuest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }
}
