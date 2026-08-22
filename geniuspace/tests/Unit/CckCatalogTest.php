<?php

namespace Tests\Unit;

use App\Llm\CckCatalog;
use Tests\TestCase;

class CckCatalogTest extends TestCase
{
    public function test_eight_simple_types_in_french(): void
    {
        $simple = CckCatalog::simple();
        $this->assertCount(8, $simple);
        $labels = array_column($simple, 'label');
        $this->assertContains('Texte', $labels);
        $this->assertContains('Image', $labels);
        $this->assertContains('Lieu', $labels);
        $this->assertContains('Prix / nombre', $labels);
        $this->assertNotContains('Password', $labels);
    }

    public function test_advanced_mode_is_french(): void
    {
        $all = CckCatalog::all();
        $this->assertGreaterThan(20, count($all));
        $this->assertSame('Mot de passe', $all['password']['label']);
        $this->assertSame('Galerie', $all['gallery']['label']);
        $this->assertSame('Payant à télécharger', $all['pay_download']['label']);
    }
}
