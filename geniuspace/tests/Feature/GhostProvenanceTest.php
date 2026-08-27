<?php

namespace Tests\Feature;

use App\Support\GhostEdit;
use App\Support\GhostMemory;
use App\Support\GhostProvenance;
use App\Support\GhostVerifier;
use App\Models\GpNode;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GhostProvenanceTest extends TestCase
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
        GhostEdit::plantDemo('lumen');
        $this->actingAsStaff('lumen');
    }

    public function test_plan_does_not_write_provenance(): void
    {
        $this->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ])->assertOk()->assertJsonPath('action.status', 'preview');
        $this->assertSame(0, DB::table('ghost_transitions')->count());
        $this->assertDatabaseMissing('cck_fields', ['node_id' => 'lumen', 'field_key' => 'salaire']);
    }

    public function test_apply_records_produced_by_and_verified_by(): void
    {
        $plan = $this->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ])->json('action');
        $this->assertArrayHasKey('contract', $plan);
        $this->assertNotEmpty($plan['contract']['expected_state']['must_contain']);

        $this->postJson('/n/lumen/ghost/apply', ['id' => $plan['id']])
            ->assertOk()
            ->assertJsonPath('action.status', 'applied')
            ->assertJsonPath('action.verification.result', GhostVerifier::PASS);

        $row = GhostProvenance::of($plan['id']);
        $this->assertNotNull($row);
        $this->assertSame($plan['id'], $row['produced_by']);
        $this->assertIsArray($row['verified_by']);
        $this->assertSame(GhostVerifier::PASS, $row['verified_by']['result']);
        $this->assertNotEmpty($row['before_hash']);
        $this->assertNotEmpty($row['after_hash']);
        $this->assertNotSame($row['before_hash'], $row['after_hash']);
    }

    public function test_undo_records_undone_provenance(): void
    {
        $plan = $this->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ])->json('action');
        $this->postJson('/n/lumen/ghost/apply', ['id' => $plan['id']])->assertOk();
        $this->postJson('/n/lumen/ghost/undo', ['id' => $plan['id']])->assertOk();
        $this->assertDatabaseHas('ghost_transitions', [
            'action_id' => $plan['id'],
            'status' => 'undone',
        ]);
    }

    public function test_world_truth_and_belief_do_not_mix(): void
    {
        $node = GpNode::query()->findOrFail('lumen');
        $working = GhostMemory::load($node);
        GhostMemory::ingest($node, 'Je préfère les choses simples. Budget 200.', $working);
        GhostMemory::remember($node, 'world', 'prix', '99999', 0.99, 'hallucination', GhostMemory::KNOWN);
        GhostMemory::remember($node, 'world', 'prix', '99999', 0.99, 'explicit', GhostMemory::KNOWN);

        $this->assertSame('simple', GhostMemory::fact($node, 'prefers_style'));
        $beliefs = GhostMemory::agentBelief($node);
        $this->assertNotEmpty($beliefs);
        foreach ($beliefs as $b) {
            $this->assertSame('visitor', $b['subject']);
            $this->assertSame(GhostMemory::BELIEF, $b['realm']);
            $this->assertNotSame('engine', $b['source']);
        }
        foreach (GhostMemory::worldTruth($node) as $w) {
            $this->assertSame('world', $w['subject']);
            $this->assertSame('engine', $w['source']);
            $this->assertNotSame('99999', $w['object']);
        }
        $this->assertFalse(GhostMemory::mixed($node));
        $this->assertNull(GhostMemory::fact($node, 'prix'));
    }

    public function test_plan_exposes_manifest_deny_list(): void
    {
        $res = $this->postJson('/n/lumen/ghost/plan', [
            'message' => 'Ajoute un champ salaire après contrat',
        ]);
        $res->assertOk();
        $deny = $res->json('manifest.capabilities.deny');
        $this->assertContains('order.refund', $deny);
        $this->assertNotContains('order.refund', $res->json('manifest.capabilities.propose'));
        $this->assertSame(
            $res->json('manifest.capabilities.prepare'),
            $res->json('manifest.capabilities.propose')
        );
    }
}
