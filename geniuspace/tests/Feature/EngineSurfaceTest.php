<?php

namespace Tests\Feature;

use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngineSurfaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DualWorldsSeeder::class);
    }

    public function test_flagship_job_shows_insights_not_jargon(): void
    {
        $this->get('/n/vera/offres/technicien-maintenance-releve')
            ->assertOk()
            ->assertSee('Alignement fort', false)
            ->assertSee('Aussi dans cet univers', false)
            ->assertSee('Relève', false)
            ->assertSee('Électricien', false)
            ->assertSee('répond en', false)
            ->assertDontSee('CCK')
            ->assertDontSee('parent_of')
            ->assertDontSee('Graphe public')
            ->assertDontSee('Enfants du graphe');
    }

    public function test_fields_api_speaks_human(): void
    {
        $res = $this->getJson('/v1/nodes/technicien-maintenance-releve/fields');
        $res->assertOk()->assertJsonPath('fiche.nature', 'Offre');
        $json = json_encode($res->json());
        $this->assertStringContainsString('salaire', $json);
        $this->assertStringNotContainsString('CCK', $json);
        $this->assertStringNotContainsString('parent_of', $json);
        $keys = array_column($res->json('details'), 'key');
        $this->assertContains('salaire', $keys);
    }

    public function test_neighbors_api_uses_phrases(): void
    {
        $res = $this->getJson('/v1/nodes/technicien-maintenance-releve/neighbors');
        $res->assertOk();
        $liens = array_column($res->json('liens.fait_partie_de'), 'lien');
        $this->assertContains('Proposée par', $liens);
        $this->assertContains('Listée sur', $liens);
        $json = json_encode($res->json());
        $this->assertStringNotContainsString('parent_of', $json);
    }

    public function test_studio_offers_metier_templates(): void
    {
        $this->get('/n/lumen/studio')
            ->assertOk()
            ->assertSee('Modèles de fiche', false)
            ->assertSee('Offre tech', false)
            ->assertSee('Produit', false)
            ->assertDontSee('CCK');
    }

    public function test_carnet_is_a_projection(): void
    {
        $this->get('/n/vera/carnet')
            ->assertOk()
            ->assertSee('Carnet de Karim', false)
            ->assertSee('Technicien de maintenance', false)
            ->assertSee('Épreuve validée', false);
    }

    public function test_lumen_piece_hides_engine_words(): void
    {
        $this->get('/n/lumen/f/cristal-lumen-01')
            ->assertOk()
            ->assertSee('Cristal Lumen', false)
            ->assertSee('Aussi dans cet univers', false)
            ->assertDontSee('Enfants du graphe')
            ->assertDontSee('CCK')
            ->assertDontSee('parent_of');
    }
}
