<?php

namespace Tests\Feature;

use App\Llm\GhostTools;
use App\Models\GpNode;
use App\Models\Media;
use App\Models\User;
use App\Support\Acl;
use App\Support\GhostLearn;
use App\Support\GhostMaturity;
use App\Support\Grantor;
use App\Support\Invariants;
use App\Support\Pay;
use App\Support\SignedMedia;
use App\Support\Vocab;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Les 15 invariants. Si ça casse, le moteur n’est plus le moteur.
 */
class InvariantTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        ]);
        $this->seed(DualWorldsSeeder::class);
        $this->owner = User::factory()->create(['email' => 'owner-inv@geniuspace.test']);
        $this->stranger = User::factory()->create(['email' => 'stranger@geniuspace.test']);
        DB::table('node_staff')->insert(['node_id' => 'lumen', 'user_id' => $this->owner->id, 'role' => 'owner']);
    }

    public function test_fifteen_rules_are_named(): void
    {
        $this->assertCount(15, Invariants::rules());
        $this->assertSame(54, Invariants::AUTONOMY_CAP);
        $this->assertSame(Invariants::AUTONOMY_CAP, GhostMaturity::of()['autonomy']);
        $this->assertCount(5, Invariants::architecture());
        $this->assertArrayHasKey('I-TRUTH', Invariants::architecture());
        $this->assertArrayHasKey('I-CONTRACT', Invariants::architecture());
        $this->assertArrayHasKey('I-PROVENANCE', Invariants::architecture());
    }

    public function test_guest_cannot_write_studio_forum_drive_magazine_create(): void
    {
        $this->post('/n/lumen/studio/cck', ['name' => 'Hack', 'type' => 'text'])->assertStatus(403);
        $this->post('/n/lumen/studio/weave')->assertStatus(403);
        $this->post('/forum', ['slug' => 'lumen', 'title' => 'x', 'body' => 'y'])->assertStatus(403);
        $this->post('/n/lumen/blog', [
            'title' => 'x', 'resume' => 'y', 'body' => 'z z z z', 'theme' => 'club',
        ])->assertStatus(403);
        $this->post('/create', ['title' => 'Club pirate'])->assertStatus(403);
        $fileId = (int) (\App\Models\DriveFile::query()->value('id') ?: 0);
        $this->post('/drive/lock', ['id' => $fileId ?: 1])->assertStatus($fileId ? 403 : 404);
        $this->post('/studio/image', [
            'data' => 'data:image/jpeg;base64,QQ==', 'target' => 'hero', 'slug' => 'lumen',
        ])->assertStatus(403);
        $this->postJson('/n/lumen/ghost/apply', ['id' => 'x'])->assertStatus(403);
        $this->postJson('/n/lumen/ghost/skills', ['name' => 'hack'])->assertStatus(403);
        $this->getJson('/n/lumen/ghost/context')->assertStatus(403);
        $this->postJson('/builder/lumen/propose', ['prompt' => 'x'])->assertStatus(403);
        $this->postJson('/n/lumen/ghost/plan', ['message' => 'Ajoute un champ'])->assertStatus(403);
        $this->postJson('/n/coffre-celeste/preuve-lore', [
            'field' => 'Épisode', 'value' => '13',
        ])->assertStatus(403);
        $this->post('/n/lumen/ghost/gym')->assertStatus(403);
        $this->post('/n/lumen/ghost/lab', ['objective' => 'x'])->assertStatus(403);
        $this->post('/n/lumen/ghost/cerveau')->assertStatus(403);
        $this->post('/n/lumen/t/x/reply', ['body' => 'hello'])->assertStatus(403);
        $this->post('/n/lumen/t/x/fire')->assertStatus(403);
    }

    public function test_guest_cannot_open_sculpt_pages(): void
    {
        foreach ([
            '/n/lumen/studio',
            '/n/lumen/monde',
            '/builder/lumen',
            '/builder/lumen/state',
            '/n/lumen/ghost/gym',
            '/n/lumen/ghost/lab',
            '/n/lumen/ghost/cerveau',
            '/n/lumen/ghost/editor',
            '/n/lumen/radar',
        ] as $url) {
            $this->get($url)->assertStatus(403);
        }
        $this->getJson('/n/lumen/ghost/editor')->assertStatus(403);
    }

    public function test_logged_in_stranger_is_not_auto_promoted_owner(): void
    {
        $this->actingAs($this->stranger)
            ->post('/n/lumen/studio/cck', ['name' => 'Hack', 'type' => 'text'])
            ->assertStatus(403);
        $this->assertFalse(Acl::canWrite('lumen', 'mod'));
        $this->assertDatabaseMissing('node_staff', [
            'node_id' => 'lumen',
            'user_id' => $this->stranger->id,
        ]);
        $this->actingAs($this->stranger)->get('/n/lumen/studio')->assertStatus(403);
        $this->actingAs($this->stranger)->get('/n/lumen/monde')->assertStatus(403);
        $this->actingAs($this->stranger)->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ])->assertStatus(403);
    }

    public function test_demo_login_dead_in_production(): void
    {
        User::factory()->create(['email' => 'creator@geniuspace.test']);
        $this->get('/login/demo')->assertRedirect('/');
        $this->app['env'] = 'production';
        $this->get('/login/demo')->assertNotFound();
        $this->get('/llm/tools')->assertNotFound();
        $this->app['env'] = 'testing';
    }

    public function test_plan_does_not_write_apply_needs_staff(): void
    {
        $before = DB::table('cck_fields')->where('node_id', 'lumen')->count();
        $plan = $this->actingAs($this->owner)->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ]);
        $plan->assertOk()->assertJsonPath('action.status', 'preview');
        $this->assertSame($before, DB::table('cck_fields')->where('node_id', 'lumen')->count());
        $this->actingAs($this->stranger)
            ->postJson('/n/lumen/ghost/apply', ['id' => $plan->json('action.id')])
            ->assertStatus(403);
        $this->asGuest()->postJson('/n/lumen/ghost/apply', ['id' => $plan->json('action.id')])
            ->assertStatus(403);
    }

    public function test_act_tools_return_null_and_refund_is_denied(): void
    {
        $node = GpNode::query()->findOrFail('lumen');
        foreach (Invariants::ACT_TOOLS as $op) {
            $this->assertNull(GhostTools::call($op, $node, []), $op);
        }
        $this->actingAs($this->owner)
            ->postJson('/n/lumen/ghost/plan', ['message' => 'Rembourse cette commande'])
            ->assertJsonPath('action.autonomy', 'deny');
        $this->assertFalse(GhostLearn::mayRemember('hallucination'));
        $this->assertFalse(GhostLearn::mayRemember('hypothesis'));
    }

    public function test_nodes_have_no_salary_column(): void
    {
        $cols = Schema::getColumnListing('nodes');
        foreach (Invariants::BANNED_COLUMNS as $c) {
            $this->assertFalse(in_array($c, $cols, true), $c);
        }
    }

    public function test_play_rejects_traversal_and_preview_of_private(): void
    {
        $this->assertFalse(Invariants::safeRel('../.env'));
        $this->assertFalse(Invariants::safeRel('private/../../.env'));
        $this->assertNull(SignedMedia::fullPath('../.env'));
        $this->get(SignedMedia::sign('../.env', 900))->assertForbidden();
        $this->get(SignedMedia::sign('private/nope.mp4', 900, true))->assertForbidden();
    }

    public function test_gated_media_is_403_without_grant(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->assertNotNull($media);
        $this->assertTrue(Grantor::isGated($media));
        $this->assertFalse(Grantor::canSeeMedia($media));
        $full = SignedMedia::sign($media->path, 900, false, 'media:'.$media->id);
        $this->get($full)->assertForbidden();
    }

    public function test_held_price_never_under_floor(): void
    {
        $this->assertSame(1500, Invariants::heldCents(100, 1500));
        $this->assertSame(2000, Invariants::heldCents(2000, 1500));
        $this->assertSame(0, Pay::floorCents(null));
        $this->assertSame(0, Invariants::heldCents(-12, 0));
    }

    public function test_surface_hides_jargon(): void
    {
        foreach (['/n/lumen', '/n/vera', '/n/lumen/carnet', '/create'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            foreach (Vocab::banned() as $w) {
                $this->assertStringNotContainsString($w, $html, $url);
            }
        }
        $html = $this->actingAs($this->owner)->get('/n/lumen/studio')->assertOk()->getContent();
        $this->assertStringNotContainsString('parent_of', $html);
        $this->assertStringNotContainsString('GpNode', $html);
        $this->assertStringNotContainsString('LivingWorld', $html);
    }

    public function test_owner_can_still_sculpt(): void
    {
        $this->actingAs($this->owner)
            ->post('/n/lumen/studio/cck', ['name' => 'Note atelier', 'type' => 'text', 'value' => 'tenue'])
            ->assertRedirect();
        $this->assertDatabaseHas('cck_fields', ['node_id' => 'lumen', 'name' => 'Note atelier']);
        $this->actingAs($this->owner)->get('/n/lumen/studio')->assertOk();
        $this->actingAs($this->owner)->get('/n/lumen/monde')->assertOk();
        $this->actingAs($this->owner)->getJson('/n/lumen/ghost/editor')->assertOk();
    }

    public function test_ghost_stays_inside_the_pack(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $prompt = \App\Support\Ghost::systemPrompt($node);
        $this->assertStringContainsString('jamais d\'un crawl', $prompt);
        $this->assertFalse(GhostLearn::mayRemember('hallucination'));
    }
}
