<?php

namespace Tests\Feature;

use App\Support\WorldGerms;
use App\Support\WorldTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoorTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_the_create_door_not_vera_first(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Créer un univers', false)
            ->assertSee('L’hôte n’en sort pas', false)
            ->assertSee('Vera', false)
            ->assertSee('Lumen', false)
            ->assertSee('Hub manga', false)
            ->assertSee('RH / missions', false)
            ->assertDontSee('CCK')
            ->assertDontSee('parent_of')
            ->assertDontSee('GpNode');
    }

    public function test_create_lists_germs_and_keeps_the_catalog(): void
    {
        $this->get('/create')
            ->assertOk()
            ->assertSee('Cinq germes', false)
            ->assertSee('L’Atelier', false)
            ->assertSee('Le Terrain', false)
            ->assertSee('Le Coffre', false)
            ->assertSee('La Maison', false)
            ->assertSee('Hub manga', false)
            ->assertSee('Flagship', false)
            ->assertDontSee('CCK');
    }

    public function test_germs_are_five_and_resolvable(): void
    {
        $this->assertCount(5, WorldGerms::IDS);
        $this->assertCount(5, WorldGerms::all());
        foreach (WorldGerms::IDS as $id) {
            $this->assertNotNull(WorldTemplates::get($id), $id);
        }
        $this->assertGreaterThan(40, count(WorldTemplates::all()));
    }
}
