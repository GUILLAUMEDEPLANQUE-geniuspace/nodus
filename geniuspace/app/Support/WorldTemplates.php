<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

/**
 * 50 templates d’univers. Structure + SEO + peau.
 * Contenu = 0 (le fan habille). Rien n’est copiable tel quel ailleurs :
 * chaque id a un schéma, un curseur, des salles et une innovation de ranking.
 */
class WorldTemplates
{
    public static function groups(): array
    {
        $g = [];
        foreach (self::all() as $t) {
            $g[$t['group']][] = $t;
        }
        return $g;
    }

    public static function get(string $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    public static function apply(GpNode $node, string $id): void
    {
        $t = self::get($id);
        if (! $t) {
            return;
        }
        $node->template = $id;
        $node->kind = $t['kind'];
        $node->skin = $t['skin'];
        $node->hero = $t['hero'];
        $node->subtitle = $t['pitch'];
        $node->save();
        DB::table('node_tabs')->where('node_id', $node->id)->delete();
        foreach ($t['rooms'] as $i => $key) {
            $meta = RoomCatalog::all()[$key] ?? [$key, $key];
            DB::table('node_tabs')->insert([
                'node_id' => $node->id,
                'key' => $key,
                'label' => is_array($meta) ? $meta[0] : $key,
                'icon' => 'spark',
                'sort' => $i,
                'color' => $t['primary'],
                'seo_title' => ($meta[0] ?? $key).' — '.$node->title,
                'seo_desc' => $t['seo'],
            ]);
        }
        DB::table('node_seo')->updateOrInsert(['node_id' => $node->id], [
            'title' => str_replace('{name}', $node->title, $t['title']),
            'description' => str_replace('{name}', $node->title, $t['seo']),
        ]);
        DB::table('cck_fields')->where('node_id', $node->id)->delete();
        foreach ($t['cck'] as $i => $f) {
            DB::table('cck_fields')->insert([
                'node_id' => $node->id, 'name' => $f[0], 'type' => $f[1], 'value' => '', 'sort' => $i,
            ]);
        }
        DB::table('node_arcs')->where('node_id', $node->id)->delete();
        foreach ($t['arcs'] as $i => $label) {
            DB::table('node_arcs')->insert(['node_id' => $node->id, 'label' => $label, 'ord' => $i + 1]);
        }
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        $R = ['forum', 'personnages', 'journal', 'videos', 'guides'];
        $h = [
            'sea' => '/realms/sea-hero.jpg', 'g' => '/realms/205-garage.jpg', 's' => '/realms/studio-hero.jpg',
            'p' => '/realms/portal-hero.jpg', 'm' => '/realms/205-meet.jpg', 'd' => '/realms/205-dash.jpg',
        ];
        // id, group, label, pitch, innovation, kind, skin, primary, hero, schema, rooms, cck, arcs, title, seo
        $rows = [
            ['manga-hub', 'Fandom', 'Hub manga', 'Wiki vivant + anti-spoiler d’arcs', 'Curseur d’arc + graphe persos', 'series', 'living', '#e11d48', $h['sea'], 'TVSeries', array_merge($R, ['stories', 'boutique']), [['Arc', 'text'], ['Équipage', 'text'], ['Fruit', 'text']], ['Arc 1', 'Arc milieu', 'Finale'], '{name} — wiki, arcs, magazine | Geniuspace', 'Fiches perso, magazine, forum sans spoil. Cluster indexable.'],
            ['anime-cour', 'Fandom', 'Anime (cour / saison)', 'Saison = curseur, pas un dump Fandom', 'Masque les cours non vus', 'series', 'living', '#f97316', $h['sea'], 'TVSeries', array_merge($R, ['stories']), [['Saison', 'digits'], ['Studio', 'text']], ['Cour 1', 'Cour 2', 'Film'], '{name} — saisons, fiches, magazine', 'Page saison crawlable, FAQ, cluster persos.'],
            ['serie-tv', 'Fandom', 'Série TV', 'Spoilers par épisode, Dark-style', 'Curseur SxEx + graphe timelines', 'series', 'living', '#6366f1', $h['p'], 'TVSeries', array_merge($R, ['guides']), [['Saison', 'digits'], ['Plateforme', 'text']], ['S1', 'S2', 'Finale'], '{name} — épisodes, persos, magazine', 'Entité série + FAQ + maillage persos.'],
            ['comics-bd', 'Fandom', 'BD / comics', 'Albums = fiches, pas un tumblr', 'Graphe tome parent/enfant', 'series', 'living', '#dc2626', $h['p'], 'ComicSeries', array_merge($R, ['boutique', 'gallery']), [['Tome', 'digits'], ['Éditeur', 'text']], ['Tome 1', 'Cycle 2'], '{name} — tomes, auteurs, boutique', 'Chaque album une URL Offer-ready.'],
            ['light-novel', 'Fandom', 'Light novel', 'Volumes + adaptation anime liés', 'Graphe roman → anime → persos', 'series', 'living', '#a855f7', $h['sea'], 'Book', array_merge($R, ['boutique']), [['Volume', 'digits'], ['Traducteur', 'text']], ['Vol. 1', 'Vol. suite'], '{name} — volumes, magazine LN', 'Fiches volume, FAQ traduction.'],
            ['webtoon', 'Fandom', 'Webtoon', 'Scroll vertical indexé, pas un scan', 'Chapitres = pages + digest', 'series', 'living', '#22c55e', $h['sea'], 'ComicSeries', array_merge($R, ['stories']), [['Chapitre', 'digits']], ['S1', 'S2'], '{name} — chapitres, forum', 'Chaque chapitre une URL.'],
            ['kpop', 'Fandom', 'K-pop', 'Comebacks, photocards, mag', 'Agenda comeback + Offer photocards', 'series', 'living', '#ec4899', $h['m'], 'MusicGroup', array_merge($R, ['agenda', 'boutique', 'gallery']), [['Agence', 'text'], ['Fandom', 'text']], ['Debut', 'Comeback'], '{name} — comebacks, boutique fan', 'Event + Product photocards, SEO local concerts.'],
            ['tokusatsu', 'Fandom', 'Tokusatsu', 'Henshin, saisons, jouets', 'Graphe ranger → arme → saison', 'series', 'living', '#ef4444', $h['p'], 'TVSeries', array_merge($R, ['boutique']), [['Saison', 'text']], ['S1', 'S2'], '{name} — saisons, jouets', 'Cluster jouet Offer.'],
            ['cine-club', 'Fandom', 'Ciné-club', 'Séances, critiques indexées', 'Avis = Review schema + agenda', 'series', 'living', '#eab308', $h['s'], 'Movie', array_merge(['forum', 'journal', 'agenda', 'reviews', 'videos', 'guides']), [['Réalisateur', 'text'], ['Année', 'digits']], [], '{name} — séances, critiques', 'Review + Event séance.'],
            ['club-lecture', 'Fandom', 'Club lecture', 'Livres, séances, magazine', 'Fiche Book + digest séance', 'series', 'living', '#78716c', $h['d'], 'Book', array_merge(['forum', 'journal', 'guides', 'agenda', 'personnages']), [['ISBN', 'text'], ['Auteur', 'text']], [], '{name} — lectures, magazine', 'Chaque livre une fiche Book.'],
            ['jrpg', 'Jeux', 'JRPG / wiki de jeu', 'Quêtes, builds, anti-spoiler', 'Curseur chapitre + skill tree', 'series', 'living', '#0ea5e9', $h['p'], 'VideoGame', array_merge($R, ['reliques']), [['Plateforme', 'text'], ['Patch', 'text']], ['Chap. 1', 'Mi-jeu', 'Post-game'], '{name} — builds, quêtes, magazine', 'VideoGame + FAQ builds.'],
            ['fps-esport', 'Jeux', 'FPS / esport', 'Maps, strats, VOD indexées', 'VOD chapitrées Clip schema', 'series', 'living', '#f43f5e', $h['s'], 'VideoGame', array_merge(['forum', 'videos', 'journal', 'guides', 'agenda']), [['Jeu', 'text'], ['Rôle', 'text']], [], '{name} — strats, VOD', 'Clip + Article strat.'],
            ['indie-dev', 'Jeux', 'Studio indie', 'Devlog magazine + jobs', 'Devlog BlogPosting + offres', 'company', 'living', '#14b8a6', $h['s'], 'SoftwareApplication', ['journal', 'forum', 'videos', 'offres', 'guides', 'reliques'], [['Engine', 'text'], ['Plateforme', 'text']], [], '{name} — devlog, carrières', 'SoftwareApplication + JobPosting.'],
            ['retro-pixel', 'Jeux', 'Rétro / pixel', 'ROM-hacks non : magazines, fiches', 'Fiches hardware + meets', 'series', 'living', '#84cc16', $h['d'], 'VideoGame', array_merge($R, ['classifieds', 'agenda']), [['Année', 'digits'], ['Support', 'text']], [], '{name} — rétro, magazine, occasion', 'Product occasion + Article.'],
            ['mmorpg-guilde', 'Jeux', 'Guilde MMO', 'Raids, roster, loot', 'Roster = fiches + guilde live', 'series', 'living', '#7c3aed', $h['p'], 'VideoGame', array_merge($R, ['guilde', 'reliques']), [['Serveur', 'text'], ['Faction', 'text']], ['T1', 'T2', 'Endgame'], '{name} — guilde, raids', 'Organization guilde + quêtes.'],
            ['gacha', 'Jeux', 'Gacha / banners', 'Banners datés, rates, mag', 'Event banner + curseur patch', 'series', 'living', '#e879f9', $h['sea'], 'VideoGame', array_merge($R, ['agenda', 'boutique']), [['Banner', 'text'], ['Rate', 'text']], ['Patch actuel', 'Prochain'], '{name} — banners, rates', 'Event + FAQ rates.'],
            ['speedrun', 'Jeux', 'Speedrun', 'Routes, VOD, WR', 'VOD chapitrées + table WR', 'series', 'living', '#22d3ee', $h['s'], 'VideoGame', ['videos', 'guides', 'forum', 'journal', 'personnages'], [['Catégorie', 'text'], ['Temps', 'text']], [], '{name} — routes, WR', 'VideoObject + HowTo route.'],
            ['sim-tycoon', 'Jeux', 'Gestion / tycoon', 'Guides éco, mods', 'Tableurs CCK + mag', 'series', 'living', '#65a30d', $h['g'], 'VideoGame', array_merge($R, ['guides']), [['Version', 'text']], [], '{name} — guides gestion', 'HowTo économique.'],
            ['club-auto', 'Collection', 'Club auto', 'Fiches, pièces geo, magazine', 'Offer + geo + comparateur', 'auto', 'living', '#e85d04', $h['g'], 'Vehicle', ['forum', 'personnages', 'classifieds', 'journal', 'videos', 'guides', 'carte', 'agenda'], [['Marque', 'text'], ['Cylindrée', 'text'], ['Année', 'digits']], ['Phase 1', 'Phase 2', 'Prépa'], '{name} — fiches, pièces, magazine', 'Vehicle + Offer local + TechArticle.'],
            ['moto-cafe', 'Collection', 'Moto / café racer', 'Sorties, pièces, mag', 'Agenda balades + Offer pièces', 'auto', 'living', '#44403c', $h['m'], 'Vehicle', ['forum', 'personnages', 'classifieds', 'agenda', 'journal', 'videos', 'carte'], [['Cylindrée', 'digits'], ['Type', 'text']], [], '{name} — motos, balades', 'Vehicle + Event balade.'],
            ['montres', 'Collection', 'Montres', 'Références, authenticité, mag', 'Graphe référence → calibre → bracelet', 'product', 'living', '#a8a29e', $h['d'], 'Product', ['personnages', 'guides', 'journal', 'classifieds', 'gallery', 'forum'], [['Réf.', 'text'], ['Calibre', 'text']], [], '{name} — références, magazine horloger', 'Product + FAQ authenticité.'],
            ['sneakers', 'Collection', 'Sneakers', 'Drops, tailles, mag', 'Drop Event + Offer paires', 'product', 'living', '#facc15', $h['m'], 'Product', ['agenda', 'classifieds', 'journal', 'gallery', 'forum', 'guides'], [['Silhouette', 'text'], ['Pointure', 'digits']], [], '{name} — drops, paires', 'Event drop + Product.'],
            ['lego-afol', 'Collection', 'LEGO / AFOL', 'Sets, MOC, magazine', 'Set parent → pièces enfants', 'product', 'living', '#f59e0b', $h['g'], 'Product', ['personnages', 'guides', 'journal', 'gallery', 'classifieds', 'forum'], [['Set n°', 'text'], ['Pièces', 'digits']], [], '{name} — sets, MOC', 'Product set + HowTo MOC.'],
            ['vinyle', 'Collection', 'Vinyles', 'Pressages, wantlist, mag', 'Release MusicAlbum + classifieds', 'product', 'living', '#1e293b', $h['d'], 'MusicAlbum', ['personnages', 'classifieds', 'journal', 'audio', 'forum', 'guides'], [['Label', 'text'], ['Année', 'digits']], [], '{name} — pressages, wantlist', 'MusicAlbum + Offer occasion.'],
            ['vera-tech', 'Emploi', 'Maison tech (Vera)', 'Offres = quêtes, Passport', 'JobPosting + épreuve 7 étapes + sac CV', 'company', 'vera', '#38bdf8', $h['s'], 'Organization', ['offres', 'epreuve', 'forum', 'journal', 'videos', 'guides', 'guilde'], [['Stack', 'text'], ['Remote', 'boolean'], ['Salaire', 'text']], ['Candidature', 'Épreuve', 'Offre'], '{name} — carrières, épreuves', 'JobPosting transparent + épreuves.'],
            ['vera-creative', 'Emploi', 'Studio créatif', 'Carrières récit, pas une liste', 'Quêtes portfolio + magazine making-of', 'company', 'vera', '#fb7185', $h['s'], 'Organization', ['offres', 'epreuve', 'journal', 'gallery', 'videos', 'forum'], [['Discipline', 'text'], ['Ville', 'geo']], ['Book', 'Épreuve', 'Match'], '{name} — studio, carrières', 'JobPosting narratif + CreativeWork.'],
            ['campus', 'Emploi', 'Campus / onboarding', 'Parcours salariés, académie', 'Course + épreuves internes', 'company', 'vera', '#34d399', $h['s'], 'EducationalOrganization', ['academie', 'guides', 'videos', 'forum', 'journal', 'offres'], [['Campus', 'text']], ['J1', 'J30', 'J90'], '{name} — campus, académie', 'Course + Organization.'],
            ['freelance', 'Emploi', 'Guilde freelance', 'Missions, preuves, mag', 'Offer mission + Passport', 'company', 'living', '#818cf8', $h['p'], 'Organization', ['classifieds', 'offres', 'forum', 'journal', 'guides', 'guilde'], [['TJM', 'text'], ['Stack', 'text']], [], '{name} — missions, preuves', 'Offer + Person preuves.'],
            ['vivier', 'Emploi', 'Vivier (seniors, RSA…)', 'Pas un jobboard froid', 'Fiches vivier + épreuves douces', 'company', 'vera', '#94a3b8', $h['s'], 'Organization', ['offres', 'forum', 'journal', 'guides', 'guilde'], [['Statut', 'text']], [], '{name} — vivier, missions', 'JobPosting inclusif.'],
            ['bts-com', 'Formation', 'BTS / école commerce', 'Cours, cas, mag métier', 'Course + exercises indexés', 'company', 'living', '#2563eb', $h['s'], 'Course', ['guides', 'journal', 'videos', 'forum', 'personnages', 'agenda'], [['Diplôme', 'text'], ['RNCP', 'text']], ['Année 1', 'Année 2'], '{name} — cours, cas pratiques', 'Course + FAQ examen.'],
            ['chef-secteur', 'Formation', 'Centre chef de secteur', 'Le moule geniuspace.io, en club', 'Hub métier + spokes KPI/tournée', 'company', 'living', '#0f766e', $h['m'], 'Occupation', ['guides', 'journal', 'videos', 'forum', 'agenda', 'personnages'], [['Enseigne', 'text'], ['Région', 'geo']], [], '{name} — métier chef de secteur', 'Occupation + cluster KPI.'],
            ['esoterique', 'Commerce', 'Boutique ésotérique', 'Reliques, tirages, mag', 'Product + RWA + holo-vidéo', 'product', 'living', '#7e22ce', $h['p'], 'Store', ['boutique_expert', 'journal', 'videos', 'guides', 'forum', 'gallery'], [['Pierre', 'text'], ['Rituel', 'text']], [], '{name} — reliques, magazine', 'Product Offer + Article rituel.'],
            ['pieces', 'Commerce', 'Pièces / SAV', 'Occasion geo, fiches techniques', 'Offer + geo + TechArticle', 'product', 'living', '#ea580c', $h['g'], 'Store', ['classifieds', 'guides', 'journal', 'videos', 'carte', 'forum'], [['Réf. OEM', 'text'], ['Ville', 'geo']], [], '{name} — pièces, tutos', 'Offer local + guide.'],
            ['leboncoin-club', 'Commerce', 'Petites annonces club', 'Entre membres, SEO local', 'Offer + geo, pas Facebook', 'product', 'living', '#65a30d', $h['m'], 'OfferCatalog', ['classifieds', 'carte', 'forum', 'journal', 'gallery'], [['Ville', 'geo'], ['Prix', 'text']], [], '{name} — annonces locales', 'OfferCatalog + Place.'],
            ['merch-fan', 'Commerce', 'Merch officiel fan', 'Prints, splits auteurs', 'Split payment + Product', 'product', 'living', '#db2777', $h['sea'], 'Store', ['boutique', 'gallery', 'journal', 'forum', 'videos'], [['Créateur', 'text'], ['Split %', 'digits']], [], '{name} — merch, créateurs', 'Product + split auteurs.'],
            ['galerie-rwa', 'Commerce', 'Galerie RWA / art', 'Œuvres uniques, certificat', 'VisualArtwork + crowd-goal', 'product', 'living', '#c4b5fd', $h['p'], 'ArtGallery', ['boutique_expert', 'gallery', 'journal', 'videos', 'forum'], [['Certificat', 'text'], ['Cote', 'text']], [], '{name} — œuvres, certificats', 'VisualArtwork + Offer.'],
            ['pod-print', 'Commerce', 'Print on demand', 'Fiches produit + mag lookbook', 'Product + Article lookbook', 'product', 'living', '#fb923c', $h['m'], 'Store', ['boutique', 'journal', 'gallery', 'guides', 'forum'], [['SKU', 'text'], ['Coloris', 'text']], [], '{name} — boutique print', 'Product + CollectionPage.'],
            ['japon', 'Pays', 'Japon (export / culture)', 'Nemawashi, mag, fiches lieux', 'Place + Article B2B + hreflang JA', 'series', 'living', '#b91c1c', $h['p'], 'Country', array_merge($R, ['carte', 'agenda']), [['Préfecture', 'geo'], ['Thème', 'text']], [], '{name} — Japon, magazine', 'Country + Place + Article export.'],
            ['chine-cbec', 'Pays', 'Chine / CBEC', 'Tmall, Douyin, KOC, mag', 'Article cluster + Product CBEC', 'series', 'living', '#dc2626', $h['s'], 'Country', array_merge($R, ['boutique', 'guides']), [['Plateforme', 'text'], ['HS code', 'text']], [], '{name} — CBEC, guides Chine', 'Article cluster export Chine.'],
            ['coree', 'Pays', 'Corée', 'Culture, business, mag', 'Place + Music + commerce', 'series', 'living', '#2563eb', $h['sea'], 'Country', array_merge($R, ['agenda', 'boutique']), [['Ville', 'geo']], [], '{name} — Corée, magazine', 'Country + Event.'],
            ['france-terroir', 'Pays', 'France / terroir', 'AOP, producteurs, mag', 'Place + Product AOP', 'product', 'living', '#166534', $h['m'], 'Place', ['personnages', 'boutique', 'journal', 'carte', 'forum', 'guides', 'agenda'], [['AOP', 'text'], ['Région', 'geo']], [], '{name} — terroir, producteurs', 'Place + Product.'],
            ['maghreb', 'Pays', 'Maghreb / diaspora', 'Commerces, associatif, mag', 'Place + Offer local FR-MAG', 'series', 'living', '#ca8a04', $h['m'], 'Country', array_merge($R, ['classifieds', 'carte', 'agenda']), [['Ville', 'geo']], [], '{name} — diaspora, annonces', 'Place + Offer.'],
            ['asean', 'Pays', 'ASEAN trade', 'Marchés, guides, mag', 'Country cluster + Article B2B', 'series', 'living', '#0d9488', $h['s'], 'Country', array_merge($R, ['guides']), [['Pays', 'text']], [], '{name} — ASEAN, guides trade', 'Article B2B + Country.'],
            ['b2b-sales', 'Business', 'Équipe commerciale B2B', 'Playbooks, comptes, mag', 'Article playbook + Person comptes', 'company', 'living', '#1d4ed8', $h['s'], 'Organization', ['guides', 'journal', 'forum', 'personnages', 'videos', 'offres'], [['ICP', 'text'], ['Cycle', 'text']], [], '{name} — playbooks, comptes', 'Article + Person compte cible.'],
            ['retail-gms', 'Business', 'Réseau GMS / franchise', 'Standards, visites, mag', 'HowTo visite + Occupation', 'company', 'living', '#c2410c', $h['m'], 'Organization', ['guides', 'journal', 'videos', 'forum', 'agenda', 'personnages'], [['Enseigne', 'text'], ['PDV', 'geo']], [], '{name} — standards réseau', 'HowTo + Place magasin.'],
            ['cabinet', 'Business', 'Cabinet / conseil', 'Livrables, offres, mag', 'CreativeWork livrable + JobPosting', 'company', 'vera', '#334155', $h['s'], 'ProfessionalService', ['journal', 'offres', 'guides', 'videos', 'forum'], [['Pratique', 'text']], [], '{name} — cabinet, insights', 'ProfessionalService + Article.'],
            ['saas-docs', 'Business', 'SaaS / docs produit', 'Changelog, guides, jobs', 'TechArticle + SoftwareApplication', 'company', 'living', '#4f46e5', $h['s'], 'SoftwareApplication', ['guides', 'journal', 'videos', 'forum', 'offres'], [['Version', 'text']], ['v1', 'v2'], '{name} — docs, changelog', 'SoftwareApplication + TechArticle.'],
            ['asso', 'Business', 'Association', 'CR, AG, mag, dons', 'NGO + Event AG + crowd-goal', 'company', 'living', '#0369a1', $h['m'], 'NGO', ['journal', 'agenda', 'forum', 'guides', 'gallery', 'guilde'], [['SIRET', 'text'], ['Objet', 'text']], [], '{name} — association, CR', 'NGO + Event.'],
            ['photo-club', 'Création', 'Club photo', 'Séries, EXIF, mag', 'ImageGallery + Article série', 'series', 'living', '#52525b', $h['d'], 'Photograph', ['gallery', 'journal', 'forum', 'guides', 'agenda', 'personnages'], [['APN', 'text'], ['EXIF', 'text']], [], '{name} — séries photo', 'ImageGallery + Article.'],
            ['cuisine', 'Création', 'Cuisine / terroir club', 'Recettes HowTo, mag', 'Recipe + Place resto', 'series', 'living', '#b45309', $h['m'], 'Recipe', ['guides', 'journal', 'videos', 'forum', 'agenda', 'gallery'], [['Plat', 'text'], ['Allergènes', 'text']], [], '{name} — recettes, magazine', 'Recipe + FAQ.'],
            ['club-sport', 'Création', 'Club sport', 'Équipes, matchs, mag', 'SportsEvent + Person joueur', 'series', 'living', '#16a34a', $h['m'], 'SportsTeam', ['agenda', 'personnages', 'journal', 'videos', 'forum', 'gallery'], [['Division', 'text'], ['Stade', 'geo']], ['Aller', 'Retour'], '{name} — club, matchs', 'SportsEvent + Person.'],
        ];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => $r[0], 'group' => $r[1], 'label' => $r[2], 'pitch' => $r[3], 'innovation' => $r[4],
                'kind' => $r[5], 'skin' => $r[6], 'primary' => $r[7], 'hero' => $r[8], 'schema' => $r[9],
                'rooms' => $r[10], 'cck' => $r[11], 'arcs' => $r[12], 'title' => $r[13], 'seo' => $r[14],
            ];
        }
        return $out;
    }
}
