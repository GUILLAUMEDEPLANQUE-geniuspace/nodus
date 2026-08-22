<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** UN club : 205. 3 salles, 20 fiches vides, 5 bounties. Le reste = bientôt. */
class PilotSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('nodes')->update(['featured' => 0]);
        $id = 'club205';
        if (! DB::table('nodes')->where('id', $id)->exists()) {
            DB::table('nodes')->insert([
                'id' => $id, 'slug' => 'club-205', 'kind' => 'auto', 'title' => 'Club 205',
                'subtitle' => 'Le garage. Pas Facebook.',
                'summary' => 'Fiches voitures, forum d’entraide, pièces à Reims. Google lit chaque page.',
                'body' => '', 'hero' => '/realms/sea-hero.jpg', 'skin' => 'living', 'featured' => 1,
            ]);
        } else {
            DB::table('nodes')->where('id', $id)->update(['featured' => 1, 'kind' => 'auto', 'title' => 'Club 205']);
        }
        DB::table('nodes')->where('id', $id)->update(['host' => '205']);

        DB::table('node_tabs')->where('node_id', $id)->delete();
        foreach ([['forum', 'Parler', 1], ['personnages', 'Les voitures', 2], ['classifieds', 'Pièces & annonces', 3]] as $i => $t) {
            DB::table('node_tabs')->insert(['node_id' => $id, 'key' => $t[0], 'label' => $t[1], 'icon' => 'spark', 'sort' => $t[2], 'color' => '#c45c26']);
        }

        $fiches = [
            ['peugeot-205-gti', 'Peugeot 205 GTI', 'car', 'La fiche mère'],
            ['peugeot-205-gti-16', '205 GTI 1.6', 'car', 'Phase 1'],
            ['peugeot-205-gti-19', '205 GTI 1.9', 'car', 'Phase 2'],
            ['peugeot-205-rallye', '205 Rallye', 'car', ''],
            ['peugeot-205-cti', '205 CTI', 'car', ''],
            ['peugeot-205-d-turbo', '205 D Turbo', 'car', ''],
            ['205-phase-1', '205 Phase 1', 'spec', ''],
            ['205-phase-2', '205 Phase 2', 'spec', ''],
            ['205-kit-rallye', 'Kit rallye 205', 'spec', ''],
            ['joint-culasse-205-gti', 'Joint de culasse 205 GTI', 'part', 'Requête longue'],
            ['distribution-205', 'Distribution 205', 'part', ''],
            ['train-avant-205-gti', 'Train avant 205 GTI', 'part', ''],
            ['jantes-speedline-205', 'Jantes Speedline 205', 'part', ''],
            ['sieges-peugeot-205', 'Sièges Peugeot 205', 'part', ''],
            ['echappement-205-gti', 'Échappement 205 GTI', 'part', ''],
            ['meet-reims-205', 'Meet Reims 205', 'event', 'SEO local'],
            ['meet-alsace-205', 'Meet Alsace 205', 'event', ''],
            ['205-gris-graphite', '205 Gris Graphite', 'car', ''],
            ['205-rouge-vallelunga', '205 Rouge Vallelunga', 'car', ''],
            ['boite-be3-205', 'Boîte BE3 205', 'part', ''],
        ];
        foreach ($fiches as $f) {
            if (! DB::table('nodes')->where('id', $f[0])->exists()) {
                DB::table('nodes')->insert([
                    'id' => $f[0], 'slug' => $f[0], 'kind' => $f[2], 'title' => $f[1],
                    'subtitle' => $f[3], 'summary' => '', 'body' => '',
                    'hero' => '/realms/sea-hero.jpg', 'skin' => 'living', 'featured' => 0,
                ]);
            }
            DB::table('edges')->where('from_id', $id)->where('to_id', $f[0])->delete();
            DB::table('edges')->insert(['from_id' => $id, 'to_id' => $f[0], 'kind' => 'parent_of', 'label' => $f[2]]);
        }
        $long = [
            'joint-culasse-205-gti' => 'Changement joint de culasse 205 GTI 1.9 à Reims : couple, joints, erreurs fréquentes. Guide club, pas un forum mort.',
            'peugeot-205-gti-19' => 'Peugeot 205 GTI 1.9 : cotes, trains, différences Phase 2. Fiche vide — le club l’habille.',
            'meet-reims-205' => 'Rassemblement 205 à Reims : parking, horaire, pièces à vendre sur place.',
            'train-avant-205-gti' => 'Géométrie train avant 205 GTI : valeurs club, silent-blocs, triangles.',
            '205-phase-1' => '205 Phase 1 vs Phase 2 : optiques, planche, trains. Sans spoil tuning tardif.',
            'peugeot-205-gti' => 'Fiche mère Peugeot 205 GTI — graphe vers 1.6, 1.9, Rallye, pièces.',
            'distribution-205' => 'Kit distribution 205 essence : intervalle, marque, couple poulie.',
            '205-kit-rallye' => 'Kit rallye 205 : ce qui est homologué, ce que le club refuse en meet.',
            'jantes-speedline-205' => 'Speedline 205 GTI : déport, pneus, erreurs d’offset.',
            'boite-be3-205' => 'Boîte BE3 205 GTI : rapports, synchros, fuite d’huile — à documenter.',
        ];
        foreach ($long as $nid => $desc) {
            DB::table('node_seo')->updateOrInsert(['node_id' => $nid], [
                'title' => DB::table('nodes')->where('id', $nid)->value('title').' | Club 205',
                'description' => $desc,
                'keywords' => '205 gti, peugeot 205, '.$nid,
                'noindex' => 0,
            ]);
        }
        DB::table('node_seo')->updateOrInsert(['node_id' => $id], [
            'title' => 'Club 205 — garage, fiches, pièces Reims',
            'description' => 'Club Peugeot 205 : fiches GTI, joint de culasse, meets Reims. Chaque voiture une page. Pas un groupe Facebook.',
            'keywords' => 'peugeot 205 gti, club 205, joint culasse 205, meet reims',
            'noindex' => 0,
        ]);

        DB::table('node_arcs')->where('node_id', $id)->delete();
        foreach ([1 => 'Ma 205 est Phase 1', 2 => '1.9 / Phase 2', 3 => 'Kit rallye'] as $o => $lab) {
            DB::table('node_arcs')->insert(['node_id' => $id, 'ord' => $o, 'label' => $lab]);
        }
        DB::table('nodes')->where('id', '205-kit-rallye')->update(['appear_order' => 3]);
        DB::table('nodes')->where('id', 'peugeot-205-gti-19')->update(['appear_order' => 2]);

        DB::table('bounties')->where('node_id', $id)->delete();
        foreach ([
            ['Changement joint de culasse 205 GTI', 800, 'Expert culasse'],
            ['Régler les trains 205 GTI 1.9', 500, 'Expert trains'],
            ['Différencier Phase 1 et Phase 2', 400, 'Expert carrosserie'],
            ['Préparer un meet à Reims', 400, 'Capitaine de meet'],
            ['Diagnostic allumage 205 GTI', 500, 'Expert allumage'],
        ] as $b) {
            DB::table('bounties')->insert([
                'node_id' => $id, 'keyword' => $b[0], 'reward' => $b[1],
                'title_reward' => $b[2], 'status' => 'open',
            ]);
        }

        if (! DB::table('products')->where('id', 'p-205-joint')->exists()) {
            DB::table('products')->insert([
                'id' => 'p-205-joint', 'node_id' => $id,
                'title' => 'Joint de culasse 205 GTI 1.9', 'price' => '48 €',
                'summary' => 'Pièce à Reims, dimanche.', 'kind' => 'piece',
                'rating' => '0', 'votes' => 0, 'stock' => '2', 'rwa' => 0,
                'energy' => 20, 'image' => '/realms/sea-hero.jpg',
            ]);
        }
        DB::table('products')->where('id', 'p-205-joint')->update(['city' => 'Reims', 'lat' => 49.2583, 'lng' => 4.0317]);

        if (! DB::table('threads')->where('id', 'th-205-1')->exists()) {
            DB::table('threads')->insert([
                'id' => 'th-205-1', 'node_id' => $id, 'kind' => 'forum',
                'title' => 'Joint de culasse 205 GTI 1.9 à Reims — retours',
                'author' => 'Club', 'body' => 'Qui a déjà changé le joint de culasse 205 GTI à Reims ? La fiche @joint-culasse-205-gti est vide. @p-205-joint est en stock.',
                'cover' => '/realms/sea-hero.jpg',
            ]);
        }

        $uid = DB::table('users')->value('id');
        if ($uid) {
            DB::table('node_staff')->updateOrInsert(
                ['node_id' => $id, 'user_id' => $uid],
                ['role' => 'owner']
            );
        }
    }
}
