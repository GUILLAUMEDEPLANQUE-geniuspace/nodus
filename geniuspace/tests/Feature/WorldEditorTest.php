<?php

namespace Tests\Feature;

use App\Models\GpNode;
use App\Models\User;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorldEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        ]);
        $this->seed(DualWorldsSeeder::class);
        $this->user = User::factory()->create(['email' => 'gardien@geniuspace.test', 'name' => 'Gardien']);
        DB::table('node_staff')->insert(['node_id' => 'lumen', 'user_id' => $this->user->id, 'role' => 'owner']);
    }

    public function test_lumen_shop_uses_merch_labels(): void
    {
        $this->get('/n/lumen/boutique_expert')
            ->assertOk()
            ->assertSee('Acquérir l’œuvre', false)
            ->assertSee('Certificat', false)
            ->assertDontSee('Fiche complète SEO')
            ->assertDontSee('CCK')
            ->assertDontSee('node_actions');
    }

    public function test_lumen_hero_uses_preset_ctas(): void
    {
        $this->get('/n/lumen')
            ->assertOk()
            ->assertSee('Voir la vitrine', false)
            ->assertSee('Making-of', false)
            ->assertSee('Entrer dans la cimaise', false)
            ->assertDontSee('>Forum</a>', false);
    }

    public function test_lumen_player_paywall_copy_comes_from_chrome(): void
    {
        $this->get('/n/lumen/v/making-of-cristal-01')
            ->assertOk()
            ->assertSee('La relique est lockée', false)
            ->assertSee('Débloquer · 15 €', false)
            ->assertSee('Regarder', false)
            ->assertSee('Merch', false)
            ->assertDontSee('Suite premium');
    }

    public function test_hidden_tab_leaves_the_dock(): void
    {
        $id = DB::table('node_tabs')->where('node_id', 'lumen')->where('key', 'forum')->value('id');
        $this->actingAs($this->user)
            ->post('/n/lumen/monde/tab', ['id' => $id, 'label' => 'Salon', 'enabled' => 0, 'mode' => 'structure'])
            ->assertRedirect();
        $this->get('/n/lumen')
            ->assertOk()
            ->assertDontSee('>Salon</a>', false)
            ->assertSee('Vitrine', false);
    }

    public function test_rename_tab_offres_to_missions_on_new_vera_world(): void
    {
        $this->actingAs($this->user)
            ->post('/create', ['title' => 'Maison Nova', 'template' => 'vera-tech'])
            ->assertRedirect('/n/maison-nova/monde');
        $this->get('/n/maison-nova/monde')
            ->assertOk()
            ->assertSee('Voir les missions', false)
            ->assertSee('Tenter l’épreuve', false)
            ->assertDontSee('CCK')
            ->assertDontSee('node_theme');
        $tab = DB::table('node_tabs')->where('node_id', GpNode::query()->where('slug', 'maison-nova')->value('id'))->where('key', 'offres')->first();
        $this->actingAs($this->user)
            ->post('/n/maison-nova/monde/tab', ['id' => $tab->id, 'label' => 'Missions', 'enabled' => 1, 'mode' => 'structure']);
        $this->get('/n/maison-nova')
            ->assertOk()
            ->assertSee('Missions', false)
            ->assertSee('Voir les missions', false);
    }

    public function test_shop_button_can_be_relabeled(): void
    {
        $row = DB::table('node_actions')->where('node_id', 'lumen')->where('scope', 'shop_card')->where('action_key', 'add_cart')->first();
        $this->actingAs($this->user)
            ->post('/n/lumen/monde/action', [
                'id' => $row->id,
                'label' => 'Réserver le goodie',
                'variant' => 'primary',
                'enabled' => 1,
                'mode' => 'action',
            ])
            ->assertRedirect();
        $this->get('/n/lumen/boutique_expert')
            ->assertOk()
            ->assertSee('Réserver le goodie', false);
    }

    public function test_add_character_and_invite_mod(): void
    {
        $this->actingAs($this->user)
            ->post('/n/lumen/monde/child', [
                'title' => 'Inès',
                'kind' => 'person',
                'summary' => 'La galeriste',
                'mode' => 'structure',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('nodes', ['title' => 'Inès', 'kind' => 'person']);
        $this->actingAs($this->user)
            ->post('/n/lumen/monde/staff', [
                'email' => 'modo@geniuspace.test',
                'name' => 'Modo',
                'role' => 'mod',
                'mode' => 'structure',
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'modo@geniuspace.test']);
        $this->get('/n/lumen/f/ines')->assertOk()->assertSee('Inès', false);
    }

    public function test_guest_cannot_write_chrome(): void
    {
        $this->post('/n/lumen/monde/theme', ['primary' => '#ff0000'])->assertStatus(403);
    }

    public function test_flagship_vera_stays_the_jobboard(): void
    {
        $this->get('/n/vera')
            ->assertOk()
            ->assertSee('L’emploi', false)
            ->assertSee('enfin lisible', false)
            ->assertDontSee('Voir les missions');
    }

    public function test_editor_has_four_modes(): void
    {
        $this->actingAs($this->user)
            ->get('/n/lumen/monde')
            ->assertOk()
            ->assertSee('Structure', false)
            ->assertSee('Design', false)
            ->assertSee('Action', false)
            ->assertSee('Motion', false)
            ->assertSee('Première heure', false);
    }
}
