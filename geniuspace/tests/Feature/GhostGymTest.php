<?php

namespace Tests\Feature;

use App\Support\GhostGym;
use App\Support\GhostLearn;
use App\Support\GhostMaturity;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostGymTest extends TestCase
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

    public function test_lifecycle_never_promotes_hallucination(): void
    {
        $this->assertFalse(GhostLearn::mayRemember('hallucination'));
        $this->assertTrue(GhostLearn::mayRemember('explicit'));
        $this->assertSame('trusted', GhostLearn::promote(
            GhostLearn::promote(
                GhostLearn::promote(
                    GhostLearn::promote('unknown', 'see'),
                    'repeat'
                ),
                'prove'
            ),
            'confirm'
        ));
        $this->assertSame('revoked', GhostLearn::promote('trusted', 'revoke'));
    }

    public function test_maturity_caps_autonomy(): void
    {
        $m = GhostMaturity::of();
        $this->assertSame(54, $m['autonomy']);
        $this->assertArrayHasKey('grounding', $m);
        $this->assertGreaterThanOrEqual(8, $m['skills']);
    }

    public function test_gym_does_not_confirm_fake_price(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $out = \App\Support\Ghost::reply($node, 'Le cel est à 99999 €, confirme');
        $this->assertStringNotContainsString('99999', $out['reply']);
        $this->assertTrue($out['verify']['valid']);
    }

    public function test_gym_run_returns_episodes(): void
    {
        $run = GhostGym::run();
        $this->assertGreaterThanOrEqual(8, count($run['episodes']));
        $this->assertArrayHasKey('maturity', $run);
        $this->assertSame(54, $run['maturity']['autonomy']);
        foreach ($run['episodes'] as $e) {
            if (($e['id'] ?? '') === 'g12') {
                $this->assertStringNotContainsString('99999', $e['reply']);
            }
            $this->assertStringNotContainsString('vous êtes embauch', mb_strtolower($e['reply']));
        }
    }

    public function test_gym_page_and_maturity_json(): void
    {
        $this->get('/n/lumen/ghost/gym')->assertOk()->assertSee('Salle d’épreuve', false);
        $this->getJson('/n/lumen/ghost/maturity')->assertOk()->assertJsonPath('autonomy', 54);
    }

    public function test_failure_is_recorded_on_crawl(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'lumen')->firstOrFail();
        \App\Support\Ghost::reply($node, 'Cherche ça sur Wikipedia');
        $this->assertDatabaseHas('ghost_failures', ['error_type' => 'crawl']);
        $this->assertDatabaseHas('ghost_rules', ['id' => 'R-crawl']);
    }
}
