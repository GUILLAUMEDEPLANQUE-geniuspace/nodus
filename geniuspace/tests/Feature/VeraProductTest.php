<?php

namespace Tests\Feature;

use App\Support\VeraCatalog;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VeraProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DualWorldsSeeder::class);
    }

    public function test_home_is_plain_french(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('L’emploi, lisible', false)
            ->assertSee('Voir les offres Vera', false)
            ->assertDontSee('LivingWorld')
            ->assertDontSee('VeraHouse');
    }

    public function test_vera_is_a_jobboard_not_a_campus(): void
    {
        $this->get('/n/vera')
            ->assertOk()
            ->assertSee('L’emploi', false)
            ->assertSee('enfin lisible', false)
            ->assertSee('Faut-il postuler', false)
            ->assertDontSee('Lead Game Designer')
            ->assertDontSee('Campus');
    }

    public function test_offres_list_has_real_jobs(): void
    {
        $this->get('/n/vera/offres')
            ->assertOk()
            ->assertSee('Technicien de maintenance', false)
            ->assertSee('Auxiliaire de vie', false)
            ->assertSee('Sable');
    }

    public function test_flagship_job_has_salary_honesty_and_schema(): void
    {
        $this->get('/n/vera/offres/technicien-maintenance-releve')
            ->assertOk()
            ->assertSee('P25', false)
            ->assertSee('Honnêteté', false)
            ->assertSee('JobPosting', false)
            ->assertSee('Karim');
    }

    public function test_lexique_explains_in_plain_french(): void
    {
        $this->get('/n/vera/lexique')
            ->assertOk()
            ->assertSee('dits simplement', false)
            ->assertSee('Pour vous', false);
    }

    public function test_lumen_gallery_still_lives(): void
    {
        $this->get('/n/lumen')->assertOk()->assertSee('Lumen');
    }

    public function test_offres_filter_is_plain_french(): void
    {
        $this->get('/n/vera/offres')
            ->assertOk()
            ->assertSee('Répondent à l’heure', false)
            ->assertSee('Fiabilité', false)
            ->assertDontSee('Pacte solide')
            ->assertDontSee('PPQC');
    }

    public function test_flagship_job_hides_internal_acronyms(): void
    {
        $this->get('/n/vera/offres/technicien-maintenance-releve')
            ->assertOk()
            ->assertSee('Réponse sous', false)
            ->assertSee('le profil est qualifié', false)
            ->assertDontSee('PPQC')
            ->assertDontSee('Pacte :');
    }

    public function test_studio_shows_visual_field_builder(): void
    {
        $this->actingAsStaff()
            ->get('/n/lumen/studio')
            ->assertOk()
            ->assertSee('Champs de la fiche', false)
            ->assertSee('Aperçu de la fiche', false)
            ->assertSee('Modèles de fiche', false)
            ->assertSee('Texte', false)
            ->assertSee('Lieu', false)
            ->assertDontSee('LivingWorld')
            ->assertDontSee('CCK');
    }

    public function test_nav_labels_on_vera_home(): void
    {
        $this->get('/n/vera')
            ->assertOk()
            ->assertSee('Tests métier', false)
            ->assertSee('Mon carnet', false)
            ->assertDontSee('PPQC')
            ->assertDontSee('Passport');
    }

    public function test_plain_french_urls_work(): void
    {
        $this->get('/n/vera/tarif')->assertOk()->assertSee('Vous ne payez que si quelqu’un réussit le test', false);
        $this->get('/n/vera/carnet')->assertOk()->assertSee('Carnet de preuves', false);
        $this->get('/n/vera/delais')->assertOk()->assertSee('Elles répondent', false);
        $this->get('/n/vera/ppqc')->assertOk();
        $this->get('/n/vera/passport')->assertOk();
        $this->get('/n/vera/pacte')->assertOk();
    }

    public function test_club_205_is_gone(): void
    {
        $this->get('/n/club-205')->assertNotFound();
    }
}
