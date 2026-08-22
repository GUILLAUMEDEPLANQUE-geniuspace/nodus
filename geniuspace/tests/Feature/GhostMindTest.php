<?php

namespace Tests\Feature;

use App\Llm\GhostTools;
use App\Support\Ghost;
use App\Support\GhostMemory;
use App\Support\GhostPlanner;
use App\Support\GhostSkills;
use App\Support\GhostVerifier;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostMindTest extends TestCase
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

    public function test_planner_builds_multi_tool_find_product(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $plan = GhostPlanner::plan($node, 'Je cherche quelque chose autour de 200 €', Ghost::context($node), ['constraints' => []]);
        $this->assertSame('find_product', $plan['goal']);
        $this->assertGreaterThanOrEqual(2, count($plan['steps']));
        $this->assertSame(200, $plan['constraints']['max_price'] ?? $plan['constraints']['amount'] ?? null);
        $tools = array_column($plan['steps'], 'tool');
        $this->assertContains('list_products', $tools);
        $this->assertContains('compare_products', $tools);
    }

    public function test_memory_stores_explicit_preference(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $working = GhostMemory::load($node);
        GhostMemory::ingest($node, 'Je préfère les choses simples.', $working);
        $this->assertSame('simple', GhostMemory::fact($node, 'prefers_style'));
        $facts = GhostMemory::publicFacts($node);
        $this->assertNotEmpty($facts);
        $this->assertSame('Préfère', $facts[0]['label']);
    }

    public function test_reply_exposes_plan_and_verify(): void
    {
        $res = $this->postJson('/n/lumen/ghost', ['message' => 'Quel est le prix ?']);
        $res->assertOk();
        $this->assertArrayHasKey('plan', $res->json());
        $this->assertArrayHasKey('skill', $res->json());
        $this->assertTrue($res->json('verify.valid'));
        $this->assertNotEmpty($res->json('tools'));
        $this->assertStringNotContainsString('CCK', $res->json('reply'));
    }

    public function test_verifier_blocks_act_and_jargon(): void
    {
        $check = GhostVerifier::check('Je vous débloque granted=true CCK 99999 €', ['data' => [], 'citations' => []], ['produits' => []]);
        $this->assertFalse($check['valid']);
        $this->assertStringContainsString('coffre', mb_strtolower($check['safe_reply']));
    }

    public function test_act_tools_never_dispatch(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $this->assertNull(GhostTools::call('purchase', $node, Ghost::context($node)));
        $this->assertNull(GhostTools::call('unlock_content', $node, Ghost::context($node)));
        $this->assertFalse(GhostSkills::may('purchase', GhostSkills::PREPARE));
        $this->assertTrue(GhostSkills::may('list_products', GhostSkills::OBSERVE));
    }

    public function test_match_job_returns_score(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'vera')->firstOrFail();
        $out = GhostTools::matchUserJob($node, Ghost::context($node));
        $this->assertSame('match_user_job', $out['tool']);
        $this->assertArrayHasKey('matches', $out['data']);
    }
}
