<?php

namespace Tests\Unit;

use App\Support\Engine;
use App\Support\Vocab;
use Tests\TestCase;

class EngineTest extends TestCase
{
    public function test_alignment_fort_is_plain_french(): void
    {
        $r = Engine::alignment(
            ['consignation', 'mécanique', 'hydraulique', 'gmao'],
            ['mécanique', 'hydraulique', 'consignation', 'gmao']
        );
        $this->assertSame('fort', $r['level']);
        $this->assertSame('Alignement fort', $r['word']);
        $this->assertStringNotContainsString('hop', json_encode($r));
        $this->assertStringNotContainsString('CCK', json_encode($r));
        $this->assertStringNotContainsString('parent_of', json_encode($r));
    }

    public function test_alignment_faible_when_metiers_diverge(): void
    {
        $r = Engine::alignment(['consignation'], ['figma', 'react', 'typescript']);
        $this->assertSame('faible', $r['level']);
        $this->assertSame('Alignement faible', $r['word']);
    }

    public function test_vocab_never_exposes_internal_words(): void
    {
        $this->assertSame('Maison', Vocab::kind('company'));
        $this->assertSame('Offre', Vocab::kind('job'));
        $this->assertSame('Proposée par', Vocab::parentPhrase('company'));
        $this->assertSame('Fait partie de', Vocab::parentPhrase('series'));
        $this->assertSame('Offres ouvertes', Vocab::childPhrase('job'));
        foreach (Vocab::banned() as $w) {
            $this->assertStringNotContainsString($w, Vocab::kind('company'));
            $this->assertStringNotContainsString($w, Vocab::edgePhrase('parent_of', 'Offre'));
        }
    }

    public function test_salary_surface_is_one_field_four_places(): void
    {
        $f = (object) ['field_key' => 'salaire', 'unit' => 'k€', 'min_val' => 34, 'max_val' => 40, 'value' => '34–40 k€', 'name' => 'Salaire'];
        $this->assertSame("34–40\u{00a0}k€", Engine::surface($f, 'badge'));
        $this->assertStringContainsString('Fourchette', Engine::surface($f, 'fiche'));
        $this->assertStringContainsString('k€', Engine::surface($f, 'jsonld'));
    }
}
