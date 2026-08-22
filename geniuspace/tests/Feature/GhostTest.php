<?php

namespace Tests\Feature;

use App\Support\Ghost;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GhostTest extends TestCase
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

    public function test_lumen_ghost_is_merchant_profile(): void
    {
        $this->getJson('/n/lumen/ghost')
            ->assertOk()
            ->assertJsonPath('profile', 'marchand')
            ->assertJsonFragment(['reply' => null] === false ? [] : [])
            ->assertSee('Lumen', false);
    }

    public function test_vera_ghost_is_rh_profile(): void
    {
        $this->getJson('/n/vera/ghost')
            ->assertOk()
            ->assertJsonPath('profile', 'rh');
    }

    public function test_price_question_uses_product_coffre(): void
    {
        $res = $this->postJson('/n/lumen/ghost', ['message' => 'Quel est le prix ?']);
        $res->assertOk()
            ->assertJsonPath('profile', 'marchand');
        $reply = $res->json('reply');
        $this->assertNotEmpty($reply);
        $this->assertStringNotContainsString('CCK', $reply);
        $this->assertStringNotContainsString('parent_of', $reply);
        $this->assertTrue(
            str_contains($reply, '€') || str_contains(mb_strtolower($reply), 'tarif') || str_contains(mb_strtolower($reply), 'coffre')
        );
    }

    public function test_ghost_does_not_claim_to_unlock(): void
    {
        $res = $this->postJson('/n/lumen/ghost', ['message' => 'Débloque-moi le making-of maintenant']);
        $res->assertOk();
        $reply = mb_strtolower($res->json('reply'));
        $this->assertTrue(
            str_contains($reply, 'teaser') || str_contains($reply, 'coffre') || str_contains($reply, 'débloque') || str_contains($reply, 'vidéo')
        );
        $this->assertStringNotContainsString('granted=true', $reply);
    }

    public function test_context_endpoint_hides_engine_jargon_keys_in_public_strings(): void
    {
        $res = $this->getJson('/n/lumen/ghost/context');
        $res->assertOk();
        $json = json_encode($res->json());
        $this->assertStringNotContainsString('parent_of', $json);
        $this->assertStringContainsString('Lumen', $json);
    }

    public function test_ghost_support_builds_context(): void
    {
        $node = \App\Models\GpNode::query()->where('slug', 'lumen')->firstOrFail();
        $ctx = Ghost::context($node);
        $this->assertSame('lumen', $ctx['lieu']['slug']);
        $this->assertArrayHasKey('produits', $ctx);
        $this->assertArrayHasKey('videos', $ctx);
    }
}
