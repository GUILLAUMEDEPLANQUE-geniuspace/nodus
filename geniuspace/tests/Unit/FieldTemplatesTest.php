<?php

namespace Tests\Unit;

use App\Support\FieldTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FieldTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_covers_the_four_metiers(): void
    {
        $all = FieldTemplates::all();
        $this->assertArrayHasKey('offre-tech', $all);
        $this->assertArrayHasKey('offre-industrie', $all);
        $this->assertArrayHasKey('personnage', $all);
        $this->assertArrayHasKey('produit', $all);
        $this->assertSame('Salaire, télétravail, stack, séniorité, visa.', $all['offre-tech']['plain']);
        foreach ($all as $t) {
            $this->assertStringNotContainsString('CCK', $t['label'].$t['plain']);
        }
    }

    public function test_apply_creates_stable_keys_and_is_idempotent(): void
    {
        DB::table('nodes')->insert([
            'id' => 'x-job', 'slug' => 'x-job', 'kind' => 'job', 'title' => 'Test',
        ]);
        $n = FieldTemplates::apply('x-job', 'offre-tech');
        $this->assertGreaterThanOrEqual(5, $n);
        $this->assertSame(0, FieldTemplates::apply('x-job', 'offre-tech'));
        $keys = DB::table('cck_fields')->where('node_id', 'x-job')->pluck('field_key')->all();
        $this->assertContains('salaire', $keys);
        $this->assertContains('stack', $keys);
        $this->assertContains('visa', $keys);
    }

    public function test_industrie_has_caces_not_sql_column(): void
    {
        DB::table('nodes')->insert([
            'id' => 'x-ind', 'slug' => 'x-ind', 'kind' => 'job', 'title' => 'Tech',
        ]);
        FieldTemplates::apply('x-ind', 'offre-industrie');
        $keys = DB::table('cck_fields')->where('node_id', 'x-ind')->pluck('field_key')->all();
        $this->assertContains('habilitation', $keys);
        $this->assertContains('caces', $keys);
        $this->assertContains('trois_huit', $keys);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('nodes', 'salary'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('nodes', 'caces'));
    }
}
