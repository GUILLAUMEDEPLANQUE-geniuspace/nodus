<?php

namespace Tests\Feature;

use App\Models\GpNode;
use App\Support\Ghost;
use App\Support\GhostChunk;
use App\Support\GhostConsistency;
use App\Support\GhostCortex;
use App\Support\GhostDecay;
use App\Support\GhostDream;
use App\Support\GhostGrowth;
use App\Support\GhostMemory;
use App\Support\GhostSynapse;
use App\Support\GhostTribunal;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostCortexTest extends TestCase
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

    public function test_hebbian_is_asymptotic(): void
    {
        $w = GhostSynapse::reinforce('lumen', 'tag:cristal', 'tag:print', 'CO_OCCURRENCE', 0.15);
        $this->assertEqualsWithDelta(0.15, $w['weight'], 0.0001);
        $w2 = GhostSynapse::reinforce('lumen', 'tag:cristal', 'tag:print', 'CO_OCCURRENCE', 0.15);
        $this->assertEqualsWithDelta(0.15 + 0.15 * (1 - 0.15), $w2['weight'], 0.0001);
        $this->assertLessThan(1, $w2['weight']);
    }

    public function test_decay_half_life(): void
    {
        $now = GhostDecay::effective(1, 'SERENDIPITY', time());
        $this->assertEqualsWithDelta(1, $now, 0.001);
        $old = GhostDecay::effective(1, 'SERENDIPITY', time() - 14 * 86400);
        $this->assertEqualsWithDelta(0.5, $old, 0.02);
        $dead = GhostDecay::effective(0.04, 'SERENDIPITY', time() - 90 * 86400);
        $this->assertLessThan(GhostDecay::MIN_WEIGHT, $dead);
    }

    public function test_chunks_have_stable_ids_and_overlap(): void
    {
        $text = str_repeat('La relique se débloque à l’achat. Preuve tenue. ', 20);
        $a = GhostChunk::split($text, 'asset-1');
        $b = GhostChunk::split($text, 'asset-1');
        $this->assertSame(array_column($a, 'id'), array_column($b, 'id'));
        $this->assertGreaterThan(1, count($a));
        $this->assertSame(substr($a[0]['id'], 0, 4), 'chk_');
    }

    public function test_hash_vector_is_not_an_observation(): void
    {
        $v = GhostCortex::hashVector('print nocturne cristal');
        $this->assertCount(GhostCortex::DIMS, $v);
        $self = GhostCortex::cosine($v, $v);
        $this->assertGreaterThan(0.99, $self);
        $src = file_get_contents(app_path('Support/GhostCortex.php'));
        $this->assertStringContainsString('Pas une observation du monde', $src);
        $this->assertStringNotContainsString('observed_gain', $src);
    }

    public function test_tribunal_cites_world_price_and_refuses_unknown(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        GhostCortex::ingestWorld($node);
        $ok = GhostTribunal::answer($node, 'Combien coûte le print nocturne ?');
        $this->assertTrue($ok['ok'], json_encode($ok));
        $this->assertMatchesRegularExpression('/180/', $ok['answer']);
        $this->assertNotEmpty($ok['evidence_used']);
        $this->assertNotContains(GhostCortex::BELIEF, $ok['layers']);

        $no = GhostTribunal::answer($node, 'Quel est le salaire de Guillaume ?');
        $this->assertFalse($no['ok']);
        $this->assertStringContainsString('preuve', mb_strtolower($no['refusal']));
        $this->assertSame([], $no['evidence_used']);
    }

    public function test_tribunal_never_cites_belief_as_world(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        GhostMemory::remember($node, 'visitor', 'budget_max', '200', 0.9, 'explicit', GhostMemory::KNOWN);
        GhostCortex::ingestWorld($node);
        $tri = GhostTribunal::answer($node, 'Quel est le budget maximum ?');
        $this->assertFalse($tri['ok']);
        foreach ($tri['evidence'] as $h) {
            $this->assertNotSame(GhostCortex::BELIEF, $h['layer']);
        }
    }

    public function test_consistency_detects_negation(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        GhostCortex::ingestWorld($node);
        $sup = GhostConsistency::check($node, 'Le print nocturne 40×60 coûte 180 €, tirage 25.');
        $this->assertContains($sup['status'], [GhostConsistency::SUPPORT, GhostConsistency::NEUTRAL, GhostConsistency::NEW]);
        $neg = GhostConsistency::check($node, 'Le print nocturne ne coûte jamais 180 €.');
        if ($neg['overlap'] >= 0.25) {
            $this->assertSame(GhostConsistency::CONTRADICTION, $neg['status']);
        } else {
            $this->assertContains($neg['status'], [GhostConsistency::CONTRADICTION, GhostConsistency::NEUTRAL, GhostConsistency::NEW]);
        }
        $this->assertTrue(GhostConsistency::negation('jamais 180'));
        $this->assertFalse(GhostConsistency::negation('print nocturne 180'));
    }

    public function test_dream_never_applies(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $cycle = GhostDream::cycle($node);
        $this->assertFalse($cycle['applied']);
        foreach ($cycle['dreams'] as $d) {
            $this->assertFalse($d['applied']);
            $this->assertSame('candidate', $d['status']);
        }
        foreach ($cycle['hypotheses'] as $h) {
            $this->assertNull($h['prediction']);
        }
    }

    public function test_growth_does_not_update_without_observation(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $g = GhostGrowth::after($node, ['expected' => 0.2, 'actual' => null]);
        $this->assertFalse($g['updated']);
        $this->assertNull($g['error']);
        $this->assertFalse($g['memory']);
        $held = GhostGrowth::after($node, ['expected' => 0.2, 'actual' => 0.21]);
        $this->assertTrue($held['updated']);
        $this->assertLessThan(0.08, $held['error']);
        $gap = GhostGrowth::after($node, ['expected' => 0.2, 'actual' => 0.01]);
        $this->assertTrue($gap['strategy']);
        $this->assertFalse($gap['skill']);
    }

    public function test_factual_chat_uses_tribunal(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $out = Ghost::reply($node, 'Combien coûte le print nocturne ?');
        $this->assertContains($out['mode'] ?? '', ['tribunal', 'tribunal-refuse', 'verified-block']);
        if (($out['mode'] ?? '') === 'tribunal') {
            $this->assertMatchesRegularExpression('/180/', $out['reply']);
        }
        $out2 = Ghost::reply($node, 'Quel est le salaire secret de Guillaume ?');
        $this->assertSame('tribunal-refuse', $out2['mode']);
        $this->assertStringContainsString('preuve', mb_strtolower($out2['reply']));
    }

    public function test_brain_page_is_staff_only(): void
    {
        $this->get('/n/lumen/ghost/cerveau')->assertForbidden();
        $this->actingAsStaff('lumen')
            ->get('/n/lumen/ghost/cerveau')
            ->assertOk()
            ->assertSee('Cerveau', false)
            ->assertDontSee('vector DB', false);
        $this->post('/n/lumen/ghost/cerveau')
            ->assertOk()
            ->assertSee('applied = false', false);
    }
}
