<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GodSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('media')->where('node_id', 'onepiece')->update([
            'author_name' => 'Nami',
            'author_avatar' => '/realms/nami.jpg',
            'author_role' => 'Cartographe · chaîne Grand Line',
        ]);
        DB::table('media')->where('node_id', 'atelier')->update([
            'author_name' => 'Créateur',
            'author_avatar' => '/realms/actor-hero.jpg',
            'author_role' => 'Artiste RWA',
        ]);
        DB::table('media')->where('node_id', 'orion')->update([
            'author_name' => 'Maya',
            'author_avatar' => '/realms/studio-hero.jpg',
            'author_role' => 'Recruteuse Orion',
        ]);
        DB::table('threads')->where('author', 'Zoro')->update(['author_avatar' => '/realms/zoro.jpg']);
        DB::table('threads')->where('author', 'Nami')->update(['author_avatar' => '/realms/nami.jpg']);
        DB::table('threads')->where('author', 'Usopp')->update(['author_avatar' => '/realms/sanji.jpg']);
        foreach (['onepiece', 'orion', 'atelier'] as $id) {
            if (! DB::table('spatial_nodes')->where('universe_id', $id)->where('kind', 'core')->exists()) {
                DB::table('spatial_nodes')->insert([
                    'universe_id' => $id,
                    'node_id' => $id,
                    'kind' => 'core',
                    'x' => 0, 'y' => 0, 'z' => 0,
                    'radius' => 0, 'angle' => 0, 'speed' => 0,
                ]);
            }
        }
    }
}
