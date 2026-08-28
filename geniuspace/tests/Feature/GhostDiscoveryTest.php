<?php

namespace Tests\Feature;

use App\Models\GpNode;
use App\Support\Ghost;
use App\Support\GhostExperiment;
use App\Support\GhostHypothesis;
use App\Support\GhostLearn;
use App\Support\GhostStrategy;
use App\Support\GhostWorldObserver;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GhostDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DualWorldsSeeder::class);
    }

    public function test_estimate_is_not_an_observation(): void
    {
        $s = GhostStrategy::estimate(GhostStrategy::make('x', ['fourchette'], ['fourchette']));
        $this->assertNull($s['observed_gain']);
        $this->assertSame('estimate', $s['kind']);
        $this->assertSame('untested', $s['status']);
        $this->assertNotNull($s['expected_gain']);
        $sim = GhostStrategy::simulate(GhostStrategy::make('x', ['salon'], ['salon']));
        $this->assertNull($sim['observed_gain']);
    }

    public function test_classify_never_promotes_an_estimate_to_winner(): void
    {
        $s = GhostStrategy::make('x', ['teaser', 'portes'], ['teaser', 'portes']);
        $s['observed_gain'] = null;
        $s['risk'] = 0.05;
        $this->assertSame('untested', GhostStrategy::classify($s));
        $s['observed_gain'] = 0.2;
        $s['evidence'] = 'world';
        $this->assertSame('winner', GhostStrategy::classify($s));
    }

    public function test_decompose_does_not_dump_all_levers(): void
    {
        $p = GhostStrategy::decompose('Augmenter la participation de 20 %.');
        $this->assertSame('increase_participation', $p['key']);
        $this->assertSame('participation', $p['metric']);
        $this->assertLessThan(count(GhostStrategy::LEVERS), count($p['levers']));
        $this->assertContains('salon', $p['levers']);
        $this->assertArrayHasKey('salon', $p['why']);
        $c = GhostStrategy::decompose('Tenir plus d’épreuves et publier les délais.');
        $this->assertSame('increase_preuves', $c['key']);
        $this->assertNotEquals($p['levers'], $c['levers']);
    }

    public function test_add_does_not_always_pick_the_first_unused_lever(): void
    {
        $a = GhostStrategy::mutate(GhostStrategy::make('x', ['fourchette'], ['fourchette']), 'ADD');
        $b = GhostStrategy::mutate(GhostStrategy::make('x', ['teaser'], ['teaser']), 'ADD');
        $this->assertNotSame($a['genome']['mechanisms'][1] ?? null, $b['genome']['mechanisms'][1] ?? 'same');
    }

    public function test_observer_reads_the_world_not_a_formula(): void
    {
        $w = GhostWorldObserver::of('lumen');
        $this->assertSame('engine', $w['source']);
        $this->assertGreaterThan(0, $w['media']);
        $this->assertGreaterThan(0, $w['threads']);
        $this->assertSame(1, $w['replies']);
        $this->assertGreaterThan(0, $w['products']);
        $this->assertIsInt($w['fields']);
        $src = file_get_contents(app_path('Support/GhostWorldObserver.php'));
        $this->assertStringNotContainsString('Grantor::', $src);
        $this->assertStringNotContainsString('crc32', $src);
    }

    public function test_catalog_contrast_is_not_an_experiment(): void
    {
        $world = GhostWorldObserver::of('lumen');
        $this->assertSame('marchand', $world['profile']);
        $this->assertArrayHasKey('gaps', $world);
        $s = GhostStrategy::estimate(GhostStrategy::make('Tenir plus de preuves d’achat.', ['plancher'], ['plancher']), [], $world);
        $h = GhostHypothesis::raise(GhostStrategy::decompose('Tenir plus de preuves d’achat.', $world), $world)[0];
        $trial = GhostExperiment::evaluate(GhostExperiment::observe(GhostExperiment::design($s, $h, $world, 'lumen'), $world));
        $this->assertNull($trial['observed']);
        $this->assertFalse($trial['causal_claim'] ?? true);
        $this->assertSame(GhostExperiment::AWAITING, $trial['status']);
        $this->assertSame('none', $trial['evidence']);

        DB::table('threads')->insert([
            'id' => 'th-lu-cold', 'node_id' => 'lumen', 'kind' => 'forum',
            'title' => 'Silence', 'author' => 'Nemo', 'body' => 'Personne.',
            'views' => 80, 'fires' => 0, 'replies_count' => 0,
        ]);
        $world2 = GhostWorldObserver::of('lumen');
        $assoc = GhostWorldObserver::associations($world2);
        foreach ($assoc as $a) {
            $this->assertSame('catalog_contrast', $a['method']);
            $this->assertNotSame('natural_experiment', $a['method']);
        }
        $trial2 = GhostExperiment::evaluate(GhostExperiment::observe(GhostExperiment::design($s, $h, $world2, 'lumen'), $world2));
        $this->assertNull($trial2['observed'], 'un catalogue n’observe pas');
        $this->assertSame(GhostExperiment::AWAITING, $trial2['status']);
    }

    public function test_observe_needs_an_intervention_and_enough_events(): void
    {
        $world = GhostWorldObserver::of('lumen');
        $s = GhostStrategy::estimate(GhostStrategy::make('Tenir plus de preuves.', ['certificat'], ['certificat']), [], $world);
        $h = GhostHypothesis::raise(GhostStrategy::decompose('Tenir plus de preuves.', $world), $world)[0];
        $trial = GhostExperiment::design($s, $h, $world, 'lumen');
        $this->assertNull(GhostExperiment::observe($trial, $world)['observed']);

        DB::table('ghost_actions')->insert([
            'id' => 'ga-deploy-1',
            'node_id' => 'lumen',
            'action' => 'strategy.deploy',
            'level' => 'ACT',
            'autonomy' => 'CONFIRM',
            'ops' => json_encode([['op' => 'strategy.deploy', 'code' => $s['code']]]),
            'preview' => json_encode(['ok']),
            'status' => 'applied',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
        $under = GhostExperiment::observe($trial, $world);
        $this->assertSame(GhostExperiment::UNDERPOWERED, $under['status']);
        $this->assertNull($under['observed']);

        $now = now();
        for ($i = 1; $i <= 4; $i++) {
            DB::table('grants')->insert([
                'user_id' => $i,
                'session_id' => 's-'.$i,
                'node_id' => 'lumen',
                'subject_type' => 'proof',
                'subject_id' => 'p-'.$i,
                'reason' => 'preuve',
                'created_at' => $now,
            ]);
        }
        $ok = GhostExperiment::evaluate(GhostExperiment::observe($trial, GhostWorldObserver::of('lumen')));
        $this->assertSame(GhostExperiment::OBSERVED, $ok['status']);
        $this->assertSame('world', $ok['evidence']);
        $this->assertNotNull($ok['observed']);
        $this->assertFalse($ok['causal_claim']);
        $this->assertSame('interrupted_time_series', $ok['causal_method']);
        $this->assertStringContainsString('causalité établie', $ok['stopping_reason']);
    }

    public function test_falsify_prefers_refutation(): void
    {
        $h = GhostHypothesis::make([], GhostHypothesis::problem('x'), 'obs', 'hyp', 'pred', 0.6, 'counter', ['fourchette'], null);
        $yes = GhostHypothesis::falsify($h, ['observed' => 0.12, 'prediction' => 0.14, 'n' => 8]);
        $this->assertSame(GhostHypothesis::SUPPORTED, $yes['status']);
        $no = GhostHypothesis::falsify($h, ['observed' => -0.08, 'prediction' => 0.12, 'n' => 8]);
        $this->assertSame(GhostHypothesis::REFUTED, $no['status']);
        $this->assertStringContainsString('négative', $no['verdict']);
        $unk = GhostHypothesis::falsify($h, ['observed' => null, 'prediction' => 0.12, 'n' => 1]);
        $this->assertSame(GhostHypothesis::UNKNOWN, $unk['status']);
    }

    public function test_surprise_is_abs_observed_minus_predicted(): void
    {
        $h = GhostHypothesis::make([], GhostHypothesis::problem('x'), 'o', 'h', 'p', 0.5, 'c', ['teaser'], null);
        $e = GhostHypothesis::falsify($h, ['observed' => 0.178, 'prediction' => 0.042, 'n' => 10]);
        $this->assertEqualsWithDelta(0.136, $e['surprise'], 0.001);
    }

    public function test_concepts_are_structured_not_judged(): void
    {
        $p = GhostHypothesis::problem('Augmenter la participation.');
        $hs = GhostHypothesis::ingestConcepts(['réputation', 'défi', 'plancher'], $p);
        $this->assertCount(3, $hs);
        $this->assertSame('honneur', $hs[0]['levers'][0]);
        $this->assertSame(GhostHypothesis::DRAFT, $hs[0]['status']);
    }

    public function test_lab_does_not_count_estimates_as_observed_experiments(): void
    {
        $board = GhostStrategy::lab('Augmenter la participation de 20 %.', 'lumen', true);
        $this->assertArrayHasKey('world', $board);
        $this->assertArrayHasKey('hypotheses', $board);
        $this->assertSame(0, $board['experiments_observed']);
        $this->assertSame(0, $board['winners']);
        $this->assertNull($board['best']['observed_gain']);
        $this->assertGreaterThan(0, $board['world']['threads']);
        $this->assertStringContainsString('observation', $board['honesty']);
        $this->assertDatabaseCount('ghost_strategy_experiments', $board['experiments']);
        $this->assertDatabaseMissing('grants', ['reason' => 'strategy']);
    }

    public function test_learn_records_a_refutation_as_future_ban(): void
    {
        $node = GpNode::query()->find('lumen');
        $this->assertNotNull($node);
        $trial = [
            'hypothesis' => [
                'status' => GhostHypothesis::REFUTED,
                'hypothesis' => 'La récompense suffit.',
                'levers' => ['fourchette'],
            ],
            'observed' => -0.08,
            'surprise' => 0.2,
        ];
        GhostLearn::afterExperiment('lumen', $trial);
        $this->assertContains('fourchette', GhostLearn::strategyBans('lumen'));
        $this->assertDatabaseHas('ghost_failures', ['error_type' => 'strategy_refuted']);
    }

    public function test_chat_does_not_claim_a_fake_observed_gain(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $out = Ghost::reply($node, 'Augmenter la participation de 20 %.');
        $this->assertStringContainsString('Prédiction', $out['reply']);
        $this->assertStringContainsString('aucune observation du monde encore', $out['reply']);
        $this->assertStringNotContainsString('gain observé', $out['reply']);
        $this->assertSame('preview', $out['action']['status']);
    }

    public function test_kernel_keeps_discovery_inside_ghost(): void
    {
        $engine = file_get_contents(app_path('Support/Engine.php'));
        foreach (['GhostHypothesis', 'GhostExperiment', 'GhostWorldObserver', 'GhostStrategy', 'GhostCore', 'GhostSituation', 'GhostCritic', 'GhostSimulator', 'GhostReflector', 'GhostBelief', 'GhostSelfModel'] as $ban) {
            $this->assertStringNotContainsString($ban, $engine, $ban);
        }
        foreach (['GhostHypothesis.php', 'GhostExperiment.php', 'GhostWorldObserver.php', 'GhostCore.php', 'GhostCritic.php', 'GhostSimulator.php', 'GhostReflector.php'] as $f) {
            $src = file_get_contents(app_path('Support/'.$f));
            $this->assertStringNotContainsString('Grantor::give', $src);
            $this->assertStringNotContainsString("DB::table('cck_fields')->insert", $src);
            $this->assertStringNotContainsString("DB::table('grants')->insert", $src);
        }
        $strat = file_get_contents(app_path('Support/GhostStrategy.php'));
        $this->assertStringNotContainsString('jitter', $strat);
        $this->assertStringContainsString("\$s['observed_gain'] = null;", $strat);
    }
}
