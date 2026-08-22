<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Deux univers complets : Vera (recrutement) + Lumen (boutique). Club 205 retiré. */
class DualWorldsSeeder extends Seeder
{
    public function run(): void
    {
        $this->forgetClub205();
        DB::table('nodes')->update(['featured' => 0]);
        $this->vera();
        $this->lumen();
    }

    private function forgetClub205(): void
    {
        $id = 'club205';
        foreach (['node_tabs', 'products', 'media', 'wiki_pages', 'articles', 'drive_files', 'cck_fields', 'node_arcs', 'quests', 'crowd_goals', 'node_seo', 'node_i18n'] as $t) {
            if (DB::getSchemaBuilder()->hasTable($t)) {
                DB::table($t)->where('node_id', $id)->delete();
            }
        }
        DB::table('threads')->where('node_id', $id)->delete();
        DB::table('edges')->where('from_id', $id)->orWhere('to_id', $id)->delete();
        DB::table('nodes')->where('id', $id)->orWhere('slug', 'club-205')->delete();
    }

    private function node(array $row): void
    {
        $row += ['subtitle' => '', 'body' => '', 'hero' => '/realms/studio-hero.jpg', 'skin' => 'living', 'featured' => 0, 'template' => ''];
        DB::table('nodes')->updateOrInsert(['id' => $row['id']], $row);
    }

    private function tabs(string $id, array $rows): void
    {
        DB::table('node_tabs')->where('node_id', $id)->delete();
        foreach ($rows as $i => $r) {
            DB::table('node_tabs')->insert([
                'node_id' => $id, 'key' => $r[0], 'label' => $r[1], 'icon' => 'spark', 'sort' => $i,
                'color' => $r[2] ?? '#38bdf8', 'seo_title' => $r[1].' — '.($r[3] ?? ''), 'seo_desc' => $r[4] ?? '',
            ]);
        }
    }

    private function vera(): void
    {
        $id = 'vera';
        $this->node([
            'id' => $id, 'slug' => 'vera', 'kind' => 'company', 'title' => 'Vera',
            'subtitle' => 'Recrutement expérientiel',
            'summary' => 'Pas un jobboard. Salon, arbre de compétences, 7 épreuves, Passport CV. JobPosting transparent.',
            'hero' => '/realms/studio-hero.jpg', 'skin' => 'vera', 'featured' => 1, 'template' => 'vera-tech',
        ]);
        $this->tabs($id, [
            ['maison', 'Campus', '#38bdf8', 'Vera', 'Maison de recrutement expérientiel.'],
            ['salon', 'Salon', '#38bdf8', 'Vera', 'Stands, visio quand tu t’approches.'],
            ['arbre', 'Skill tree', '#38bdf8', 'Vera', 'Offres en constellations, pas une liste.'],
            ['offres', 'Offres', '#38bdf8', 'Vera', 'JobPosting : salaire, remote, épreuve.'],
            ['epreuve', 'Épreuves', '#38bdf8', 'Vera', '7 étapes configurables. Pas un CV.'],
            ['forum', 'Forum', '#38bdf8', 'Vera', 'Holo-forum candidats × recruteurs.'],
            ['journal', 'Magazine', '#38bdf8', 'Vera', 'Playbooks métier, FAQPage.'],
            ['videos', 'Vidéos', '#38bdf8', 'Vera', 'Épreuves filmées, holo-fiches.'],
            ['guides', 'Académie', '#38bdf8', 'Vera', 'HowTo onboarding.'],
            ['guilde', 'Équipe', '#38bdf8', 'Vera', 'Trombinoscope, grades.'],
        ]);
        DB::table('node_seo')->updateOrInsert(['node_id' => $id], [
            'title' => 'Vera — recrutement expérientiel | Geniuspace',
            'description' => 'Offres en quêtes, skill tree, 7 épreuves, Passport. LinkedIn ne peut pas importer ça.',
        ]);
        $jobs = [
            ['vera-gd', 'vera-lead-game-designer', 'Lead Game Designer', 'Remote EU · 65–80k · épreuve Culture fit'],
            ['vera-fe', 'vera-frontend-craft', 'Frontend Craft', 'Paris / remote · 55–70k · épreuve UI en 48h'],
            ['vera-pm', 'vera-product-narratif', 'Product narratif', 'Hybrid · 60–75k · étude de cas salon'],
            ['vera-ops', 'vera-ops-campus', 'Ops campus', 'Paris · 42–50k · quête J1'],
        ];
        DB::table('edges')->where('from_id', $id)->delete();
        foreach ($jobs as $j) {
            $this->node([
                'id' => $j[0], 'slug' => $j[1], 'kind' => 'job', 'title' => $j[2],
                'subtitle' => 'Offre Vera', 'summary' => $j[3],
                'hero' => '/realms/studio-hero.jpg', 'skin' => 'vera', 'template' => 'vera-tech',
            ]);
            DB::table('edges')->insert(['from_id' => $id, 'to_id' => $j[0], 'kind' => 'parent_of', 'label' => 'Offre']);
        }
        DB::table('quests')->where('node_id', $id)->delete();
        $qs = [
            [1, 'Culture fit', 'alignement', 'Un collègue publie un lore faux en public. Vous…', 'Corrigez en public, sources.', 'Message privé + source Drive.'],
            [2, 'Dossier Drive', 'preuve', 'Le PDF de l’épreuve est locké. Vous…', 'Passez l’étape 1 pour le jeton.', 'Demandez un accès modo.'],
            [3, 'Cas salon', 'récit', 'Un candidat freeze dans le salon 2.5D. Vous…', 'Ouvrez la visio automatique.', 'Laissez le stand vide.'],
            [4, 'Skill tree', 'stack', 'Le profil n’a pas le nœud “systems”. Vous…', 'Proposez la quête systems.', 'Rejetez.'],
            [5, 'Paie transparente', 'éthique', 'Le candidat demande la fourchette. Vous…', 'Affichez 65–80k dans le JobPosting.', '« Selon profil ».'],
            [6, 'Passport', 'confiance', 'Il coche 12 guides validés d’un autre Node. Vous…', 'Les compter comme reliques CV.', 'Ignorer, LinkedIn only.'],
            [7, 'Offre', 'close', 'Il réussit. Vous…', 'Contrat + split d’onboarding.', 'Ghost.'],
        ];
        foreach ($qs as $q) {
            DB::table('quests')->insert(['node_id' => $id, 'step' => $q[0], 'title' => $q[1], 'skill' => $q[2], 'prompt' => $q[3], 'option_a' => $q[4], 'option_b' => $q[5]]);
        }
        DB::table('node_arcs')->where('node_id', $id)->delete();
        foreach (['Candidature', 'Épreuve 1–3', 'Épreuve 4–6', 'Offre'] as $i => $l) {
            DB::table('node_arcs')->insert(['node_id' => $id, 'label' => $l, 'ord' => $i + 1]);
        }
        DB::table('media')->where('node_id', $id)->delete();
        DB::table('media')->insert([
            ['node_id' => $id, 'title' => 'Épreuve Culture fit — teaser', 'path' => 'media/orion.mp4', 'mode' => 'interview', 'access' => 'freemium', 'teaser_sec' => 8, 'price' => '', 'duration' => '00:18', 'chapters' => "00:00 — Maison\n00:08 — Cas (premium)", 'transcript' => 'Teaser. Le dossier Drive s’ouvre à l’étape 2.', 'kind' => 'video', 'views' => 410, 'rating' => '4.8'],
        ]);
        DB::table('threads')->updateOrInsert(['id' => 'th-vera-1'], [
            'node_id' => $id, 'kind' => 'forum', 'title' => 'L’épreuve 4 est-elle trop dure ?',
            'author' => 'Camille', 'body' => 'On parle skill tree, pas LeetCode. Voir @lead-game-designer.',
            'cover' => '/realms/studio-hero.jpg', 'views' => 220, 'fires' => 9, 'replies_count' => 2,
        ]);
        DB::table('replies')->where('thread_id', 'th-vera-1')->delete();
        DB::table('replies')->insert([
            ['thread_id' => 'th-vera-1', 'author' => 'Noah', 'body' => 'Sans le Passport, c’est encore LinkedIn.', 'votes' => 14, 'badge' => 'Recruteur', 'product_id' => '', 'file_title' => '', 'file_path' => '', 'file_locked' => 0, 'video_title' => '', 'video_path' => '', 'video_meta' => ''],
        ]);
        DB::table('wiki_pages')->updateOrInsert(['node_id' => $id, 'title' => 'Bible recruteur'], [
            'body' => 'Sept étapes. JobPosting visible. Pas de « selon profil ».',
        ]);
        $now = now();
        DB::table('articles')->updateOrInsert(['id' => 'art-vera-1'], [
            'node_id' => $id, 'slug' => 'recrutement-epreuves-pas-cv',
            'title' => 'Recrutement par épreuves : pourquoi le CV est mort',
            'theme' => 'Métier', 'dossier' => 'Dossier métier',
            'resume' => 'Remplacer le CV par 7 micro-quêtes. JobPosting transparent, Passport cross-node, skill tree.',
            'body' => "Vera n’est pas un ATS. C’est un campus. L’offre @lead-game-designer s’ouvre en quête.\n\nPublic : recruteurs, C-level, candidats senior.",
            'definition_term' => 'Épreuve Vera',
            'definition' => 'Scénario interactif noté, lié à un JobPosting. Le sac à dos (reliques d’autres Nodes) compte.',
            'toc' => "Pourquoi le CV ment\nLes 7 étapes\nPassport\nFAQ",
            'longtail' => "recrutement par épreuve|Ce guide\njob board rpg|Salon Vera\nats skill tree|Arbre campus",
            'faq' => "C’est du gamification gadget ?||Non. Chaque étape alimente le JobPosting et le graphe.\nLinkedIn peut importer le Passport ?||Non. C’est le moat.",
            'cover' => '/realms/studio-hero.jpg', 'video_path' => 'media/orion.mp4',
            'author' => 'Camille', 'author_role' => 'Head of Talent', 'reading_min' => 8, 'views' => 90,
            'published_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('drive_files')->updateOrInsert(['node_id' => $id, 'title' => 'Dossier épreuve.pdf'], [
            'path' => '/realms/studio-hero.jpg', 'kind' => 'pdf', 'locked' => 1,
        ]);
    }

    private function lumen(): void
    {
        $id = 'lumen';
        $this->node([
            'id' => $id, 'slug' => 'lumen', 'kind' => 'boutique_expert', 'title' => 'Lumen',
            'subtitle' => 'Galerie hologramme',
            'summary' => 'Boutique où chaque œuvre a une holo-fiche, un certificat RWA, un making-of, un split auteurs. Pas Shopify.',
            'hero' => '/realms/actor-hero.jpg', 'skin' => 'living', 'featured' => 1, 'template' => 'galerie-rwa',
        ]);
        $this->tabs($id, [
            ['vivre', 'Univers', '#c4b5fd', 'Lumen', 'Entrée de la galerie.'],
            ['boutique_expert', 'Vitrine', '#c4b5fd', 'Lumen', 'Œuvres, prix, stock, Offer.'],
            ['videos', 'Making-of', '#c4b5fd', 'Lumen', 'Holo-fiches vidéo, chapitres, loot.'],
            ['journal', 'Magazine', '#c4b5fd', 'Lumen', 'Lookbook indexable.'],
            ['forum', 'Salon', '#c4b5fd', 'Lumen', 'Collectionneurs, Legacy SEO.'],
            ['gallery', 'Cimaises', '#c4b5fd', 'Lumen', 'ImageGallery EXIF.'],
            ['guides', 'Certificats', '#c4b5fd', 'Lumen', 'HowTo authenticité.'],
            ['classifieds', 'Secondaire', '#c4b5fd', 'Lumen', 'Reventes Offer + geo.'],
        ]);
        DB::table('node_seo')->updateOrInsert(['node_id' => $id], [
            'title' => 'Lumen — galerie hologramme | Geniuspace',
            'description' => 'Œuvres uniques, holo-vidéo, RWA, split auteurs. Shopify n’a pas le graphe.',
        ]);
        DB::table('products')->where('node_id', $id)->delete();
        $ps = [
            ['p-lu-1', 'Cristal Lumen #01', '2 400 €', 'Pièce unique, certificat RWA.', 'rwa', '1', '/realms/actor-hero.jpg', 'Paris'],
            ['p-lu-2', 'Print nocturne 40×60', '180 €', 'Tirage 25. Split 70/30 graveur.', 'print', '8', '/realms/portal-hero.jpg', 'Paris'],
            ['p-lu-3', 'Relique audio (wav)', '90 €', 'Pay to download, jeton 24h.', 'audio', '∞', '/realms/sea-hero.jpg', ''],
            ['p-lu-4', 'Visite privée atelier', '320 €', 'Créneau, toast live sales.', 'service', '3', '/realms/studio-hero.jpg', 'Paris'],
        ];
        foreach ($ps as $p) {
            DB::table('products')->insert([
                'id' => $p[0], 'node_id' => $id, 'title' => $p[1], 'price' => $p[2], 'summary' => $p[3],
                'kind' => $p[4], 'rating' => '4.9', 'votes' => 18, 'stock' => $p[5], 'rwa' => $p[4] === 'rwa' ? 1 : 0,
                'energy' => 80, 'image' => $p[6], 'city' => $p[7],
            ]);
        }
        DB::table('crowd_goals')->updateOrInsert(['node_id' => $id], [
            'target' => 12000, 'current' => 7400, 'reward' => 'Croquis secret pour tous les mécènes',
        ]);
        DB::table('media')->where('node_id', $id)->delete();
        DB::table('media')->insert([
            ['node_id' => $id, 'title' => 'Making-of Cristal #01', 'path' => 'media/atelier.mp4', 'mode' => 'shop', 'access' => 'freemium', 'teaser_sec' => 6, 'price' => '15 €', 'duration' => '00:16', 'chapters' => "00:00 — Atelier\n00:08 — Pièce (premium)", 'transcript' => 'Teaser. La relique se débloque à l’achat.', 'kind' => 'video', 'views' => 640, 'rating' => '4.9'],
        ]);
        DB::table('threads')->updateOrInsert(['id' => 'th-lu-1'], [
            'node_id' => $id, 'kind' => 'forum', 'title' => 'Le Cristal #01 vaut-il 2 400 € ?',
            'author' => 'Inès', 'body' => 'Holo-fiche + certificat. Voir @p-lu-1.',
            'cover' => '/realms/actor-hero.jpg', 'views' => 110, 'fires' => 7, 'replies_count' => 1,
        ]);
        DB::table('replies')->where('thread_id', 'th-lu-1')->delete();
        DB::table('replies')->insert([
            ['thread_id' => 'th-lu-1', 'author' => 'Marc', 'body' => 'Le making-of justifie le prix. Relique @p-lu-1.', 'votes' => 11, 'badge' => 'Mécène', 'product_id' => 'p-lu-1', 'file_title' => 'Certificat RWA.pdf', 'file_path' => '/realms/actor-hero.jpg', 'file_locked' => 0, 'video_title' => 'Making-of Cristal #01', 'video_path' => 'media/atelier.mp4', 'video_meta' => '2 chapitres · Drive'],
        ]);
        $now = now();
        DB::table('articles')->updateOrInsert(['id' => 'art-lu-1'], [
            'node_id' => $id, 'slug' => 'galerie-hologramme-vs-shopify',
            'title' => 'Pourquoi une galerie hologramme enterre Shopify',
            'theme' => 'Métier', 'dossier' => 'Dossier métier',
            'resume' => 'Une œuvre = VisualArtwork + Offer + holo-vidéo + split. La grille produit ne peut pas.',
            'body' => "Lumen n’est pas une boutique. C’est une cimaise. @p-lu-1 a une URL, un certificat, un making-of.",
            'definition_term' => 'Holo-fiche',
            'definition' => 'Landing d’une œuvre : chapitres, loot temporel, Drive, auteur, panier. YouTube n’a pas le graphe.',
            'toc' => "Grille vs cimaise\nRWA\nSplit\nFAQ",
            'longtail' => "galerie rwa|Vitrine Lumen\nboutique hologramme|Ce guide\nsplit payment artistes|Produit @p-lu-2",
            'faq' => "C’est un NFT ?||Certificat, pas une spéculation. Le fichier reste chez toi.\nShopify peut faire ça ?||Pas le graphe, pas le forum Legacy, pas le split natif.",
            'cover' => '/realms/actor-hero.jpg', 'video_path' => 'media/atelier.mp4',
            'author' => 'Inès', 'author_role' => 'Galerie', 'reading_min' => 7, 'views' => 70,
            'published_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('wiki_pages')->updateOrInsert(['node_id' => $id, 'title' => 'Lire un certificat RWA'], [
            'body' => 'Chaque pièce unique porte un identifiant. La holo-fiche le montre.',
        ]);
        DB::table('drive_files')->updateOrInsert(['node_id' => $id, 'title' => 'Certificat RWA.pdf'], [
            'path' => '/realms/actor-hero.jpg', 'kind' => 'pdf', 'locked' => 0,
        ]);
        DB::table('drive_files')->updateOrInsert(['node_id' => $id, 'title' => 'Cimaise 01.jpg'], [
            'path' => '/realms/actor-hero.jpg', 'kind' => 'image', 'locked' => 0,
        ]);
    }
}
