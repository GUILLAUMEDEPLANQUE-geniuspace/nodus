<?php

namespace Tests\Feature;

use App\Support\Order;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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

    public function test_print_has_buyer_options_not_seller_jargon(): void
    {
        $fields = Order::fields('p-lu-2');
        $keys = array_map(fn ($f) => $f->field_key, $fields);
        $this->assertContains('taille', $keys);
        $this->assertContains('gravure', $keys);
        $this->assertContains('dos', $keys);
        $this->get('/n/lumen/boutique_expert')
            ->assertOk()
            ->assertSee('Taille', false)
            ->assertSee('Gravure', false)
            ->assertSee('Dos imprimé', false)
            ->assertDontSee('CCK')
            ->assertDontSee('audience');
    }

    public function test_extra_price_hits_the_cart(): void
    {
        $this->get('/');
        $this->post('/cart', [
            'product_id' => 'p-lu-2',
            'opt_taille' => 'M',
            'opt_gravure' => 'Inès',
            'opt_dos' => 'Oui',
        ])->assertRedirect();
        $this->get('/panier')
            ->assertOk()
            ->assertSee('Print nocturne', false)
            ->assertSee('gravure', false)
            ->assertSee('Inès', false)
            ->assertSee('185', false);
    }

    public function test_unique_piece_has_no_size_form(): void
    {
        $this->assertSame([], Order::fields('p-lu-1'));
    }
}
