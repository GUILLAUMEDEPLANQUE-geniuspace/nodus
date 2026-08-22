<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(GeniuspaceSeeder::class);
        $this->call(ForumVideoSeeder::class);
        $this->call(PlatformSeeder::class);
        $this->call(GodSeeder::class);
        $this->call(MoatSeeder::class);
        $this->call(PilotSeeder::class);
        $this->call(ShowcaseSeeder::class);
    }
}
