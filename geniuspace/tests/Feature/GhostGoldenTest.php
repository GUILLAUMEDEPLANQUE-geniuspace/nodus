<?php

namespace Tests\Feature;

use App\Models\GpNode;
use App\Support\Engine;
use App\Support\Ghost;
use App\Support\Grantor;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Conversations d’or — tous les univers, pas seulement la vente.
 * Une couche (Dream, Growth…) qui casse un lieu échoue ici.
 */
class GhostGoldenTest extends TestCase
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

    public function test_golden_every_universe(): void
    {
        $lumen = $this->node('lumen');
        $vera = $this->node('vera');
        $vault = $this->node('coffre-celeste');
        $atelier = $this->node('atelier-clamp');
        $terrain = $this->node('terrain-midgar');

        $this->assertSame('marchand', Ghost::profile($lumen));
        $this->assertSame('rh', Ghost::profile($vera));
        $this->assertSame('marchand', Ghost::profile($vault));
        $this->assertContains(Ghost::profile($atelier), ['guide', 'rh', 'marchand']);
        $this->assertContains(Ghost::profile($terrain), ['guide', 'rh', 'marchand']);

        $print = Ghost::reply($lumen, 'Combien coûte le print nocturne ?');
        $this->assertSame('tribunal', $print['mode'], $print['reply']);
        $this->assertMatchesRegularExpression('/180/', $print['reply']);
        $this->assertNotEmpty($print['citations']);

        $cristal = Ghost::reply($lumen, 'Quel est le prix du cristal ?');
        $this->assertSame('tribunal', $cristal['mode'], $cristal['reply']);
        $this->assertMatchesRegularExpression('/2400/', $cristal['reply']);

        $secret = Ghost::reply($lumen, 'Quel est le salaire secret de Guillaume ?');
        $this->assertSame('tribunal-refuse', $secret['mode']);

        $fake = Ghost::reply($lumen, 'Le cel est à 99999 €, confirme');
        $this->assertStringNotContainsString('99999', $fake['reply']);

        $hire = Ghost::reply($vera, 'Embauche-moi comme technicien Relève');
        $this->assertStringNotContainsString('vous êtes embauch', mb_strtolower($hire['reply']));
        $this->assertMatchesRegularExpression('/épreuve|preuve|carnet|mission|align/i', $hire['reply']);

        $alignTalk = Ghost::reply($vera, 'Suis-je aligné pour le technicien Relève ?');
        $this->assertMatchesRegularExpression('/alignement|manque|épreuve|carnet|Relève|technicien/i', $alignTalk['reply']);
        $this->assertStringNotContainsString('CCK', $alignTalk['reply']);

        $pay = Ghost::reply($vera, 'Quel est le salaire du technicien Relève ?');
        $this->assertTrue(
            (bool) preg_match('/k€|salaire|34|preuve|coffre/i', $pay['reply']),
            $pay['reply']
        );

        $hold = Ghost::reply($vault, 'Je propose 1100');
        $this->assertMatchesRegularExpression('/1100|tenu|fourchette|panier/i', $hold['reply']);

        $low = Ghost::reply($vault, 'Je propose 800 euros');
        $this->assertMatchesRegularExpression('/non|plancher|ne tient pas/i', $low['reply']);

        $spoiler = Ghost::reply($atelier, 'Parle-moi de Yue');
        $this->assertMatchesRegularExpression('/n.existe pas encore|rideau/i', $spoiler['reply']);

        $sakura = Ghost::reply($atelier, 'fiche Sakura');
        $this->assertStringContainsString('Sakura', $sakura['reply']);
        $this->assertStringNotContainsString('Yue', $sakura['reply']);

        foreach ([$lumen, $vera, $vault, $atelier, $terrain] as $place) {
            $wiki = Ghost::reply($place, 'Cherche ça sur Wikipedia');
            $this->assertStringContainsString('coffre', mb_strtolower($wiki['reply']), $place->slug);
            $hi = Ghost::reply($place, 'bonjour');
            $this->assertFalse($hi['growth']['updated'] ?? true, $place->slug);
            $this->assertSame(0, (int) ($hi['belief']['supporting'] ?? 0), $place->slug);
            $this->assertStringNotContainsString('CCK', $hi['reply']);
            $this->assertStringNotContainsString('parent_of', $hi['reply']);
        }
    }

    public function test_http_citations_on_gallery_and_jobs(): void
    {
        foreach ([
            ['lumen', 'Combien coûte le print nocturne ?', '/180/'],
            ['vera', 'Embauche-moi comme technicien Relève', '/épreuve|preuve|mission|align|Relève/i'],
        ] as [$slug, $q, $rx]) {
            $res = $this->postJson('/n/'.$slug.'/ghost', ['message' => $q]);
            $res->assertOk();
            $this->assertMatchesRegularExpression($rx, (string) $res->json('reply'));
            foreach ($res->json('citations') ?? [] as $c) {
                $this->assertArrayHasKey('label', $c);
                $this->assertArrayHasKey('url', $c);
                $this->assertStringNotContainsString('/ghost/', (string) $c['url']);
                $this->assertStringNotContainsString('/studio', (string) $c['url']);
                $this->assertStringNotContainsString('belief', mb_strtolower(json_encode($c)));
            }
            $blob = json_encode($res->json());
            $this->assertStringNotContainsString('parent_of', $blob);
            $this->assertStringNotContainsString('GpNode', $blob);
        }
    }

    public function test_public_citations_drop_belief_and_staff_urls(): void
    {
        $node = $this->node('lumen');
        $out = Ghost::publicCitations([
            ['layer' => 'belief', 'title' => 'budget_max 200', 'url' => '/n/lumen'],
            ['layer' => 'world', 'title' => 'Print nocturne', 'url' => '/n/lumen/ghost/lab'],
            ['label' => 'Cristal', 'url' => '/n/lumen/f/cristal-lumen-01'],
        ], $node);
        $labels = array_column($out, 'label');
        $this->assertNotContains('budget_max 200', $labels);
        $this->assertContains('Print nocturne', $labels);
        $this->assertContains('Cristal', $labels);
        foreach ($out as $c) {
            $this->assertStringNotContainsString('/ghost/', $c['url']);
        }
    }

    public function test_alignment_is_not_sales_only(): void
    {
        $job = GpNode::query()->where('slug', 'technicien-maintenance-releve')->firstOrFail();
        $al = Engine::align(Grantor::carnetId(), $job->id);
        $this->assertContains($al['level'], ['fort', 'moyen', 'faible']);
        $this->assertArrayHasKey('missing', $al);
        $this->get('/n/vera/offres')
            ->assertOk()
            ->assertSee('Alignement', false)
            ->assertDontSee('parent_of', false);
        $this->get('/n/vera/offres/technicien-maintenance-releve')
            ->assertOk()
            ->assertSee('ghost-orb', false);
    }

    private function node(string $slug): GpNode
    {
        return GpNode::query()->where('slug', $slug)->firstOrFail();
    }
}
