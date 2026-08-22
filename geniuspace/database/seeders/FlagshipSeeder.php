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
        DB::table('cck_fields')->where('node_id', $id)->where('name', 'Plancher')->update(['value' => '1080', 'field_key' => 'prix_plancher']);
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
        DB::table('cck_fields')->where('node_id', 'fl-table')->where('name', 'Plancher')->update(['value' => '74', 'field_key' => 'prix_plancher']);
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
