<?php

namespace Tests\Feature;

use App\Support\Flagships;
use App\Support\Ghost;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlagshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        ]);
        $this->seed(DualWorldsSeeder::class);
    }

    public function test_ten_flagships_exist_and_hide_jargon(): void
    {
        $all = Flagships::all();
        $this->assertCount(10, $all);
        foreach ($all as $f) {
            $blob = $f['label'].$f['pitch'].$f['ghost']['name'].$f['wormhole']['label'];
            $this->assertStringNotContainsString('CCK', $blob);
            $this->assertStringNotContainsString('parent_of', $blob);
            $this->assertNotSame('', $f['schema']);
        }
    }

    public function test_create_puts_flagships_first(): void
    {
        $this->get('/create')
            ->assertOk()
            ->assertSee('Le Coffre', false)
            ->assertSee('Le Terrain', false)
            ->assertSee('L’Atelier', false)
            ->assertSee('Le Territoire', false)
            ->assertSee('La Maison', false)
            ->assertSee('La Scène', false)
            ->assertSee('L’Arène', false)
            ->assertSee('Le Labo', false)
            ->assertSee('Le Plateau', false)
            ->assertSee('La Table', false)
            ->assertSee('Bible', false);
    }

    public function test_bible_is_the_dev_spec(): void
    {
        $this->get('/flagships')
            ->assertOk()
            ->assertSee('Ghost marchand', false)
            ->assertSee('JobPosting', false)
            ->assertDontSee('JoomCCK');
    }

    public function test_vault_canvas_is_a_product_place_not_a_grid(): void
    {
        $this->get('/n/coffre-celeste/p/p-cel-1')
            ->assertOk()
            ->assertSee('Cel : Ryo Saeba', false)
            ->assertSee('1 200', false)
            ->assertSee('Acquérir l’œuvre', false)
            ->assertSee('L’hôte', false)
            ->assertSee('Passage', false)
            ->assertSee('"@type":"Product"', false)
            ->assertDontSee('CCK')
            ->assertDontSee('parent_of')
            ->assertDontSee('graphe')
            ->assertDontSee('JoomCCK')
            ->assertDontSee('Ghost Node');
    }

    public function test_host_negotiates_inside_the_floor(): void
    {
        $this->get('/');
        $ok = $this->postJson('/n/coffre-celeste/ghost', [
            'message' => 'Je propose 1100',
        ]);
        $ok->assertOk();
        $this->assertMatchesRegularExpression('/1100|tenu|clos|fourchette|panier/i', $ok->json('reply'));
        $hrefs = collect($ok->json('actions'))->pluck('href')->implode(' ');
        $this->assertStringContainsString('/panier', $hrefs);
        $this->get('/panier')->assertOk()->assertSee('1100', false);

        $no = $this->postJson('/n/coffre-celeste/ghost', [
            'message' => 'Je propose 800 euros',
        ]);
        $no->assertOk();
        $this->assertMatchesRegularExpression('/non|plancher|ne tient pas/i', $no->json('reply'));
    }

    public function test_carnet_is_a_room_not_a_404(): void
    {
        $this->assertArrayHasKey('carnet', \App\Support\RoomCatalog::all());
        $this->get('/n/coffre-celeste/carnet')
            ->assertOk()
            ->assertSee('Carnet', false)
            ->assertDontSee('CCK');
        $this->get('/n/coffre-celeste/carnet.json')->assertOk();
    }

    public function test_bible_separates_flagship_and_start(): void
    {
        $this->get('/flagships')
            ->assertOk()
            ->assertSee('Flagship vs démarrage', false)
            ->assertSee('Ghost marchand', false);
    }

    public function test_range_reads_min_max_fields(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'coffre-celeste')->firstOrFail();
        $r = Ghost::range($node, ['prix' => '1 200 €']);
        $this->assertSame(1080, $r['floor']);
        $this->assertSame(1200, $r['ceil']);
    }

    public function test_eight_flagships_are_playable_homes(): void
    {
        $homes = [
            'coffre-celeste' => 'L’hôte',
            'terrain-midgar' => 'Maître du donjon',
            'atelier-clamp' => 'Le concierge',
            'territoire-japon' => 'Le guide',
            'scene-neon' => 'Le régisseur',
            'arene-reims' => 'Le speaker',
            'labo-next' => 'Le tuteur',
            'plateau-nuit' => 'L’AD',
            'table-aop' => 'Le sommelier',
        ];
        foreach ($homes as $slug => $host) {
            $this->get('/n/'.$slug)
                ->assertOk()
                ->assertSee($host, false)
                ->assertSee('Parler à', false)
                ->assertDontSee('CCK')
                ->assertDontSee('parent_of')
                ->assertDontSee('JoomCCK');
        }
    }

    public function test_lore_stake_writes_a_proposal(): void
    {
        $user = \App\Models\User::factory()->create();
        \Illuminate\Support\Facades\DB::table('users')->where('id', $user->id)->update(['nodecoins' => 500]);
        $this->actingAs($user)->postJson('/n/coffre-celeste/preuve-lore', [
            'field' => 'Épisode',
            'value' => '13',
            'stake' => 0,
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('lore_proposals', [
            'node_id' => 'fl-vault',
            'field_name' => 'Épisode',
            'value' => '13',
            'status' => 'pending',
        ]);
    }

    public function test_terrain_and_table_are_alive(): void
    {
        $this->get('/n/terrain-midgar')
            ->assertOk()
            ->assertSee('Maître du donjon', false)
            ->assertDontSee('CCK');
        $this->get('/n/table-aop/p/p-aop-1')
            ->assertOk()
            ->assertSee('Caisse AOP', false)
            ->assertSee('Le sommelier', false);
    }

    public function test_omni_labo_scene_arene_switch_the_frame(): void
    {
        $labo = \App\Models\GpNode::query()->where('slug', 'labo-next')->firstOrFail();
        $clip = $labo->media()->first();
        $this->assertNotNull($clip);
        $beats = \App\Support\Omni::beats($clip, $labo);
        $this->assertNotEmpty($beats);
        $this->assertSame('labo', $beats[0]['kind']);
        $this->get('/n/labo-next/v/'.$clip->id)
            ->assertOk()
            ->assertSee('omni-frame', false)
            ->assertSee('Tu ne regardes plus', false)
            ->assertDontSee('CCK');

        $scene = \App\Models\GpNode::query()->where('slug', 'scene-neon')->firstOrFail();
        $this->assertSame('mixer', \App\Support\Omni::beats($scene->media()->first(), $scene)[0]['kind']);
        $arene = \App\Models\GpNode::query()->where('slug', 'arene-reims')->firstOrFail();
        $this->assertSame('tactique', \App\Support\Omni::beats($arene->media()->first(), $arene)[0]['kind']);
    }

    public function test_atelier_concierge_filters_beyond_the_arc(): void
    {
        $this->get('/');
        $this->getJson('/n/atelier-clamp/ghost/context')->assertStatus(403);
        $node = \App\Models\GpNode::query()->where('slug', 'atelier-clamp')->firstOrFail();
        $ctx = Ghost::context($node);
        $titles = collect($ctx['liens']['contient'] ?? [])->pluck('titre')->implode(' ');
        $this->assertStringContainsString('Sakura', $titles);
        $this->assertStringNotContainsString('Yue', $titles);
        $this->assertLessThan(4, $ctx['rideau']['cursor']);

        $spoiler = $this->postJson('/n/atelier-clamp/ghost', ['message' => 'Parle-moi de Yue']);
        $spoiler->assertOk();
        $this->assertMatchesRegularExpression('/n.existe pas encore|rideau/i', $spoiler->json('reply'));

        $ok = $this->postJson('/n/atelier-clamp/ghost', ['message' => 'fiche Sakura']);
        $ok->assertOk();
        $this->assertStringContainsString('Sakura', $ok->json('reply'));
        $this->assertStringNotContainsString('Yue', $ok->json('reply'));
    }

    public function test_new_maison_rh_receives_vera_jobs(): void
    {
        $node = \App\Models\GpNode::query()->create([
            'id' => 'maison-demo',
            'slug' => 'maison-demo',
            'kind' => 'company',
            'title' => 'Maison Demo',
            'subtitle' => '',
            'summary' => '',
            'body' => '',
            'hero' => '/offer/releve-atelier.jpg',
            'skin' => 'vera',
            'template' => 'maison-rh',
            'featured' => 0,
        ]);
        \App\Support\WorldTemplates::apply($node, 'maison-rh');
        $n = \Illuminate\Support\Facades\DB::table('edges')->where('from_id', 'maison-demo')->where('kind', 'offers')->count();
        $this->assertSame(35, $n);
        $this->get('/n/maison-demo/offres')
            ->assertOk()
            ->assertSee('Offres', false);
        $this->get('/n/vera')->assertOk();
    }

    public function test_checkout_charges_the_held_price(): void
    {
        $this->get('/');
        $this->postJson('/n/coffre-celeste/ghost', ['message' => 'Je propose 1100'])->assertOk();
        $this->post('/cart/checkout')->assertRedirect('/panier');
        $this->assertDatabaseHas('ledger', [
            'product_id' => 'p-cel-1',
            'amount_cents' => 110000,
            'kind' => 'sale',
        ]);
    }

    public function test_geniuspedia_stays_inside_the_node(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'atelier-clamp')->firstOrFail();
        foreach (\App\Support\Geniuspedia::cards($node, 8) as $c) {
            $this->assertTrue(str_starts_with($c['url'], '/'), $c['url']);
            $this->assertContains($c['source'], ['pack', 'guide', 'magazine']);
            $this->assertStringNotContainsString('Finale', $c['titre']);
        }
        $this->assertStringContainsString('jamais d\'un crawl', \App\Support\Ghost::systemPrompt($node));
    }
}
