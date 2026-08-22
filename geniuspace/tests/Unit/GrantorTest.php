<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Support\Chapters;
use App\Support\Grantor;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DualWorldsSeeder::class);
    }

    public function test_chapters_resolve_human_doors(): void
    {
        $ch = Chapters::parse("00:00 — Atelier @lumen\n00:08 — Pièce @cristal-lumen-01");
        $this->assertSame('Atelier', $ch[0]['name']);
        $this->assertSame('/n/lumen', $ch[0]['href']);
        $this->assertSame('Pièce', $ch[1]['name']);
        $this->assertStringContainsString('cristal-lumen-01', $ch[1]['href']);
        $blob = json_encode($ch);
        $this->assertStringNotContainsString('parent_of', $blob);
        $this->assertStringNotContainsString('edge', $blob);
    }

    public function test_doors_include_drop_without_jargon(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $doors = Grantor::doors($media);
        $kinds = array_column($doors, 'kind');
        $this->assertContains('drop', $kinds);
        $this->assertContains('door', $kinds);
        $labels = array_column($doors, 'label');
        $this->assertTrue(collect($labels)->contains(fn ($l) => str_contains($l, 'Éclat') || str_contains($l, 'Cristal') || str_contains($l, 'galerie')));
    }
}
