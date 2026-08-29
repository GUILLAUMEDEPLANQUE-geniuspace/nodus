<?php

namespace Tests\Unit;

use App\Models\GpNode;
use App\Support\ClusterGraph;
use App\Support\ElementCatalog;
use App\Support\EntityFloor;
use App\Support\EntityProposer;
use App\Support\SeoCompiler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EntityClusterTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shares_components_not_lore(): void
    {
        $this->assertSame('components', ElementCatalog::sharePolicy());
        $this->assertTrue(ElementCatalog::mayShare('city-3d'));
        $this->assertTrue(ElementCatalog::mayShare('personnage'));
        $preset = ElementCatalog::packPreset('manga-hub');
        $this->assertContains('personnages', $preset['rooms']);
        $this->assertContains('personnage', $preset['entities']);
        $this->assertContains('jutsu', $preset['entities']);
    }

    public function test_empty_fiche_is_not_indexable(): void
    {
        $world = $this->world();
        $thin = $this->fiche($world, 'kakashi-thin', 'Kakashi', 'character', 'n');
        $this->assertFalse(EntityFloor::published($thin));
        $this->assertFalse(EntityFloor::indexable($world, $thin));
        $this->assertSame('noindex,follow', EntityFloor::robots($world, $thin));
    }

    public function test_unique_published_fiche_is_a_spoke(): void
    {
        $world = $this->world();
        $kakashi = $this->fiche($world, 'kakashi', 'Kakashi', 'character', 'Hatake Kakashi, copieur, Konoha, ancien chef de l’équipe 7, maître du Raikiri. Fiche tenue pour le cluster.');
        $boruto = $this->fiche($world, 'boruto', 'Boruto', 'character', 'Boruto Uzumaki, nouvelle génération, fils de Naruto, académie et karma. Intention distincte.');
        $this->assertTrue(EntityFloor::indexable($world, $kakashi));
        $this->assertTrue(EntityFloor::indexable($world, $boruto));
        $g = ClusterGraph::of($world);
        $this->assertCount(2, array_filter($g['spokes'], fn ($s) => $s['indexable']));
        $locs = array_column(SeoCompiler::urls($world), 'loc');
        $this->assertTrue(collect($locs)->contains(fn ($u) => str_contains($u, '/f/kakashi')));
        $this->assertTrue(collect($locs)->contains(fn ($u) => str_contains($u, '/f/boruto')));
        $txt = SeoCompiler::llmsTxt($world);
        $this->assertStringContainsString('Kakashi', $txt);
        $this->assertStringContainsString('Boruto', $txt);
    }

    public function test_near_duplicate_is_blocked(): void
    {
        $world = $this->world();
        $this->fiche($world, 'k1', 'Kakashi', 'character', 'Le même texte de fiche Kakashi copieur Konoha Raikiri équipe sept pour le test.');
        $b = $this->fiche($world, 'k2', 'Hatake Kakashi', 'character', 'Le même texte de fiche Kakashi copieur Konoha Raikiri équipe sept pour le test.');
        $this->assertFalse(EntityFloor::uniqueEnough($world, $b));
    }

    public function test_empty_room_stays_out_of_sitemap(): void
    {
        $world = $this->world();
        DB::table('node_tabs')->insert([
            ['node_id' => $world->id, 'key' => 'forum', 'label' => 'Forum', 'icon' => 'spark', 'sort' => 1, 'enabled' => 1],
            ['node_id' => $world->id, 'key' => 'boutique', 'label' => 'Boutique', 'icon' => 'spark', 'sort' => 2, 'enabled' => 1],
        ]);
        $locs = array_column(SeoCompiler::urls($world), 'loc');
        $this->assertFalse(collect($locs)->contains(fn ($u) => str_ends_with($u, '/forum')));
        $this->assertFalse(collect($locs)->contains(fn ($u) => str_ends_with($u, '/boutique')));
    }

    public function test_proposer_does_not_write_nodes(): void
    {
        $world = $this->world();
        $before = GpNode::query()->count();
        $drafts = EntityProposer::fromText($world, "# Kakashi\nLieu: Konoha\nJutsu: Raikiri\n");
        $this->assertGreaterThanOrEqual(2, count($drafts));
        $this->assertFalse($drafts[0]['applied']);
        $this->assertSame('draft', $drafts[0]['status']);
        $this->assertSame($before, GpNode::query()->count());
    }

    private function world(): GpNode
    {
        return GpNode::query()->create([
            'id' => 'naruto-lab',
            'slug' => 'naruto-lab',
            'kind' => 'series',
            'title' => 'Naruto Lab',
            'subtitle' => '',
            'summary' => 'Cluster de test pour fiches indexables.',
            'body' => '',
            'hero' => '/realms/sea-hero.jpg',
            'skin' => 'living',
            'featured' => false,
        ]);
    }

    private function fiche(GpNode $world, string $slug, string $title, string $kind, string $copy): GpNode
    {
        $n = GpNode::query()->create([
            'id' => substr(md5($slug), 0, 12),
            'slug' => $slug,
            'kind' => $kind,
            'title' => $title,
            'subtitle' => '',
            'summary' => $copy,
            'body' => $copy,
            'hero' => $world->hero,
            'skin' => 'living',
            'featured' => false,
        ]);
        DB::table('edges')->insert([
            'from_id' => $world->id,
            'to_id' => $n->id,
            'kind' => 'parent_of',
            'label' => 'Fiche',
        ]);

        return $n;
    }
}
