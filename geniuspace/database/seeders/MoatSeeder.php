<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MoatSeeder extends Seeder
{
    public function run(): void
    {
        $n = DB::table('nodes')->where('slug', 'one-piece')->first();
        if (! $n) {
            return;
        }
        $id = $n->id;
        DB::table('node_arcs')->where('node_id', $id)->delete();
        foreach ([1 => 'East Blue', 2 => 'Alabasta', 3 => 'Skypiea', 4 => 'Marineford', 5 => 'Wano'] as $o => $lab) {
            DB::table('node_arcs')->insert(['node_id' => $id, 'ord' => $o, 'label' => $lab]);
        }
        DB::table('nodes')->where('slug', 'chopper')->update(['appear_order' => 3, 'appear_label' => 'Skypiea+']);
        DB::table('products')->where('id', 'sp-op-1')->update(['appear_order' => 4]);
        if (! DB::table('bounties')->where('node_id', $id)->exists()) {
            DB::table('bounties')->insert([
                'node_id' => $id,
                'keyword' => 'Volonté du D',
                'reward' => 500,
                'title_reward' => 'Expert Lore',
                'status' => 'open',
            ]);
        }
    }
}
