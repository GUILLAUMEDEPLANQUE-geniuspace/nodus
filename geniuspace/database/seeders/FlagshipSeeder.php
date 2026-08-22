<?php

namespace Database\Seeders;

use App\Models\GpNode;
use App\Support\Chrome;
use App\Support\Engine;
use App\Support\FieldTemplates;
use App\Support\Flagships;
use App\Support\WorldTemplates;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Dix flagships visitables. Le Coffre est habillé à fond (cel City Hunter). */
class FlagshipSeeder extends Seeder
{
    public function run(): void
    {
        $demos = [
            'vault' => ['coffre-celeste', 'Coffre Céleste', 'Reliques d’animation. L’hôte tient le coffre.', '/realms/actor-hero.jpg'],
            'terrain' => ['terrain-midgar', 'Terrain Midgar', 'Patch vivant. Builds prouvés. Loot en reliques.', '/realms/portal-hero.jpg'],
            'atelier-anime' => ['atelier-clamp', 'Atelier Clamp', 'Arcs, fiches, cels. Sans spoiler.', '/realms/sea-hero.jpg'],
            'territoire' => ['territoire-japon', 'Territoire Japon', 'Atlas habité. Corridors, pas un listing.', '/realms/portal-hero.jpg'],
            'scene' => ['scene-neon', 'Scène Néon', 'Pressages, stems, drop.', '/realms/studio-hero.jpg'],
            'arene' => ['arene-reims', 'Arène Reims', 'Jour de match. La compos est un lieu.', '/realms/205-meet.jpg'],
            'labo' => ['labo-next', 'Labo Next', 'À 03:15 tu ne regardes plus. Tu fais.', '/realms/studio-hero.jpg'],
            'plateau' => ['plateau-nuit', 'Plateau Nuit', 'Dailies lockées. Feuille de service vivante.', '/realms/studio-hero.jpg'],
            'table' => ['table-aop', 'Table AOP', 'Le plat est un lieu. Le millésime, une relique.', '/realms/205-meet.jpg'],
        ];
        foreach ($demos as $tpl => $d) {
            $this->world($tpl, $d[0], $d[1], $d[2], $d[3]);
        }
        $this->dressVault();
        $this->dressTable();
        $this->dressTerrain();
        $this->dressAtelier();
        $this->dressTerritoire();
        $this->dressScene();
        $this->dressArene();
        $this->dressLabo();
        $this->dressPlateau();
        $this->linkWormholes();
    }

    private function world(string $tpl, string $slug, string $title, string $summary, string $hero): GpNode
    {
        $id = 'fl-'.$tpl;
        $node = GpNode::query()->updateOrCreate(['id' => $id], [
            'slug' => $slug,
            'kind' => Flagships::all()[$tpl]['kind'] ?? 'series',
            'title' => $title,
            'subtitle' => Flagships::all()[$tpl]['pitch'] ?? '',
            'summary' => $summary,
            'body' => '',
            'hero' => $hero,
            'skin' => Flagships::all()[$tpl]['skin'] ?? 'living',
            'template' => $tpl,
            'featured' => in_array($tpl, ['vault', 'terrain', 'atelier-anime', 'territoire'], true) ? 1 : 0,
        ]);
        WorldTemplates::apply($node, $tpl);
        Chrome::applyPreset($node, Flagships::preset($tpl));

        return $node->fresh();
    }

    private function dressVault(): void
    {
        $id = 'fl-vault';
        DB::table('products')->updateOrInsert(['id' => 'p-cel-1'], [
            'node_id' => $id,
            'title' => 'Cel : Ryo Saeba (1987)',
            'price' => '1 200 €',
            'summary' => 'Celluloïd original, épisode 12. Pièce unique, certificat.',
            'kind' => 'relique',
            'rating' => '4.9',
            'votes' => 41,
            'stock' => 'pièce unique',
            'rwa' => 1,
            'energy' => 40,
            'image' => '/realms/actor-hero.jpg',
            'city' => 'Tokyo',
        ]);
        $this->child('fl-hojo', 'tsukasa-hojo', 'person', 'Tsukasa Hojo', 'Auteur', $id, '/realms/actor-hero.jpg');
        $this->child('fl-cel-node', 'cel-ryo-saeba-87', 'product', 'Cel : Ryo Saeba (1987)', 'Relique', $id, '/realms/actor-hero.jpg');
        FieldTemplates::apply('fl-cel-node', 'produit');
        Engine::fill('fl-cel-node', [
            'sku' => 'CH-CEL-87-12',
            'stock' => '1',
            'matiere' => 'Rhodoïd / celluloïd',
            'prix' => '1200',
        ]);
        DB::table('cck_fields')->where('node_id', $id)->where('name', 'Format')->update(['value' => 'Celluloïd (Rhodoïd)', 'field_key' => 'format']);
        DB::table('cck_fields')->where('node_id', $id)->where('name', 'Épisode')->update(['value' => '12', 'field_key' => 'episode']);
        DB::table('cck_fields')->where('node_id', $id)->where('name', 'Certificat')->update(['value' => 'RWA · pièce unique', 'field_key' => 'certificat']);
        DB::table('cck_fields')->where('node_id', $id)->where('name', 'Plancher')->update([
            'value' => '1080', 'field_key' => 'prix_plancher', 'min_val' => 1080, 'max_val' => 1200,
        ]);
        DB::table('cck_fields')->where('node_id', $id)->where('name', 'Rareté')->update(['value' => 'Unique 1987', 'field_key' => 'rarete']);
        DB::table('media')->where('node_id', $id)->where('title', 'Making-of du cel')->delete();
        DB::table('media')->insert([
            'node_id' => $id,
            'title' => 'Making-of du cel',
            'kind' => 'video',
            'path' => 'private/media/lumen-makingof.mp4',
            'mode' => 'shop',
            'access' => 'paid',
            'teaser_sec' => 6,
            'price' => '0',
            'duration' => '6:00',
            'chapters' => "0:00 Intro\n1:25 Couches de peinture @cel-ryo-saeba-87\n4:00 Certificat",
            'transcript' => 'Les couches de peinture. Le certificat s’ouvre à l’acquisition.',
            'views' => 88,
            'rating' => '4.9',
        ]);
        DB::table('node_scene_layers')->where('node_id', $id)->where('kind', 'image')->update(['action_target' => 'tsukasa-hojo']);
    }

    private function dressTable(): void
    {
        DB::table('products')->updateOrInsert(['id' => 'p-aop-1'], [
            'node_id' => 'fl-table',
            'title' => 'Caisse AOP 2018',
            'price' => '84 €',
            'summary' => 'Six bouteilles. Millésime attesté.',
            'kind' => 'cave',
            'rating' => '4.8',
            'votes' => 19,
            'stock' => '12 caisses',
            'rwa' => 0,
            'energy' => 12,
            'image' => '/realms/205-meet.jpg',
            'city' => 'Reims',
        ]);
        DB::table('cck_fields')->where('node_id', 'fl-table')->where('name', 'AOP')->update(['value' => 'Champagne', 'field_key' => 'aop']);
        DB::table('cck_fields')->where('node_id', 'fl-table')->where('name', 'Millésime')->update(['value' => '2018', 'field_key' => 'millesime']);
        DB::table('cck_fields')->where('node_id', 'fl-table')->where('name', 'Plancher')->update([
            'value' => '74', 'field_key' => 'prix_plancher', 'min_val' => 74, 'max_val' => 84,
        ]);
        $this->guide('fl-table', 'Accord millésime 2018', 'Champagne AOP 2018. Accorde le plat, pas le dessert.');
    }

    private function dressTerrain(): void
    {
        $this->fill('fl-terrain', [
            'Plateforme' => 'PC / PS5',
            'Patch' => '1.04 — Midgar Gate',
            'Classe' => 'Lame',
            'Difficulté' => 'Hard',
        ]);
        $this->product('p-mid-1', 'fl-terrain', 'Épée de Midgar', '24 €', 'Loot de boss. Rate 8 % sur Gate Hard.', 'loot', '/realms/portal-hero.jpg');
        $this->guide('fl-terrain', 'Route Hard Gate', 'Fenêtre boss : 0:42 après le choc. Loot : Épée 8 %. Patch 1.04.');
        $this->clip('fl-terrain', 'VOD raid Gate', 'La route. Pause = labo.', "0:00 Teaser\n0:03 Route\n3:15 Labo");
    }

    private function dressAtelier(): void
    {
        $this->fill('fl-atelier-anime', [
            'Studio' => 'Clamp',
            'Saison' => '1',
            'Arc actuel' => 'Tokyo',
        ]);
        $this->child('fl-sakura', 'sakura-kinomoto', 'person', 'Sakura', 'Perso', 'fl-atelier-anime', '/realms/sea-hero.jpg');
        DB::table('nodes')->where('id', 'fl-sakura')->update(['appear_order' => 1, 'appear_label' => 'Cour 1']);
        $this->child('fl-yue', 'yue-finale', 'person', 'Yue', 'Gardien', 'fl-atelier-anime', '/realms/sea-hero.jpg');
        DB::table('nodes')->where('id', 'fl-yue')->update(['appear_order' => 4, 'appear_label' => 'Finale']);
        $this->product('p-cel-clamp', 'fl-atelier-anime', 'Cel Sakura', '90 €', 'Cel original. Le concierge tient le plancher.', 'relique', '/realms/sea-hero.jpg');
        $this->fillPlancher('fl-atelier-anime', 82, 90);
        $this->guide('fl-atelier-anime', 'Arc Tokyo sans spoiler', 'Jusqu’à l’arc Tokyo seulement. La suite n’existe pas encore.');
        $this->guide('fl-atelier-anime', 'Arc Finale — Yue', 'Yue n’existe pas avant la Finale. Le concierge se tait.');
    }

    private function dressTerritoire(): void
    {
        $this->fill('fl-territoire', [
            'Pays' => 'Japon',
            'Langue' => 'ja / fr',
            'Monnaie' => 'JPY',
        ]);
        $this->child('fl-kyoto', 'kyoto', 'place', 'Kyoto', 'Préfecture', 'fl-territoire', '/realms/portal-hero.jpg');
        $this->child('fl-osaka', 'osaka', 'place', 'Osaka', 'Préfecture', 'fl-territoire', '/realms/portal-hero.jpg');
        $this->product('p-kura', 'fl-territoire', 'Saké de cave', '42 €', 'Producteur lié à la table.', 'cave', '/realms/205-meet.jpg');
        $this->guide('fl-territoire', 'Corridor Kyoto', 'Entrer par Gion, sortir par un producteur. Pas un listing.');
    }

    private function dressScene(): void
    {
        $this->fill('fl-scene', [
            'Label' => 'Néon',
            'Année' => '2026',
            'Pressage' => '180g noir',
        ]);
        $this->product('p-vinyl-1', 'fl-scene', 'Pressage Néon', '32 €', 'Vinyl drop. Stems lockés.', 'vinyl', '/realms/studio-hero.jpg');
        $this->fillPlancher('fl-scene', 28, 32);
        $this->clip('fl-scene', 'Clip Néon', 'Waveform. Scroll = timecode.', "0:00 Clip\n0:03 Mixer\n3:15 Stems");
        $this->guide('fl-scene', 'Drop de la nuit', 'Le pressage est une relique. Le stem se mérite.');
    }

    private function dressArene(): void
    {
        $this->fill('fl-arene', [
            'Division' => 'Ligue 2',
            'Stade' => 'Auguste-Delaune',
            'Entraîneur' => 'Staff Reims',
        ]);
        $this->child('fl-j9', 'ailier-reims', 'person', 'N°9', 'Attaquant', 'fl-arene', '/realms/205-meet.jpg');
        $this->child('fl-j10', 'meneur-reims', 'person', 'N°10', 'Meneur', 'fl-arene', '/realms/205-meet.jpg');
        $this->product('p-maillot', 'fl-arene', 'Maillot domicile', '79 €', 'Flockage. Split club.', 'merch', '/realms/205-meet.jpg');
        $this->clip('fl-arene', 'VOD Reims — actions', 'Pause = tableau tactique.', "0:00 Actions\n0:03 Tactique\n3:15 Tactique");
        $this->guide('fl-arene', 'Peau du match', 'Compos à 18 h. Absents notés. Pas un Facebook.');
    }

    private function dressLabo(): void
    {
        $this->fill('fl-labo', [
            'Diplôme' => 'Module Next',
            'Durée' => '6 min + exo',
            'Niveau' => 'Intermédiaire',
            'Langage' => 'PHP',
        ]);
        $this->clip('fl-labo', 'Exo 3 — le cadre s’ouvre', 'À 03:15 tu ne regardes plus. Tu fais.', "0:00 Teaser\n0:03 Labo\n3:15 Labo");
        $this->product('p-mod-1', 'fl-labo', 'Module Next', '49 €', 'Leçon + labo + preuve.', 'cours', '/realms/studio-hero.jpg');
        $this->guide('fl-labo', 'Exo 3 : erreurs fréquentes', 'Colle l’erreur. Le tuteur débloque la porte, pas le TP.');
    }

    private function dressPlateau(): void
    {
        $this->fill('fl-plateau', [
            'Réalisateur' => 'Nuit Studio',
            'Année' => '2026',
            'Format' => '6K',
        ]);
        $this->child('fl-ad', 'ad-nuit', 'person', 'L’AD', 'Régie', 'fl-plateau', '/realms/studio-hero.jpg');
        $this->child('fl-lead', 'actrice-nuit', 'person', 'Rôle principal', 'Jeu', 'fl-plateau', '/realms/actor-hero.jpg');
        $this->clip('fl-plateau', 'Daily scène 12', 'Rushes lockés. L’AD ouvre au rôle.');
        $this->guide('fl-plateau', 'Feuille de service du jour', 'Scène 12, 08:30. Rushes signés, jamais en clair.');
    }

    private function fill(string $id, array $byName): void
    {
        foreach ($byName as $name => $val) {
            $n = DB::table('cck_fields')->where('node_id', $id)->where('name', $name)->update([
                'value' => $val,
                'field_key' => \Illuminate\Support\Str::slug($name, '_'),
            ]);
            if (! $n) {
                DB::table('cck_fields')->insert([
                    'node_id' => $id, 'name' => $name, 'type' => 'text', 'value' => $val,
                    'field_key' => \Illuminate\Support\Str::slug($name, '_'), 'sort' => 20,
                ]);
            }
        }
    }

    private function fillPlancher(string $id, int $min, int $max): void
    {
        $n = DB::table('cck_fields')->where('node_id', $id)->where('name', 'Plancher')->update([
            'value' => (string) $min, 'field_key' => 'prix_plancher', 'min_val' => $min, 'max_val' => $max,
        ]);
        if (! $n) {
            DB::table('cck_fields')->insert([
                'node_id' => $id, 'name' => 'Plancher', 'type' => 'digits', 'value' => (string) $min,
                'field_key' => 'prix_plancher', 'min_val' => $min, 'max_val' => $max, 'sort' => 40,
            ]);
        }
    }

    private function product(string $pid, string $nodeId, string $title, string $price, string $summary, string $kind, string $image): void
    {
        DB::table('products')->updateOrInsert(['id' => $pid], [
            'node_id' => $nodeId, 'title' => $title, 'price' => $price, 'summary' => $summary,
            'kind' => $kind, 'rating' => '4.7', 'votes' => 9, 'stock' => 'en rayon',
            'rwa' => 0, 'energy' => 8, 'image' => $image, 'city' => '',
        ]);
    }

    private function guide(string $nodeId, string $title, string $body): void
    {
        $exists = DB::table('wiki_pages')->where('node_id', $nodeId)->where('title', $title)->exists();
        if (! $exists) {
            DB::table('wiki_pages')->insert(['node_id' => $nodeId, 'title' => $title, 'body' => $body]);
        }
    }

    private function clip(string $nodeId, string $title, string $transcript, ?string $chapters = null): void
    {
        $chapters = $chapters ?: "0:00 Teaser\n0:03 Labo\n3:15 Labo";
        DB::table('media')->where('node_id', $nodeId)->where('title', $title)->delete();
        DB::table('media')->insert([
            'node_id' => $nodeId, 'title' => $title, 'kind' => 'video',
            'path' => 'private/media/lumen-makingof.mp4', 'mode' => 'shop', 'access' => 'paid',
            'teaser_sec' => 6, 'price' => '0', 'duration' => '6:00',
            'chapters' => $chapters, 'transcript' => $transcript, 'views' => 12, 'rating' => '4.6',
        ]);
    }

    private function linkWormholes(): void
    {
        $this->edge('fl-vault', 'fl-hojo', 'parent_of', 'Auteur');
        $this->edge('fl-vault', 'fl-cel-node', 'parent_of', 'Œuvre');
        $this->edge('fl-atelier-anime', 'fl-vault', 'parent_of', 'Cels');
        $this->edge('fl-territoire', 'fl-table', 'parent_of', 'Goût');
        $this->edge('fl-labo', 'vera', 'offers', 'Débouchés');
        $this->child('fl-midgar-blade', 'epee-midgar', 'product', 'Épée de Midgar', 'Loot', 'fl-terrain', '/realms/portal-hero.jpg');
        $this->edge('fl-terrain', 'fl-midgar-blade', 'parent_of', 'Loot');
    }

    private function child(string $id, string $slug, string $kind, string $title, string $sub, string $parent, string $hero): void
    {
        DB::table('nodes')->updateOrInsert(['id' => $id], [
            'slug' => $slug, 'kind' => $kind, 'title' => $title, 'subtitle' => $sub,
            'summary' => $sub, 'body' => '', 'hero' => $hero, 'skin' => 'living', 'featured' => 0, 'template' => '',
        ]);
        $this->edge($parent, $id, 'parent_of', $sub);
    }

    private function edge(string $from, string $to, string $kind, string $label): void
    {
        $exists = DB::table('edges')->where('from_id', $from)->where('to_id', $to)->where('kind', $kind)->exists();
        if (! $exists) {
            DB::table('edges')->insert(['from_id' => $from, 'to_id' => $to, 'kind' => $kind, 'label' => $label]);
        }
    }
}
