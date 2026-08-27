<?php

namespace Tests\Feature;

use App\Support\Ghost;
use App\Support\GhostAction;
use App\Support\GhostActionContract;
use App\Support\GhostPlanner;
use App\Support\GhostStrategy;
use App\Models\GpNode;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostStrategyTest extends TestCase
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

    public function test_generate_is_combinatorial_not_three_paraphrases(): void
    {
        $pool = GhostStrategy::generate('Augmenter la participation de 20 %.', 24);
        $this->assertGreaterThanOrEqual(12, count($pool));
        $sigs = [];
        foreach ($pool as $s) {
            $sigs[] = implode(',', $s['genome']['mechanisms']);
        }
        $this->assertGreaterThan(8, count(array_unique($sigs)));
    }

    public function test_fitness_is_performance_plus_novelty_minus_cost_risk(): void
    {
        $s = GhostStrategy::make('x', ['reward'], ['reward']);
        $sc = GhostStrategy::score($s);
        $this->assertEqualsWithDelta(
            $sc['performance'] + $sc['novelty'] - $sc['cost'] - $sc['risk'],
            $sc['fitness'],
            0.001
        );
    }

    public function test_recombine_keeps_both_parents(): void
    {
        $a = GhostStrategy::make('x', ['reward'], ['reward']);
        $b = GhostStrategy::make('x', ['social'], ['social']);
        $d = GhostStrategy::recombine($a, $b);
        $this->assertContains('reward', $d['genome']['mechanisms']);
        $this->assertContains('social', $d['genome']['mechanisms']);
        $this->assertContains('COMBINE', $d['genome']['mutations']);
    }

    public function test_mutations_change_the_genome(): void
    {
        $s = GhostStrategy::make('x', ['reward', 'social'], ['reward', 'social']);
        $rev = GhostStrategy::mutate($s, 'REVERSE');
        $this->assertSame(['social', 'reward'], $rev['genome']['sequence']);
        $delay = GhostStrategy::mutate($s, 'DELAY');
        $this->assertContains('DELAY', $delay['genome']['mutations']);
        $add = GhostStrategy::mutate($s, 'ADD');
        $this->assertGreaterThan(count($s['genome']['mechanisms']), count($add['genome']['mechanisms']));
    }

    public function test_plateau_triggers_exploration(): void
    {
        $this->assertFalse(GhostStrategy::plateau([0.62, 0.67, 0.71]));
        $this->assertTrue(GhostStrategy::plateau([0.72, 0.72, 0.72, 0.72]));
        $this->assertSame('explore', GhostStrategy::mode([0.72, 0.72, 0.72, 0.72]));
        $this->assertSame('exploit', GhostStrategy::mode([0.50, 0.60, 0.71, 0.80]));
    }

    public function test_challenge_hardens_the_winner(): void
    {
        $s = GhostStrategy::make('x', ['reward'], ['reward']);
        $attacks = GhostStrategy::challenge($s);
        $this->assertNotEmpty($attacks);
        $this->assertStringStartsWith('ATTACK', $attacks[0]['id']);
        $h = GhostStrategy::harden($s, $attacks);
        $this->assertNotSame($s['genome'], $h['genome']);
    }

    public function test_deploy_is_act_confirm_preview(): void
    {
        $s = GhostStrategy::make('x', ['friction', 'activation'], ['friction', 'activation']);
        $d = GhostStrategy::deploy($s);
        $this->assertSame(GhostAction::ACT, $d['level']);
        $this->assertSame(GhostAction::CONFIRM, $d['autonomy']);
        $this->assertSame('preview', $d['status']);
        $this->assertSame(GhostAction::AUTHORIZE, GhostAction::stageOf($d));
        $this->assertNotSame('applied', $d['status']);
    }

    public function test_lab_never_writes_a_grant(): void
    {
        $src = file_get_contents(app_path('Support/GhostStrategy.php'));
        $this->assertStringNotContainsString('Grantor::', $src);
        $board = GhostStrategy::lab('Augmenter la participation de 20 %.', 'lumen', true);
        $this->assertGreaterThan(5, $board['explored']);
        $this->assertArrayHasKey('best', $board);
        $this->assertSame('preview', $board['deploy']['status']);
        $this->assertDatabaseMissing('grants', ['node_id' => 'lumen', 'reason' => 'strategy']);
    }

    public function test_planner_routes_objective_to_strategy(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $plan = GhostPlanner::plan($node, 'Augmenter la participation de 20 %.', [], []);
        $this->assertSame('discover_strategy', $plan['skill']);
    }

    public function test_chat_returns_lab_without_deploying(): void
    {
        $node = GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $out = Ghost::reply($node, 'Augmenter la participation de 20 %.');
        $this->assertSame('discover_strategy', $out['skill']);
        $this->assertStringContainsString('Rien n’est déployé', $out['reply']);
        $this->assertSame('preview', $out['action']['status']);
        $this->assertGreaterThan(0, $out['lab']['explored']);
    }

    public function test_guest_cannot_open_lab(): void
    {
        $this->get('/n/lumen/ghost/lab')->assertStatus(403);
        $this->post('/n/lumen/ghost/lab', ['objective' => 'x'])->assertStatus(403);
        $this->postJson('/n/lumen/ghost/lab/deploy', ['code' => 'STR-0001'])->assertStatus(403);
    }

    public function test_staff_lab_run_and_deploy_stays_preview(): void
    {
        $this->actingAsStaff('lumen');
        $this->get('/n/lumen/ghost/lab')
            ->assertOk()
            ->assertSee('Jamais sur l’autorité', false)
            ->assertDontSee('CCK');
        $this->post('/n/lumen/ghost/lab', ['objective' => 'Augmenter la participation de 20 %.'])
            ->assertOk()
            ->assertSee('Meilleure', false)
            ->assertSee('Préparer le déploiement', false);
        $best = GhostStrategy::lab('Augmenter la participation de 20 %.', 'lumen', true)['best']['code'];
        $this->postJson('/n/lumen/ghost/lab/deploy', ['code' => $best])
            ->assertOk()
            ->assertJsonPath('applied', false)
            ->assertJsonPath('action.status', 'preview');
    }

    public function test_contract_drafts_strategy_ops(): void
    {
        $s = GhostStrategy::make('x', ['content', 'timing'], ['content', 'timing']);
        $a = GhostActionContract::draft(GhostStrategy::promote($s));
        $this->assertSame('strategy.promote', $a['action']);
        $this->assertContains('strategy.deploy', $a['contract']['allowed_operations']);
        $this->assertNotContains('order.refund', $a['contract']['allowed_operations']);
    }
}
