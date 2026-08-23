<?php

namespace Tests\Feature;

use App\Models\GpNode;
use App\Models\User;
use App\Support\GhostEdit;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GhostActionTest extends TestCase
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
        GhostEdit::plantDemo('lumen');
    }

    public function test_plan_does_not_write(): void
    {
        $before = DB::table('cck_fields')->where('node_id', 'lumen')->where('field_key', 'salaire')->count();
        $res = $this->actingAs($this->user)->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ]);
        $res->assertOk();
        $this->assertSame('field.add', $res->json('action.ops.0.op'));
        $this->assertSame('preview', $res->json('action.status'));
        $this->assertSame($before, DB::table('cck_fields')->where('node_id', 'lumen')->where('field_key', 'salaire')->count());
        $this->assertStringNotContainsString('CCK', $res->json('action.preview.0'));
    }

    public function test_apply_writes_then_undo_restores(): void
    {
        $plan = $this->actingAs($this->user)->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ])->json('action');
        $this->actingAs($this->user)
            ->postJson('/n/lumen/ghost/apply', ['id' => $plan['id']])
            ->assertOk()
            ->assertJsonPath('action.status', 'applied');
        $this->assertDatabaseHas('cck_fields', ['node_id' => 'lumen', 'field_key' => 'salaire']);
        $this->actingAs($this->user)
            ->postJson('/n/lumen/ghost/undo', ['id' => $plan['id']])
            ->assertOk();
        $this->assertDatabaseMissing('cck_fields', ['node_id' => 'lumen', 'field_key' => 'salaire']);
    }

    public function test_apply_requires_staff(): void
    {
        $plan = $this->actingAs($this->user)->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ])->json('action');
        $this->asGuest()->postJson('/n/lumen/ghost/apply', ['id' => $plan['id']])->assertStatus(403);
    }

    public function test_refund_is_blocked(): void
    {
        $this->actingAs($this->user)
            ->postJson('/n/lumen/ghost/plan', ['message' => 'Rembourse cette commande'])
            ->assertOk()
            ->assertJsonPath('action.status', 'blocked')
            ->assertJsonPath('action.autonomy', 'deny');
    }

    public function test_editor_manifest_is_french(): void
    {
        $this->actingAs($this->user)
            ->getJson('/n/lumen/ghost/editor')
            ->assertOk()
            ->assertJsonPath('editor.page', 'Lumen')
            ->assertJsonFragment(['field.add'])
            ->assertJsonPath('editor.catalog.digits.label', 'Prix / nombre');
    }

    public function test_chat_counts_buyers(): void
    {
        $this->postJson('/n/lumen/ghost', ['message' => 'Quels clients ont acheté le cel ?'])
            ->assertOk()
            ->assertSee('143', false)
            ->assertJsonPath('skill', 'segment_customers');
    }

    public function test_campaign_is_prepare_not_send(): void
    {
        $res = $this->actingAs($this->user)->postJson('/n/lumen/ghost/plan', [
            'message' => 'Lance une campagne pour les clients qui ont acheté le cel et n\'ont pas le print. Propose-leur le print avec 15 % de réduction.',
        ]);
        $res->assertOk();
        $this->assertSame('prepare', $res->json('action.level'));
        $this->assertSame('confirm', $res->json('action.autonomy'));
        $this->assertSame('preview', $res->json('action.status'));
    }

    public function test_studio_shows_copilot(): void
    {
        $this->actingAs($this->user)
            ->get('/n/lumen/studio')
            ->assertOk()
            ->assertSee('Ghost veut modifier', false)
            ->assertSee('Curseur Ghost', false)
            ->assertDontSee('>CCK<', false);
    }

    public function test_act_tools_are_null(): void
    {
        $node = GpNode::query()->findOrFail('lumen');
        $this->assertNull(\App\Llm\GhostTools::call('campaign.launch', $node, [], []));
        $this->assertNull(\App\Llm\GhostTools::call('field.add', $node, [], []));
        $this->assertNull(\App\Llm\GhostTools::call('order.refund', $node, [], []));
        $read = \App\Llm\GhostTools::call('read_editor', $node, [], []);
        $this->assertSame('read_editor', $read['tool']);
    }
}
