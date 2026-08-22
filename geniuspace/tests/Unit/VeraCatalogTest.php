<?php

namespace Tests\Unit;

use App\Support\VeraCatalog;
use Tests\TestCase;

class VeraCatalogTest extends TestCase
{
    public function test_thirty_five_jobs_all_have_a_published_salary(): void
    {
        $jobs = VeraCatalog::jobs();
        $this->assertCount(35, $jobs);
        foreach ($jobs as $j) {
            $this->assertNotEmpty($j['title']);
            $this->assertNotNull($j['salaryMin']);
            $this->assertNotNull($j['salaryMax']);
            $this->assertArrayHasKey('company', $j);
        }
    }

    public function test_flagship_pack_has_honesty_and_simulation(): void
    {
        $job = VeraCatalog::job('technicien-maintenance-releve');
        $this->assertNotNull($job);
        $this->assertTrue($job['full']);
        $this->assertArrayHasKey('hard', $job['pack']['honesty']);
        $this->assertSame('machine', $job['pack']['sim']['kind']);
    }

    public function test_nav_is_plain_french(): void
    {
        $labels = array_column(VeraCatalog::nav(), 'label');
        $this->assertContains('Offres', $labels);
        $this->assertContains('Tests métier', $labels);
        $this->assertContains('Mon carnet', $labels);
        $this->assertNotContains('PPQC', $labels);
        $this->assertNotContains('Épreuve', $labels);
    }

    public function test_say_vulgarizes_jargon(): void
    {
        $this->assertSame('Test métier', VeraCatalog::say('epreuve')['word']);
        $this->assertSame('Candidat qualifié', VeraCatalog::say('ppqc')['word']);
        $this->assertSame('Délai de réponse', VeraCatalog::say('pacte')['word']);
        $this->assertSame('Carnet de preuves', VeraCatalog::say('passport')['word']);
        $this->assertSame('Fiabilité', VeraCatalog::say('honneur')['word']);
    }

    public function test_honor_caption_is_plain_french(): void
    {
        $this->assertSame('Toujours à l’heure', VeraCatalog::honorCaption(98, 4));
        $this->assertSame('Rate les délais', VeraCatalog::honorCaption(40, 4));
        $this->assertStringNotContainsString('Pacte', VeraCatalog::honorCaption(90, 2));
    }
}
