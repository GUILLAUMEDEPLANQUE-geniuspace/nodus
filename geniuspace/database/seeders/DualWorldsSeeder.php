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
            'subtitle' => 'L’emploi, enfin lisible',
            'summary' => 'Jobboard indépendant. Conseil avant candidature, délai de réponse public, une page à la place du CV, test métier. Indeed n’a aucun intérêt à faire ça.',
            'hero' => '/offer/releve-atelier.jpg', 'skin' => 'vera', 'featured' => 1, 'template' => 'vera-tech',
        ]);
        $this->tabs($id, [
            ['home', 'Accueil', '#1b4332', 'Vera', 'L’emploi, enfin lisible.'],
            ['offres', 'Offres', '#1b4332', 'Offres', 'Salaire publié, test métier.'],
            ['europe', 'Europe', '#1b4332', 'Europe', 'La preuve avant le titre, aussi hors de France.'],
            ['preuve', 'Tests métier', '#1b4332', 'Tests', 'Geste 6 min, module si ça rate, on rejoue.'],
            ['carnet', 'Mon carnet', '#1b4332', 'Carnet', 'Preuves exportables, pas un CV généré.'],
            ['savoirs', 'Fiches', '#1b4332', 'Fiches', 'Guides métier liés aux offres.'],
            ['viviers', 'Profils oubliés', '#1b4332', 'Profils', 'Seniors, RSA, reprise.'],
            ['lexique', 'Lexique', '#1b4332', 'Lexique', 'Les mots, dits simplement.'],
            ['delais', 'Délais', '#1b4332', 'Délais', 'Elles répondent, ou ça se voit.'],
            ['tarif', 'Tarif entreprise', '#1b4332', 'Tarif', 'On paie un candidat qualifié, pas un clic.'],
            ['journal', 'Journal', '#1b4332', 'Journal', 'Carnets d’entreprises.'],
            ['reliques', 'Drive', '#1b4332', 'Drive', 'Visites et modes opératoires.'],
        ]);
        DB::table('node_seo')->updateOrInsert(['node_id' => $id], [
            'title' => 'Vera — l’emploi enfin lisible | Offres à salaire publié',
            'description' => 'Offres d’emploi à salaire publié. Un test de 6 minutes avant le CV. Un délai de réponse écrit. Pas de pubs.',
        ]);

        foreach (['vera-gd', 'vera-fe', 'vera-pm', 'vera-ops'] as $old) {
            DB::table('edges')->where('from_id', $id)->where('to_id', $old)->delete();
            DB::table('nodes')->where('id', $old)->delete();
        }
        DB::table('edges')->where('from_id', $id)->delete();
        DB::table('edges')->where('from_id', 'like', 'vc-%')->delete();
        DB::table('edges')->where('from_id', 'carnet-karim')->delete();
        DB::table('cck_fields')->where('node_id', 'like', 'vj-%')->delete();
        DB::table('cck_fields')->where('node_id', 'like', 'vc-%')->delete();
        DB::table('cck_fields')->where('node_id', 'carnet-karim')->delete();

        $jobs = \App\Support\VeraCatalog::jobs();
        $houses = [];
        foreach ($jobs as $j) {
            $nid = 'vj-'.$j['slug'];
            $taken = DB::table('nodes')->where('slug', $j['slug'])->where('id', '!=', $nid)->first();
            if ($taken) {
                DB::table('nodes')->where('id', $taken->id)->update(['slug' => $taken->slug.'-old']);
            }
            $hero = $j['pack']['workplace']['image'] ?? '/offer/releve-atelier.jpg';
            $this->node([
                'id' => $nid,
                'slug' => $j['slug'],
                'kind' => 'job',
                'title' => $j['title'],
                'subtitle' => $j['company']['name'].' · '.$j['salaryLabel'],
                'summary' => $j['description'],
                'body' => $j['description'],
                'hero' => $hero,
                'skin' => 'vera',
                'template' => 'vera-tech',
            ]);
            // Vera liste l’offre. La maison la propose. Deux arêtes, deux phrases.
            DB::table('edges')->insert(['from_id' => $id, 'to_id' => $nid, 'kind' => 'parent_of', 'label' => 'Offre']);

            $coSlug = $j['companySlug'];
            $cid = 'vc-'.$coSlug;
            if (! isset($houses[$cid])) {
                $co = $j['company'];
                $this->node([
                    'id' => $cid,
                    'slug' => 'maison-'.$coSlug,
                    'kind' => 'company',
                    'title' => $co['name'],
                    'subtitle' => $co['tagline'] ?? '',
                    'summary' => $co['about'] ?? '',
                    'body' => $co['about'] ?? '',
                    'hero' => $hero,
                    'skin' => 'living',
                ]);
                \App\Support\FieldTemplates::apply($cid, 'maison');
                \App\Support\Engine::fill($cid, [
                    'delai_reponse' => ['value' => ($co['slaDays'] ?? 10).' j', 'min' => $co['slaDays'] ?? 10],
                    'fiabilite' => ['value' => (string) ($co['honorScore'] ?? 80), 'min' => $co['honorScore'] ?? 80],
                    'industrie' => $co['industry'] ?? '',
                    'ville' => $co['hqCity'] ?? '',
                ]);
                DB::table('edges')->insert(['from_id' => $id, 'to_id' => $cid, 'kind' => 'parent_of', 'label' => 'Maison']);
                $houses[$cid] = true;
            }
            DB::table('edges')->insert(['from_id' => $cid, 'to_id' => $nid, 'kind' => 'parent_of', 'label' => 'Offre']);

            $tpl = $this->templateForJob($j);
            \App\Support\FieldTemplates::apply($nid, $tpl);
            \App\Support\Engine::fill($nid, $this->jobFieldValues($j));
        }

        $this->seedKarimCarnet($id);

        DB::table('quests')->where('node_id', $id)->delete();
        $qs = [
            [1, 'Honnêteté', 'lire', 'Le difficile est-il écrit ?', 'Oui, trois blocs : dur / bien / exceptionnel.', '« Selon profil ».'],
            [2, 'Salaire', 'marché', 'La bande P25–P90 est-elle publique ?', 'Oui, Observatoire Vera 2026.', 'Fourchette cachée.'],
            [3, 'Épreuve', 'geste', 'Les coordonnées avant l’épreuve ?', 'Non. Après un 55.', 'Formulaire LinkedIn.'],
            [4, 'PPQC', 'modèle', 'Quand facture-t-on ?', 'Épreuve tenue + grille ≥ 55.', 'Au clic.'],
            [5, 'Pacte', 'honneur', 'Un retard ?', 'L’honneur baisse, public.', 'Silence.'],
            [6, 'Passport', 'preuve', 'Le CV IA ?', 'Registre JSON exportable.', 'PDF LinkedIn.'],
            [7, 'Close', 'tenu', 'Le dossier tient. Vous…', 'Contrat + split d’onboarding.', 'Ghost.'],
        ];
        foreach ($qs as $q) {
            DB::table('quests')->insert(['node_id' => $id, 'step' => $q[0], 'title' => $q[1], 'skill' => $q[2], 'prompt' => $q[3], 'option_a' => $q[4], 'option_b' => $q[5]]);
        }

        DB::table('wiki_pages')->where('node_id', $id)->delete();
        foreach (\App\Support\VeraCatalog::json('savoirs-arts') as $a) {
            DB::table('wiki_pages')->insert([
                'node_id' => $id,
                'title' => $a['title'],
                'body' => implode("\n\n", $a['body'] ?? []),
            ]);
        }

        $now = now();
        DB::table('articles')->updateOrInsert(['id' => 'art-vera-1'], [
            'node_id' => $id, 'slug' => 'verdict-avant-candidature',
            'title' => 'Le Verdict avant candidature : pourquoi un « Passez » est le produit',
            'theme' => 'Métier', 'dossier' => 'Dossier métier',
            'resume' => 'Ghost, honneur, fourchette, process. Un « Passez » épargne des heures. Indeed n’a aucun intérêt à le dire.',
            'body' => "Vera n’est pas un ATS. C’est un jobboard éditorial. L’offre @technicien-maintenance-releve s’ouvre en salaire, honnêteté, épreuve.\n\nPublic : candidats, C-level, OPCO.",
            'definition_term' => 'Verdict Vera',
            'definition' => 'Score public avant candidature : ghost, honneur, salaire, durée du process. Trois issues : Allez, Demandez, Passez.',
            'toc' => "Pourquoi le CV ment\nLe Pacte\nPPQC\nFAQ",
            'longtail' => "jobboard salaire publié|Vera\nrecrutement par test métier|Preuve\ntarif candidat qualifié|Modèle",
            'faq' => "C’est du gamification gadget ?||Non. Chaque étape alimente le JobPosting et le PPQC.\nLinkedIn peut importer le Passport ?||Non. JSON Open Badge, pas un PDF.",
            'cover' => '/offer/releve-atelier.jpg', 'video_path' => 'offer/v/karim.mp4',
            'author' => 'Observatoire Vera', 'author_role' => 'Éditorial', 'reading_min' => 8, 'views' => 240,
            'published_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('drive_files')->where('node_id', $id)->delete();
        foreach ([
            ['Atelier Relève.jpg', '/offer/releve-atelier.jpg', 'image'],
            ['Chantier Kora.jpg', '/offer/kora-chantier.jpg', 'image'],
            ['Domicile Lise.jpg', '/offer/lise-domicile.jpg', 'image'],
            ['Kit consignation.jpg', '/offer/tool-consignation.jpg', 'image'],
            ['Voix Karim.mp4', '/offer/v/karim.mp4', 'video'],
        ] as $f) {
            DB::table('drive_files')->insert([
                'node_id' => $id, 'title' => $f[0], 'path' => $f[1], 'kind' => $f[2], 'locked' => 0,
            ]);
        }

        DB::table('threads')->updateOrInsert(['id' => 'th-vera-1'], [
            'node_id' => $id, 'kind' => 'forum', 'title' => 'Un cadenas partagé = 0. On est d’accord ?',
            'author' => 'Karim', 'body' => 'Relève Fos. Voir @technicien-maintenance-releve. L’épreuve lockout n’est pas un QCM LinkedIn.',
            'cover' => '/offer/releve-atelier.jpg', 'views' => 410, 'fires' => 18, 'replies_count' => 2,
        ]);
        DB::table('replies')->where('thread_id', 'th-vera-1')->delete();
        DB::table('replies')->insert([
            ['thread_id' => 'th-vera-1', 'author' => 'Nadia', 'body' => 'Kora pareil sur le neutre ouvert. @electricien-ombrieres-kora', 'votes' => 21, 'badge' => 'Terrain', 'product_id' => '', 'file_title' => '', 'file_path' => '', 'file_locked' => 0, 'video_title' => '', 'video_path' => '', 'video_meta' => ''],
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
            'description' => 'Œuvres uniques, holo-vidéo, RWA, split auteurs. Shopify n’a pas les fiches liées.',
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
            'definition' => 'Landing d’une œuvre : chapitres, loot temporel, Drive, auteur, panier. YouTube n’a pas ça.',
            'toc' => "Grille vs cimaise\nRWA\nSplit\nFAQ",
            'longtail' => "galerie rwa|Vitrine Lumen\nboutique hologramme|Ce guide\nsplit payment artistes|Produit @p-lu-2",
            'faq' => "C’est un NFT ?||Certificat, pas une spéculation. Le fichier reste chez toi.\nShopify peut faire ça ?||Pas les fiches liées, pas le forum Legacy, pas le split natif.",
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

        $this->seedLumenPieces($id);
    }

    private function templateForJob(array $j): string
    {
        $blob = mb_strtolower(implode(' ', array_merge($j['skills'] ?? [], $j['requirements'] ?? [])));
        if (($j['collection'] ?? '') === 'terrain' || str_contains($blob, 'caces') || str_contains($blob, 'habilitation') || str_contains($blob, 'hydraulique')) {
            return 'offre-industrie';
        }

        return 'offre-tech';
    }

    private function jobFieldValues(array $j): array
    {
        $minK = isset($j['salaryMin']) ? (int) round($j['salaryMin'] / 1000) : null;
        $maxK = isset($j['salaryMax']) ? (int) round($j['salaryMax'] / 1000) : null;
        $text = mb_strtolower(($j['description'] ?? '').' '.implode(' ', $j['requirements'] ?? []).' '.implode(' ', $j['benefits'] ?? []));
        $out = [
            'salaire' => ['value' => $j['salaryLabel'], 'min' => $minK, 'max' => $maxK],
            'remote' => $j['remoteLabel'] ?? '',
            'contrat' => $j['contractLabel'] ?? '',
            'seniorite' => $j['seniorityLabel'] ?? '',
            'stack' => implode(', ', $j['skills'] ?? []),
            'visa' => str_contains($text, 'visa') ? 'Sponsorisé' : 'Non requis',
            'habilitation' => str_contains($text, 'habilitation') ? 'Requise / financée' : 'Selon poste',
            'caces' => str_contains($text, 'caces') ? 'Oui — financé' : 'Non',
            'trois_huit' => (str_contains($text, 'astreinte') || str_contains($text, 'nuit')) ? 'Astreinte écrite' : 'Non',
        ];

        return $out;
    }

    /** Carnet démo : projection graphe (arêtes validées + détails). */
    private function seedKarimCarnet(string $veraId): void
    {
        $this->node([
            'id' => 'carnet-karim',
            'slug' => 'carnet-karim',
            'kind' => 'person',
            'title' => 'Carnet de Karim',
            'subtitle' => 'Maintenance · Fos-sur-Mer',
            'summary' => 'Preuves tenues : consignation, hydraulique, GMAO.',
            'hero' => '/offer/karim.jpg',
            'skin' => 'vera',
        ]);
        DB::table('cck_fields')->where('node_id', 'carnet-karim')->delete();
        foreach ([
            ['geste', 'Le geste', 'text', 'Consignation'],
            ['metier', 'Métier', 'text', 'Maintenance industrielle'],
            ['stack', 'Compétences', 'text', 'Mécanique, Hydraulique, Consignation, GMAO, CACES'],
        ] as $i => $f) {
            DB::table('cck_fields')->insert([
                'node_id' => 'carnet-karim', 'name' => $f[1], 'type' => $f[2], 'value' => $f[3],
                'target_kind' => 'node', 'target_id' => '', 'sort' => $i, 'options' => '', 'seo_title' => $f[1],
                'field_key' => $f[0], 'unit' => '', 'schema_version' => 1,
            ]);
        }
        DB::table('edges')->insert(['from_id' => $veraId, 'to_id' => 'carnet-karim', 'kind' => 'parent_of', 'label' => 'Carnet']);
        foreach (['vj-technicien-maintenance-releve', 'vj-electricien-ombrieres-kora'] as $to) {
            if (DB::table('nodes')->where('id', $to)->exists()) {
                DB::table('edges')->insert(['from_id' => 'carnet-karim', 'to_id' => $to, 'kind' => 'validated', 'label' => 'Épreuve validée']);
            }
        }
    }

    private function seedLumenPieces(string $lumenId): void
    {
        DB::table('edges')->where('from_id', $lumenId)->where('to_id', 'like', 'lu-%')->delete();
        DB::table('cck_fields')->where('node_id', 'like', 'lu-%')->delete();
        $pieces = [
            ['lu-cristal', 'cristal-lumen-01', 'Cristal Lumen #01', 'Pièce unique, certificat.', '/realms/actor-hero.jpg', 'LU-CR-01', '1', 'Cristal optique', '2400'],
            ['lu-print', 'print-nocturne-40-60', 'Print nocturne 40×60', 'Tirage 25.', '/realms/portal-hero.jpg', 'LU-PR-25', '8', 'Papier baryté', '180'],
        ];
        foreach ($pieces as $p) {
            $this->node([
                'id' => $p[0], 'slug' => $p[1], 'kind' => 'product', 'title' => $p[2],
                'subtitle' => $p[3], 'summary' => $p[3], 'hero' => $p[4], 'skin' => 'living',
            ]);
            DB::table('edges')->insert(['from_id' => $lumenId, 'to_id' => $p[0], 'kind' => 'parent_of', 'label' => 'Œuvre']);
            \App\Support\FieldTemplates::apply($p[0], 'produit');
            \App\Support\Engine::fill($p[0], [
                'sku' => $p[5],
                'stock' => $p[6],
                'matiere' => $p[7],
                'prix' => $p[8],
            ]);
        }
    }
}
