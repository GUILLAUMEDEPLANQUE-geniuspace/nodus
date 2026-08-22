<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeniuspaceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('nodes')->insert([
            ['id' => 'onepiece', 'slug' => 'one-piece', 'kind' => 'series', 'title' => 'One Piece', 'subtitle' => 'Grand Line habitée', 'summary' => 'Wiki, guilde, reliques, boutique de lore. Les fans bâtissent le monde.', 'body' => 'Un lieu de vie, pas un dump wiki.', 'hero' => '/realms/sea-hero.jpg', 'skin' => 'living', 'featured' => 1],
            ['id' => 'luffy', 'slug' => 'luffy', 'kind' => 'character', 'title' => 'Monkey D. Luffy', 'subtitle' => 'Capitaine', 'summary' => 'Enfant du Node One Piece.', 'body' => '', 'hero' => '/realms/luffy.jpg', 'skin' => 'living', 'featured' => 0],
            ['id' => 'nami', 'slug' => 'nami', 'kind' => 'character', 'title' => 'Nami', 'subtitle' => 'Navigatrice', 'summary' => '', 'body' => '', 'hero' => '/realms/nami.jpg', 'skin' => 'living', 'featured' => 0],
            ['id' => 'sg1', 'slug' => 'stargate-sg1', 'kind' => 'series', 'title' => 'Stargate SG-1', 'subtitle' => 'Série habitée', 'summary' => 'Portail, équipes, graphe des rôles.', 'body' => '', 'hero' => '/realms/portal-hero.jpg', 'skin' => 'living', 'featured' => 1],
            ['id' => 'rda', 'slug' => 'richard-dean-anderson', 'kind' => 'person', 'title' => 'Richard Dean Anderson', 'subtitle' => 'Acteur', 'summary' => 'Tous les rôles : Jack O\'Neill, MacGyver…', 'body' => '', 'hero' => '/realms/actor-hero.jpg', 'skin' => 'living', 'featured' => 0],
            ['id' => 'jack', 'slug' => 'jack-oneill', 'kind' => 'character', 'title' => 'Jack O\'Neill', 'subtitle' => 'Colonel · SG-1', 'summary' => 'Enfant de RDA et de SG-1.', 'body' => '', 'hero' => '/realms/portal-hero.jpg', 'skin' => 'living', 'featured' => 0],
            ['id' => 'orion', 'slug' => 'maison-orion', 'kind' => 'company', 'title' => 'Maison Orion', 'subtitle' => 'Recrutement expérientiel', 'summary' => 'Quêtes, pas des CV. Salon, skill tree, 7 étapes.', 'body' => '', 'hero' => '/realms/studio-hero.jpg', 'skin' => 'vera', 'featured' => 1],
            ['id' => 'job-gd', 'slug' => 'lead-game-designer', 'kind' => 'job', 'title' => 'Lead Game Designer', 'subtitle' => 'Offre Orion', 'summary' => 'Épreuve Culture fit.', 'body' => '', 'hero' => '/realms/studio-hero.jpg', 'skin' => 'vera', 'featured' => 0],
            ['id' => 'atelier', 'slug' => 'atelier-nocturne', 'kind' => 'company', 'title' => 'Atelier Nocturne', 'subtitle' => 'Galerie RWA', 'summary' => 'Œuvre unique certifiée. Making-of dans le Drive.', 'body' => '', 'hero' => '/realms/actor-hero.jpg', 'skin' => 'living', 'featured' => 1],
        ]);

        DB::table('edges')->insert([
            ['from_id' => 'onepiece', 'to_id' => 'luffy', 'kind' => 'parent_of', 'label' => 'Capitaine'],
            ['from_id' => 'onepiece', 'to_id' => 'nami', 'kind' => 'parent_of', 'label' => 'Navigatrice'],
            ['from_id' => 'sg1', 'to_id' => 'jack', 'kind' => 'parent_of', 'label' => 'Colonel'],
            ['from_id' => 'rda', 'to_id' => 'jack', 'kind' => 'portrays', 'label' => 'Interprète'],
            ['from_id' => 'orion', 'to_id' => 'job-gd', 'kind' => 'parent_of', 'label' => 'Offre'],
            ['from_id' => 'atelier', 'to_id' => 'atelier', 'kind' => 'offers', 'label' => 'Galerie'],
        ]);

        DB::table('products')->insert([
            ['id' => 'sp-op-1', 'node_id' => 'onepiece', 'title' => 'Carte annotée Grand Line', 'price' => '24 €', 'summary' => 'Tirage limité.', 'kind' => 'print', 'rating' => '4.8', 'votes' => 128, 'stock' => '12 pièces', 'rwa' => 0, 'energy' => 30, 'image' => '/realms/sea-hero.jpg'],
            ['id' => 'sp-op-2', 'node_id' => 'onepiece', 'title' => 'Chapeau de paille', 'price' => '39 €', 'summary' => 'Merch de guilde.', 'kind' => 'objet', 'rating' => '4.6', 'votes' => 86, 'stock' => 'sur commande', 'rwa' => 0, 'energy' => 25, 'image' => '/realms/luffy.jpg'],
            ['id' => 'sp-at-1', 'node_id' => 'atelier', 'title' => 'Concept Art · L\'Aube', 'price' => '5 000 €', 'summary' => 'Œuvre unique certifiée RWA.', 'kind' => 'rwa', 'rating' => '4.9', 'votes' => 21, 'stock' => 'pièce unique', 'rwa' => 1, 'energy' => 100, 'image' => '/realms/actor-hero.jpg'],
        ]);

        DB::table('media')->insert([
            ['node_id' => 'onepiece', 'title' => 'Volonté du D', 'path' => 'media/teaser.mp4', 'mode' => 'lore', 'access' => 'free', 'teaser_sec' => 0, 'price' => '', 'duration' => '02:10', 'chapters' => "00:00 — Mer\n01:00 — Équipage", 'transcript' => 'Lore de la Grand Line.'],
            ['node_id' => 'atelier', 'title' => 'Making-of L\'Aube', 'path' => 'media/atelier.mp4', 'mode' => 'shop', 'access' => 'freemium', 'teaser_sec' => 6, 'price' => '15 €', 'duration' => '00:16', 'chapters' => "00:00 — Atelier\n00:08 — Pièce (premium)", 'transcript' => 'Teaser public. La suite est derrière le chaudron.'],
            ['node_id' => 'orion', 'title' => 'Épreuve Culture fit', 'path' => 'media/orion.mp4', 'mode' => 'interview', 'access' => 'freemium', 'teaser_sec' => 8, 'price' => 'Épreuve', 'duration' => '00:18', 'chapters' => "00:00 — Maison\n00:08 — Cas", 'transcript' => 'Teaser. Dossier Drive à l\'étape 2.'],
        ]);

        DB::table('threads')->insert([
            ['id' => 'th-op-1', 'node_id' => 'onepiece', 'kind' => 'forum', 'title' => 'Marineford : qui avait raison ?', 'author' => 'Zoro', 'body' => 'Débat lore. Le Legacy indexe les meilleures réponses.', 'cover' => '/realms/sea-hero.jpg'],
            ['id' => 'th-op-2', 'node_id' => 'onepiece', 'kind' => 'forum', 'title' => 'Carte Grand Line annotée', 'author' => 'Nami', 'body' => 'Les reliques du Drive changent le wiki.', 'cover' => '/realms/nami.jpg'],
        ]);

        DB::table('wiki_pages')->insert([
            ['node_id' => 'onepiece', 'title' => 'Introduction', 'body' => 'Première page du wiki. Maillage vers le forum Legacy.'],
            ['node_id' => 'orion', 'title' => 'Bible recruteur', 'body' => 'Pas de liste LinkedIn. Quêtes 1 à 7.'],
        ]);

        DB::table('quests')->insert([
            ['node_id' => 'orion', 'step' => 1, 'title' => 'Culture fit', 'skill' => 'alignement', 'prompt' => 'Un collègue publie un lore faux. Vous…', 'option_a' => 'Corrigez en public, sources.', 'option_b' => 'Message privé + source Drive.'],
            ['node_id' => 'orion', 'step' => 2, 'title' => 'Dossier Drive', 'skill' => 'preuve', 'prompt' => 'Le PDF est locké. Vous…', 'option_a' => 'Passez l\'étape 1.', 'option_b' => 'Demandez un accès modo.'],
        ]);

        DB::table('crowd_goals')->insert([
            ['node_id' => 'atelier', 'target' => 10000, 'current' => 6500, 'reward' => 'Croquis secret pour tous les acheteurs'],
            ['node_id' => 'onepiece', 'target' => 8000, 'current' => 2100, 'reward' => 'Carte annotée pour la guilde'],
        ]);

        DB::table('node_i18n')->insert([
            ['node_id' => 'onepiece', 'locale' => 'en', 'title' => 'One Piece', 'summary' => 'A living Grand Line.'],
            ['node_id' => 'onepiece', 'locale' => 'ja', 'title' => 'ワンピース', 'summary' => '生きているグランドライン。'],
            ['node_id' => 'orion', 'locale' => 'en', 'title' => 'House Orion', 'summary' => 'Quests, not CVs.'],
        ]);
    }
}
