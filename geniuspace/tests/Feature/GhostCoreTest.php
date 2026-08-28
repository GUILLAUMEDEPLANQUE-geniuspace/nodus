<?php

namespace Tests\Feature;

use App\Models\GpNode;
use App\Support\Ghost;
use App\Support\GhostBelief;
use App\Support\GhostCore;
use App\Support\GhostCritic;
use App\Support\GhostHypothesis;
use App\Support\GhostLearn;
use App\Support\GhostMaturity;
use App\Support\GhostMemory;
use App\Support\GhostPlanner;
use App\Support\GhostSelfModel;
use App\Support\GhostSimulator;
use App\Support\GhostSituation;
use App\Support\GhostSkills;
use App\Support\Invariants;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            PreventRequestForgery::class,
        ]);
        $this->seed(DualWorldsSeeder::class);
    }

    public function test_offer_is_not_a_price_cap(): void
    {
        $sit = GhostSituation::parse('Je propose 1100');
        $this->assertSame('negotiate', $sit['goal']);
        $this->assertSame(1100, $sit['hard_constraints']['amount'] ?? null);
        $this->assertArrayNotHasKey('max_price', $sit['hard_constraints']);
    }

    public function test_situation_parses_hard_soft_and_tradeoff(): void
    {
        $sit = GhostSituation::parse('trouve-moi quelque chose de sombre autour de 200 €, mais si tu trouves mieux à 230 €, montre-le-moi quand même');
        $this->assertSame('find_best_product', $sit['goal']);
        $this->assertSame(200, $sit['hard_constraints']['max_price']);
        $this->assertSame('sombre', $sit['soft_preferences']['style']);
        $this->assertSame(230, $sit['tradeoffs'][0]['allow_price']);
        $this->assertSame('know', $sit['stance']);
    }

    public function test_situation_ingest_merges_llm_json_without_deciding_truth(): void
    {
        $base = GhostSituation::parse('cherche un produit');
        $sit = GhostSituation::ingest([
            'goal' => 'find_best_product',
            'hard_constraints' => ['max_price' => 180],
            'confidence' => 0.91,
        ], $base);
        $this->assertSame('find_best_product', $sit['goal']);
        $this->assertSame(180, $sit['hard_constraints']['max_price']);
        $this->assertEqualsWithDelta(0.91, $sit['confidence'], 0.001);
    }

    public function test_sales_objective_is_strategy_not_price(): void
    {
        $sit = GhostSituation::parse('Augmente mes ventes de 15 % sans augmenter le budget marketing.');
        $this->assertSame('discover_strategy', $sit['goal']);
        $this->assertTrue($sit['hard_constraints']['budget_neutral'] ?? false);
        $this->assertArrayNotHasKey('max_price', $sit['hard_constraints']);
    }

    public function test_critic_replans_act_ceiling(): void
    {
        $plan = ['skill' => 'run_campaign', 'ceiling' => GhostSkills::ACT, 'constraints' => []];
        $attack = GhostCritic::plan($plan, ['goal' => 'run_campaign']);
        $this->assertTrue($attack['severe']);
        $this->assertContains('authority', $attack['require']);
        $replanned = GhostPlanner::replan($plan, $attack);
        $this->assertSame(GhostSkills::PREPARE, $replanned['ceiling']);
        $this->assertTrue($replanned['replanned']);
    }

    public function test_simulator_never_observes(): void
    {
        $sims = GhostSimulator::of(['skill' => 'find_product'], [
            'hard_constraints' => ['max_price' => 200],
            'tradeoffs' => [['allow_price' => 230]],
        ]);
        $this->assertCount(3, $sims);
        foreach ($sims as $s) {
            $this->assertNull($s['observed']);
            $this->assertSame('simulation', $s['kind']);
        }
        $rec = GhostSimulator::recommend($sims);
        $this->assertSame('B', $rec['choose']);
        $this->assertSame('simulation', $rec['kind']);
    }

    public function test_self_model_caps_autonomy(): void
    {
        $self = GhostSelfModel::of();
        $this->assertSame(Invariants::AUTONOMY_CAP, $self['cap']);
        $this->assertSame(54, GhostMaturity::of()['autonomy']);
        $mx = GhostMaturity::matrix();
        $this->assertArrayHasKey('observation', $mx);
        $this->assertArrayHasKey('metacognition', $mx);
        $this->assertLessThanOrEqual(72, $mx['execution']);
        $this->assertFalse($self['capabilities']['refund']);
    }

    public function test_memory_has_five_layers(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $layers = GhostMemory::layers($node);
        foreach (['semantic', 'episodic', 'procedural', 'social', 'working'] as $k) {
            $this->assertArrayHasKey($k, $layers);
        }
        $this->assertContains('recover_failed_campaign', $layers['procedural']);
    }

    public function test_rivals_and_distinguish_seek_refutation(): void
    {
        $rivals = GhostHypothesis::rivals('la baisse vient du prix');
        $this->assertCount(5, $rivals);
        $this->assertSame('plancher', $rivals[0]['cause']);
        $this->assertStringContainsString('réfuterait', $rivals[0]['falsify']);
        $d = GhostCritic::distinguish([['levers' => ['prix']], ['levers' => ['ux']]]);
        $this->assertNotEmpty($d);
        $this->assertStringContainsString('réfuterait', $d[0]['falsify']);
    }

    public function test_reply_exposes_cognitive_loop(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $out = Ghost::reply($node, 'trouve-moi quelque chose de sombre autour de 200 €, mais si tu trouves mieux à 230 €, montre-le-moi quand même');
        $this->assertSame('find_product', $out['skill']);
        $this->assertSame(200, $out['situation']['hard_constraints']['max_price']);
        $this->assertSame(230, $out['situation']['tradeoffs'][0]['allow_price']);
        $this->assertArrayHasKey('critic', $out);
        $this->assertArrayHasKey('simulations', $out);
        $this->assertNull($out['simulations'][0]['observed']);
        $this->assertArrayHasKey('reflection', $out);
        $this->assertSame(54, $out['self']['cap']);
        $this->assertStringContainsString('observe.situation', $out['loop']);
        $this->assertStringNotContainsString('CCK', $out['reply']);
    }

    public function test_sales_turn_does_not_claim_observation(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $out = Ghost::reply($node, 'Augmente mes ventes de 15 % sans augmenter le budget marketing.');
        $this->assertSame('discover_strategy', $out['skill']);
        $this->assertStringContainsString('aucune observation du monde encore', $out['reply']);
        $this->assertSame('preview', $out['action']['status'] ?? null);
        $this->assertSame(GhostSkills::PREPARE, $out['permission']);
        foreach ($out['simulations'] as $s) {
            $this->assertNull($s['observed']);
        }
    }

    public function test_recover_failed_campaign_stays_prepare(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        GhostLearn::fail($node, 'campagne trop tôt', 'run_campaign', 'campaign_age', 'Ne pas réutiliser sous 14 jours.');
        $out = Ghost::reply($node, 'Récupère la campagne qui a échoué.');
        $this->assertSame('recover_failed_campaign', $out['skill']);
        $this->assertSame(GhostSkills::PREPARE, $out['permission']);
        $this->assertStringContainsString('confirmation humaine', mb_strtolower($out['reply']));
        $this->assertStringNotContainsString('je lance', mb_strtolower($out['reply']));
        $this->assertFalse(GhostSkills::may('campaign.launch', GhostSkills::PREPARE));
    }

    public function test_belief_is_a_probability(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $b = GhostBelief::observe($node, 'campaign X improves conversion', 'support', 'inactive');
        $b = GhostBelief::observe($node, 'campaign X improves conversion', 'support', 'inactive', $b);
        $b = GhostBelief::observe($node, 'campaign X improves conversion', 'contradict', 'inactive', $b);
        $this->assertGreaterThan(0.4, $b['p']);
        $this->assertLessThan(0.9, $b['p']);
        $this->assertSame(2, $b['supporting']);
        $this->assertSame(1, $b['contradicting']);
        $got = GhostBelief::get($node, 'campaign X improves conversion');
        $this->assertEqualsWithDelta($b['p'], $got['p'], 0.001);
    }

    public function test_generalize_writes_a_rule_from_repeated_failures(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        for ($i = 0; $i < 3; $i++) {
            GhostLearn::fail($node, 'campagne trop tôt '.$i, 'run_campaign', 'campaign_age', 'Ne pas réutiliser sous 14 jours.');
        }
        $out = GhostLearn::generalize($node);
        $this->assertNotEmpty($out);
        $this->assertTrue($out[0]['validated']);
        $this->assertDatabaseHas('ghost_rules', ['when_error' => 'campaign_age']);
    }

    public function test_skill_has_preconditions_and_versions(): void
    {
        $s = GhostSkills::get('recover_failed_campaign');
        $this->assertSame(GhostSkills::PREPARE, $s['level']);
        $this->assertContains('campaign status = failed', $s['preconditions']);
        $this->assertFalse(GhostSkills::ready('recover_failed_campaign', []));
        $this->assertTrue(GhostSkills::ready('recover_failed_campaign', ['campaign' => true, 'failed_campaign' => true]));
        $this->assertSame(1, GhostSkills::version('recover_failed_campaign'));
    }

    public function test_stance_can_be_dont_know(): void
    {
        $sit = GhostSituation::parse('je ne sais pas pourquoi ça baisse');
        $this->assertSame('dont_know', $sit['stance']);
    }

    public function test_core_does_not_write_grants(): void
    {
        foreach (['GhostCore.php', 'GhostSituation.php', 'GhostCritic.php', 'GhostSimulator.php', 'GhostReflector.php', 'GhostBelief.php', 'GhostSelfModel.php', 'GhostWorkingMemory.php'] as $f) {
            $src = file_get_contents(app_path('Support/'.$f));
            $this->assertStringNotContainsString('Grantor::give', $src);
            $this->assertStringNotContainsString("DB::table('grants')->insert", $src);
            $this->assertStringNotContainsString("DB::table('cck_fields')->insert", $src);
        }
    }

    public function test_tempo_has_four_loops(): void
    {
        $t = GhostCore::tempo();
        foreach (['fast', 'episode', 'learning', 'strategic'] as $k) {
            $this->assertArrayHasKey($k, $t);
        }
    }
}
