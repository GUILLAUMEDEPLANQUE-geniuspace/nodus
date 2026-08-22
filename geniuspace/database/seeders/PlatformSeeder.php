<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'creator@geniuspace.test'],
            [
                'name' => 'Créateur',
                'password' => Hash::make('geniuspace'),
                'bio' => 'Gardien des univers.',
                'avatar' => '/realms/luffy.jpg',
                'banner' => '/realms/sea-hero.jpg',
            ]
        );
        foreach (DB::table('nodes')->pluck('id') as $nid) {
            DB::table('node_staff')->insertOrIgnore([
                'node_id' => $nid,
                'user_id' => $user->id,
                'role' => 'owner',
            ]);
        }
        $living = ['vivre' => 'Univers', 'personnages' => 'Personnages', 'forum' => 'Forum', 'journal' => 'Journal', 'guilde' => 'Guilde', 'guides' => 'Guides', 'boutique' => 'Boutique', 'videos' => 'Vidéos', 'reliques' => 'Drive'];
        $vera = ['maison' => 'Maison', 'salon' => 'Salon', 'arbre' => 'Arbre', 'offres' => 'Offres', 'epreuve' => 'Quêtes', 'drive' => 'Drive', 'forum' => 'Forum', 'videos' => 'Vidéos'];
        foreach (DB::table('nodes')->get() as $n) {
            $tabs = $n->skin === 'vera' ? $vera : $living;
            $i = 0;
            foreach ($tabs as $k => $lab) {
                DB::table('node_tabs')->insert(['node_id' => $n->id, 'key' => $k, 'label' => $lab, 'icon' => 'spark', 'sort' => $i++]);
            }
        }
        DB::table('folders')->insert([
            ['node_id' => 'onepiece', 'parent_id' => null, 'title' => 'Reliques'],
            ['node_id' => 'onepiece', 'parent_id' => null, 'title' => 'Covers forum'],
            ['node_id' => 'onepiece', 'parent_id' => null, 'title' => 'Produits'],
            ['node_id' => 'orion', 'parent_id' => null, 'title' => 'Dossiers candidats'],
        ]);
        DB::table('cck_fields')->insert([
            ['node_id' => 'onepiece', 'name' => 'Arc', 'type' => 'text', 'value' => 'Marineford', 'target_kind' => 'node', 'target_id' => '', 'sort' => 0],
            ['node_id' => 'onepiece', 'name' => 'Prime', 'type' => 'text', 'value' => '3 000 000 000', 'target_kind' => 'node', 'target_id' => 'luffy', 'sort' => 1],
            ['node_id' => 'orion', 'name' => 'Remote', 'type' => 'bool', 'value' => '1', 'target_kind' => 'node', 'target_id' => '', 'sort' => 0],
        ]);
        for ($s = 1; $s <= 7; $s++) {
            $titles = [1 => 'Culture fit', 2 => 'Drive', 3 => 'Cas produit', 4 => 'Live salon', 5 => 'Références', 6 => 'Offre', 7 => 'Onboarding'];
            DB::table('ats_steps')->insert([
                'node_id' => 'orion',
                'step' => $s,
                'title' => $titles[$s],
                'prompt' => 'Étape '.$s.' configurable dans le Studio.',
            ]);
        }
        DB::table('forum_categories')->insert([
            ['node_id' => 'onepiece', 'title' => 'Lore'],
            ['node_id' => 'onepiece', 'title' => 'Théories'],
            ['node_id' => 'orion', 'title' => 'Recrutement'],
        ]);
        DB::table('playlists')->insert(['user_id' => $user->id, 'title' => 'Grand Line', 'share_slug' => 'grand-line']);
        $mid = DB::table('media')->where('node_id', 'onepiece')->value('id');
        if ($mid) {
            DB::table('playlist_items')->insert(['playlist_id' => 1, 'media_id' => $mid]);
        }
        DB::table('notifications')->insert([
            'user_id' => $user->id,
            'title' => 'Zoro a posté sur Marineford',
            'url' => '/n/one-piece?tab=forum',
            'read' => 0,
        ]);
        DB::table('node_seo')->insert([
            'node_id' => 'onepiece',
            'title' => 'One Piece — wiki, forum, boutique | Geniuspace',
            'description' => 'Univers habité. Forum Legacy indexé.',
            'keywords' => 'one piece, wiki, forum, marineford',
            'noindex' => 0,
        ]);
    }
}
