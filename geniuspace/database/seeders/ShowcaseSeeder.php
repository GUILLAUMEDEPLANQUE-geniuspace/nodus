<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Densifie Club 205 : toutes les salles, graphe, contenus, visuels. */
class ShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $id = 'club205';
        if (! DB::table('nodes')->where('id', $id)->exists()) {
            return;
        }
        DB::table('nodes')->where('id', $id)->update([
            'hero' => '/realms/205-garage.jpg',
            'subtitle' => 'Le garage. Indexé. Habité.',
            'summary' => 'Fiches 205, forum d’entraide, pièces à Reims, meets, guides. Chaque vis, chaque voiture, une page que Google lit.',
            'body' => 'Pas un groupe Facebook. Un lieu de vie : tu parles, tu documentes, tu vends, tu compares deux GTI côte à côte.',
            'template' => 'club-auto',
        ]);

        $rooms = [
            ['vivre', 'Univers', 0],
            ['forum', 'Forum', 1],
            ['personnages', 'Les voitures', 2],
            ['classifieds', 'Pièces', 3],
            ['videos', 'Essais', 4],
            ['guides', 'Guides', 5],
            ['agenda', 'Meets', 6],
            ['carte', 'Carte', 7],
            ['journal', 'Magazine', 8],
            ['guilde', 'Le club', 9],
            ['gallery', 'Photos', 10],
            ['reliques', 'Drive', 11],
            ['boutique', 'Boutique', 12],
            ['reviews', 'Essais & avis', 13],
            ['stories', 'Stories', 14],
        ];
        DB::table('node_tabs')->where('node_id', $id)->delete();
        foreach ($rooms as $t) {
            DB::table('node_tabs')->insert([
                'node_id' => $id, 'key' => $t[0], 'label' => $t[1], 'icon' => 'spark',
                'sort' => $t[2], 'color' => '#e85d04', 'bg' => '', 'animate' => $t[0] === 'forum',
                'seo_title' => $t[1].' — Club 205', 'seo_desc' => $t[1].' du Club Peugeot 205 GTI.',
            ]);
        }

        $img = [
            'peugeot-205-gti' => '/realms/205-gti.jpg',
            'peugeot-205-gti-16' => '/realms/205-dash.jpg',
            'peugeot-205-gti-19' => '/realms/205-gti.jpg',
            'peugeot-205-rallye' => '/realms/205-rallye.jpg',
            'peugeot-205-cti' => '/realms/205-gti.jpg',
            'peugeot-205-d-turbo' => '/realms/205-garage.jpg',
            '205-phase-1' => '/realms/205-dash.jpg',
            '205-phase-2' => '/realms/205-gti.jpg',
            '205-kit-rallye' => '/realms/205-rallye.jpg',
            'joint-culasse-205-gti' => '/realms/205-joint.jpg',
            'distribution-205' => '/realms/205-joint.jpg',
            'train-avant-205-gti' => '/realms/205-garage.jpg',
            'jantes-speedline-205' => '/realms/205-gti.jpg',
            'sieges-peugeot-205' => '/realms/205-dash.jpg',
            'echappement-205-gti' => '/realms/205-garage.jpg',
            'meet-reims-205' => '/realms/205-meet.jpg',
            'meet-alsace-205' => '/realms/205-meet.jpg',
            '205-gris-graphite' => '/realms/205-garage.jpg',
            '205-rouge-vallelunga' => '/realms/205-gti.jpg',
            'boite-be3-205' => '/realms/205-joint.jpg',
        ];
        $copy = [
            'peugeot-205-gti' => ['La fiche mère', 'Hot-hatch Peugeot 1984–1994. Graphe vers 1.6, 1.9, Rallye, pièces, meets. Ce n’est pas un post Facebook : c’est l’encyclopédie du club.'],
            'peugeot-205-gti-19' => ['XU9J2 · 130 ch', 'La 1.9 que tout le monde cherche. Trains, BE3, couple. Différences Phase 2. Comparer avec la 1.6.'],
            'peugeot-205-gti-16' => ['XU5J · 105/115 ch', 'La première. Plus légère. Phase 1. Le club la défend contre la mode 1.9.'],
            'peugeot-205-rallye' => ['1.3 · homologuée', 'Boîte courte, rayures, gravette. Pas une GTI déguisée.'],
            'joint-culasse-205-gti' => ['La panne qui rank', 'Changement joint de culasse 205 GTI 1.9 à Reims : couple, joints, erreurs. Guide à écrire — bounty ouverte.'],
            'peugeot-205-cti' => ['Cabriolet', '205 CTI : le GTI à ciel ouvert. Mêmes trains, autre vie.'],
            'peugeot-205-d-turbo' => ['Diesel sport', 'D Turbo. Couple bas. Le club la range à part des GTI.'],
            '205-phase-1' => ['Première peau', 'Optiques, planche, trains Phase 1. Sans spoil Phase 2.'],
            '205-phase-2' => ['Seconde peau', 'Pare-chocs, planche, feux. Comparer avec Phase 1.'],
            '205-kit-rallye' => ['Homologation', 'Ce que le club accepte en meet. Pas un sticker.'],
            'distribution-205' => ['Entretien', 'Intervalle kit distribution essence. Couple poulie.'],
            'train-avant-205-gti' => ['Géométrie', 'Valeurs club, silent-blocs, triangles 1.9.'],
            'jantes-speedline-205' => ['Speedline', 'Déport, pneus, erreurs d’offset.'],
            'sieges-peugeot-205' => ['Baquets', 'Références, ancrages, ce que le CT refuse.'],
            'echappement-205-gti' => ['Ligne', 'Diamètres, homologation, son du XU9.'],
            'meet-alsace-205' => ['Est', 'Rassemblement Alsace. Même primitive que Reims, autre geo.'],
            '205-gris-graphite' => ['Teinte', 'Gris Graphite d’origine. Fiche couleur, pas un Tumblr.'],
            '205-rouge-vallelunga' => ['Teinte', 'Rouge Vallelunga. La photo du garage.'],
            'meet-reims-205' => ['Dimanche · Reims', 'Parking, pièces à vendre, géo Offer. SEO local que Leboncoin rate.'],
            'boite-be3-205' => ['BE3', 'Rapports, synchros, fuite. Enfant de la 1.9.'],
        ];
        foreach ($img as $nid => $hero) {
            $row = ['hero' => $hero];
            if (isset($copy[$nid])) {
                $row['subtitle'] = $copy[$nid][0];
                $row['summary'] = $copy[$nid][1];
                $row['body'] = $copy[$nid][1];
            }
            DB::table('nodes')->where('id', $nid)->update($row);
        }

        DB::table('edges')->where('from_id', 'peugeot-205-gti')->delete();
        foreach (['peugeot-205-gti-16', 'peugeot-205-gti-19', 'peugeot-205-rallye', '205-rouge-vallelunga', 'joint-culasse-205-gti', 'jantes-speedline-205'] as $to) {
            DB::table('edges')->insert(['from_id' => 'peugeot-205-gti', 'to_id' => $to, 'kind' => 'parent_of', 'label' => 'variante']);
        }
        DB::table('edges')->where('from_id', 'peugeot-205-gti-19')->delete();
        foreach (['joint-culasse-205-gti', 'train-avant-205-gti', 'boite-be3-205', 'distribution-205'] as $to) {
            DB::table('edges')->insert(['from_id' => 'peugeot-205-gti-19', 'to_id' => $to, 'kind' => 'parent_of', 'label' => 'pièce']);
        }

        DB::table('cck_fields')->where('node_id', 'peugeot-205-gti-19')->delete();
        $ccks = [
            ['Cylindrée', 'text', '1905 cm³'],
            ['Puissance', 'text', '130 ch DIN'],
            ['Années', 'text', '1986–1994'],
            ['Boîte', 'text', 'BE3 5 rapports'],
            ['Poids', 'digits', '880'],
        ];
        DB::table('cck_fields')->where('node_id', 'peugeot-205-gti-16')->delete();
        foreach ([['Cylindrée', 'text', '1580 cm³'], ['Puissance', 'text', '115 ch DIN'], ['Années', 'text', '1984–1992'], ['Boîte', 'text', 'BE1 / BE3'], ['Poids', 'digits', '850']] as $i => $f) {
            DB::table('cck_fields')->insert(['node_id' => 'peugeot-205-gti-16', 'name' => $f[0], 'type' => $f[1], 'value' => $f[2], 'sort' => $i]);
        }
        DB::table('replies')->where('thread_id', 'th-205-1')->delete();
        DB::table('replies')->insert([
            [
                'thread_id' => 'th-205-1', 'author' => 'Marc', 'body' => 'Le couple de serrage n’est pas une opinion. 2.0 puis 2.2, dans l’ordre. J’ai mis l’analyse vidéo (chapitre 2) et la fiche @joint-culasse-205-gti.',
                'votes' => 86, 'badge' => 'Expert moteur', 'product_id' => '',
                'file_title' => '', 'file_path' => '', 'file_locked' => 0,
                'video_title' => 'Changement joint — making-of', 'video_path' => 'media/atelier.mp4', 'video_meta' => '3 chapitres · 00:16 · Drive club',
            ],
            [
                'thread_id' => 'th-205-1', 'author' => 'Léa', 'body' => 'Le PDF couple est locké premium. Le joint du club est en stock à Reims — @p-205-joint. Dimanche au parking.',
                'votes' => 41, 'badge' => 'Guilde Reims', 'product_id' => 'p-205-joint',
                'file_title' => 'Couple de serrage XU9.pdf', 'file_path' => '/realms/205-joint.jpg', 'file_locked' => 1,
                'video_title' => '', 'video_path' => '', 'video_meta' => '',
            ],
            [
                'thread_id' => 'th-205-1', 'author' => 'Tom', 'body' => 'D’accord avec Marc. Sans le maillage vers la fiche, ce sujet meurt dans un groupe Facebook.',
                'votes' => 12, 'badge' => '', 'product_id' => '',
                'file_title' => '', 'file_path' => '', 'file_locked' => 0,
                'video_title' => '', 'video_path' => '', 'video_meta' => '',
            ],
        ]);
        DB::table('threads')->where('id', 'th-205-1')->update(['replies_count' => 3, 'fires' => 24, 'views' => 856]);

        $threads = [
            ['th-205-1', 'Joint de culasse 205 GTI 1.9 à Reims — retours', 'Club', 'Qui a déjà changé le joint de culasse 205 GTI à Reims ? La fiche @joint-culasse-205-gti est encore trop mince. @p-205-joint est en stock.', '/realms/205-joint.jpg'],
            ['th-205-2', '1.6 vs 1.9 : on arrête de se mentir', 'Marc', 'La 1.6 est plus vive. La 1.9 tire. Comparer les fiches : 205 GTI 1.6 et 205 GTI 1.9.', '/realms/205-gti.jpg'],
            ['th-205-3', 'Meet Reims dimanche — qui amène des pièces ?', 'Léa', 'Parking habituel. Speedline, BE3, sièges. Géo sur la fiche Meet Reims 205.', '/realms/205-meet.jpg'],
            ['th-205-4', 'Rallye n’est pas une GTI', 'Tom', 'Homologation, 1.3, boîte. Lisez la fiche 205 Rallye avant de poster une annonce.', '/realms/205-rallye.jpg'],
            ['th-205-5', 'Phase 1 ou Phase 2, comment ne plus se faire avoir', 'Club', 'Optiques, planche, trains. Bounty ouverte sur le guide.', '/realms/205-dash.jpg'],
        ];
        foreach ($threads as $t) {
            DB::table('threads')->updateOrInsert(['id' => $t[0]], [
                'node_id' => $id, 'kind' => 'forum', 'title' => $t[1], 'author' => $t[2],
                'body' => $t[3], 'cover' => $t[4], 'views' => rand(40, 400), 'fires' => rand(2, 28),
            ]);
        }
        DB::table('threads')->updateOrInsert(['id' => 'th-205-j1'], [
            'node_id' => $id, 'kind' => 'blog', 'title' => 'Meet Reims — dimanche 10h, parking Cernay',
            'author' => 'Léa', 'body' => '20 voitures la dernière fois. Pièces à vendre dès 9h30. Fiche meet-reims-205.',
            'cover' => '/realms/205-meet.jpg', 'views' => 90,
        ]);
        DB::table('threads')->updateOrInsert(['id' => 'th-205-j2'], [
            'node_id' => $id, 'kind' => 'blog', 'title' => 'Le joint de culasse n’attend pas le printemps',
            'author' => 'Marc', 'body' => 'Suivi de la bounty SEO. Le guide n’existe nulle part en FR propre.',
            'cover' => '/realms/205-joint.jpg', 'views' => 70,
        ]);

        DB::table('wiki_pages')->where('node_id', $id)->delete();
        DB::table('wiki_pages')->insert([
            ['node_id' => $id, 'title' => 'Changer un joint de culasse 205 GTI 1.9', 'body' => 'Couple, ordre de serrage, joints. Page encore courte — bounty 800 coins. Mot-clé : joint de culasse 205 GTI Reims.'],
            ['node_id' => $id, 'title' => 'Phase 1 vs Phase 2', 'body' => 'Optiques, planche de bord, trains, pare-chocs. Sans le blabla forum.'],
            ['node_id' => $id, 'title' => 'Préparer un meet à Reims', 'body' => 'Autorisation parking, horaires, table de pièces, géo Offer.'],
        ]);

        $prods = [
            ['p-205-joint', 'Joint de culasse 205 GTI 1.9', '48 €', 'Pièce à Reims, dimanche.', 'piece', '/realms/205-joint.jpg', 'Reims'],
            ['p-205-speed', 'Jantes Speedline 205 (jeu)', '620 €', 'Offset club, 4 jantes.', 'piece', '/realms/205-gti.jpg', 'Reims'],
            ['p-205-be3', 'Boîte BE3 révisée', '890 €', 'Synchros neufs, facture.', 'piece', '/realms/205-joint.jpg', 'Reims'],
            ['p-205-tee', 'Tee Club 205', '29 €', 'Merch garage.', 'merch', '/realms/205-garage.jpg', ''],
        ];
        foreach ($prods as $p) {
            if (! DB::table('products')->where('id', $p[0])->exists()) {
                DB::table('products')->insert([
                    'id' => $p[0], 'node_id' => $id, 'title' => $p[1], 'price' => $p[2],
                    'summary' => $p[3], 'kind' => $p[4], 'rating' => '4.7', 'votes' => 12,
                    'stock' => '2', 'rwa' => 0, 'energy' => 20, 'image' => $p[5],
                ]);
            }
            DB::table('products')->where('id', $p[0])->update(['image' => $p[5], 'city' => $p[6], 'lat' => $p[6] ? 49.2583 : null, 'lng' => $p[6] ? 4.0317 : null]);
        }

        DB::table('media')->where('node_id', $id)->delete();
        DB::table('media')->insert([
            ['node_id' => $id, 'title' => 'Essai piste 205 GTI 1.9', 'path' => 'media/teaser.mp4', 'mode' => 'lore', 'access' => 'free', 'teaser_sec' => 0, 'price' => '', 'duration' => '02:10', 'chapters' => "00:00 — Départ\n01:00 — Trains", 'transcript' => 'Essai club. Chapitres → Clip schema.', 'kind' => 'video', 'views' => 210, 'rating' => '4.8'],
            ['node_id' => $id, 'title' => 'Changement joint — making-of', 'path' => 'media/atelier.mp4', 'mode' => 'shop', 'access' => 'freemium', 'teaser_sec' => 6, 'price' => '9 €', 'duration' => '00:16', 'chapters' => "00:00 — Banc\n00:08 — Couple (premium)", 'transcript' => 'Teaser. La suite + PDF Drive.', 'kind' => 'video', 'views' => 88, 'rating' => '4.6'],
        ]);

        DB::table('drive_files')->where('node_id', $id)->delete();
        DB::table('drive_files')->insert([
            ['node_id' => $id, 'title' => 'Couple de serrage XU9.pdf', 'path' => '/realms/205-joint.jpg', 'kind' => 'file', 'locked' => 0],
            ['node_id' => $id, 'title' => 'Photo meet Reims', 'path' => '/realms/205-meet.jpg', 'kind' => 'image', 'locked' => 0],
            ['node_id' => $id, 'title' => 'Plan garage (locké)', 'path' => '/realms/205-garage.jpg', 'kind' => 'image', 'locked' => 1],
        ]);

        if (DB::table('guild_messages')->where('node_id', $id)->count() === 0) {
            DB::table('guild_messages')->insert([
                ['node_id' => $id, 'author' => 'Léa', 'body' => 'Qui descend à Reims dimanche ?'],
                ['node_id' => $id, 'author' => 'Marc', 'body' => 'J’amène la 1.9 et le joint.'],
            ]);
        }

        DB::table('crowd_goals')->updateOrInsert(['node_id' => $id], [
            'target' => 2000, 'current' => 740, 'reward' => 'Banc d’essai pour le club',
        ]);

        DB::table('media')->where('node_id', $id)->update(['author_name' => 'Marc', 'author_avatar' => '/realms/205-dash.jpg']);
        if (DB::table('live_messages')->where('thread_id', 'th-205-1')->count() === 0) {
            DB::table('live_messages')->insert([
                ['thread_id' => 'th-205-1', 'author' => 'Tom', 'body' => 'J’arrive avec le couplemètre.'],
                ['thread_id' => 'th-205-1', 'author' => 'Léa', 'body' => 'Parking Cernay 9h30.'],
            ]);
        }
        DB::table('threads')->updateOrInsert(['id' => 'th-205-r1'], [
            'node_id' => $id, 'kind' => 'review', 'title' => 'Essai 1.9 Phase 2 — ce qui casse vraiment',
            'author' => 'Marc', 'body' => 'Trains, BE3, conso. Fiche @peugeot-205-gti-19.',
            'cover' => '/realms/205-gti.jpg', 'views' => 140,
        ]);
        DB::table('node_i18n')->updateOrInsert(['node_id' => 'peugeot-205-gti-19', 'locale' => 'en'], [
            'title' => 'Peugeot 205 GTI 1.9', 'summary' => 'XU9J2 130 hp. The 1.9 everyone wants. Club spec sheet.',
        ]);

        $now = now();
        DB::table('articles')->updateOrInsert(['id' => 'art-205-joint'], [
            'node_id' => $id,
            'slug' => 'joint-culasse-205-gti-reims',
            'title' => 'Joint de culasse 205 GTI 1.9 à Reims : couple, erreurs, pièce',
            'theme' => 'Technique',
            'dossier' => 'Dossier métier',
            'resume' => 'Page de référence pour changer un joint de culasse 205 GTI 1.9. Couple, ordre de serrage, pièce club à Reims, erreurs qui refont le joint en 800 km.',
            'body' => "Cette page tient la requête tête joint de culasse 205 GTI. Elle renvoie vers la fiche @joint-culasse-205-gti, la pièce @p-205-joint et le sujet forum.\n\nPublic : proprio 1.9, mécanos du club, membres Reims.\n\nLe couple n’est pas une opinion. 2.0 puis 2.2, dans l’ordre. Le PDF est locké premium dans le Drive. Dimanche, parking Cernay.",
            'definition_term' => 'Joint de culasse 205 GTI',
            'definition' => 'Joint d’étanchéité entre bloc et culasse du XU9J2. Une fuite mal posée = mélange eau/huile et joint à refaire.',
            'toc' => "Rôle du joint\nCouple et ordre\nPièce club à Reims\nErreurs fréquentes\nFAQ",
            'longtail' => "joint de culasse 205 gti 1.9|Fiche + ce guide\ncouple serrage culasse xu9|Drive locké\njoint culasse 205 reims|Pièce @p-205-joint + meet",
            'faq' => "Quel couple sur un XU9 1.9 ?||2.0 puis 2.2 dans l’ordre, à froid.\nFaut-il remplacer les vis ?||Oui. Les vis de culasse 205 ne se réutilisent pas.\nOù acheter à Reims ?||La relique club @p-205-joint, parking dimanche.",
            'cover' => '/realms/205-joint.jpg',
            'video_path' => 'media/atelier.mp4',
            'author' => 'Marc',
            'author_role' => 'Expert moteur',
            'reading_min' => 9,
            'views' => 320,
            'published_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('articles')->updateOrInsert(['id' => 'art-205-vs'], [
            'node_id' => $id,
            'slug' => '205-gti-16-vs-19',
            'title' => '205 GTI 1.6 vs 1.9 : le vrai écart',
            'theme' => 'Essais',
            'dossier' => 'Dossier métier',
            'resume' => 'Comparer les deux GTI sans folklore forum. Poids, couple, trains, ce que le club achète vraiment.',
            'body' => "La 1.6 est plus vive. La 1.9 tire. Voir les fiches et le comparateur.\n\nCluster : @peugeot-205-gti-16 et @peugeot-205-gti-19.",
            'definition_term' => 'XU9J2',
            'definition' => 'Moteur 1.9 130 ch DIN de la GTI Phase 2. Ce n’est pas un 1.6 préparé.',
            'toc' => "Poids\nCouple\nPièces\nFAQ",
            'longtail' => "205 gti 1.6 ou 1.9|Ce guide + /vs/\nxu9j2 vs xu5j|Fiches CCK",
            'faq' => "La 1.9 est-elle plus fiable ?||Non. Plus de couple, plus de trains à surveiller.\nPeut-on comparer sur Geniuspace ?||Oui : /n/club-205/vs/peugeot-205-gti-16/peugeot-205-gti-19",
            'cover' => '/realms/205-gti.jpg',
            'video_path' => 'media/teaser.mp4',
            'author' => 'Léa',
            'author_role' => 'Guilde Reims',
            'reading_min' => 6,
            'views' => 180,
            'published_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('articles')->updateOrInsert(['id' => 'art-205-meet'], [
            'node_id' => $id,
            'slug' => 'preparer-meet-reims-205',
            'title' => 'Préparer un meet 205 à Reims',
            'theme' => 'Meets',
            'dossier' => 'Dossier club',
            'resume' => 'Parking, horaires, table de pièces, géo Offer. Un meet indexable, pas un event Facebook.',
            'body' => "Fiche @meet-reims-205. Pièces en vitrine. Carte OSM du club.",
            'definition_term' => 'Meet club',
            'definition' => 'Rassemblement géolocalisé. Chaque édition a une page, pas un post éphémère.',
            'toc' => "Lieu\nPièces\nFAQ",
            'longtail' => "meet 205 reims|Fiche event + ce guide",
            'faq' => "C’est quand ?||Dimanche 10h, parking Cernay.\nOn vend des pièces ?||Oui, Offer + geo.",
            'cover' => '/realms/205-meet.jpg',
            'video_path' => '',
            'author' => 'Léa',
            'author_role' => 'Guilde Reims',
            'reading_min' => 5,
            'views' => 90,
            'published_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
