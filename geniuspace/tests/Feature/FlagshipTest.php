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
        $this->assertMatchesRegularExpression('/1100|tenu|clos|fourchette/i', $ok->json('reply'));

        $no = $this->postJson('/n/coffre-celeste/ghost', [
            'message' => 'Je propose 800 euros',
        ]);
        $no->assertOk();
        $this->assertMatchesRegularExpression('/non|plancher|ne tient pas/i', $no->json('reply'));
    }

    public function test_lore_stake_writes_a_proposal(): void
    {
        $this->get('/');
        $this->postJson('/n/coffre-celeste/preuve-lore', [
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
}
