<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Grantor;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CoffreHardeningTest extends TestCase
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

    public function test_seeder_parses(): void
    {
        $file = database_path('seeders/DatabaseSeeder.php');
        $this->assertFileExists($file);
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($file).' 2>&1', $out, $code);
        $this->assertSame(0, $code, implode("\n", $out));
    }

    public function test_public_profile_hides_private_data(): void
    {
        $owner = User::factory()->create(['name' => 'Inès Privée']);
        $stranger = User::factory()->create();
        DB::table('notifications')->insert([
            'user_id' => $owner->id, 'title' => 'Secret notif', 'url' => '/secret',
        ]);
        DB::table('dm_messages')->insert([
            'from_id' => $stranger->id, 'to_id' => $owner->id, 'body' => 'Message privé coffre',
        ]);
        DB::table('inventory')->insert([
            'user_id' => $owner->id, 'kind' => 'relic', 'node_id' => 'lumen',
            'label' => 'Relique secrète', 'meta' => 'x',
        ]);

        $this->actingAs($stranger)->get('/profil/'.$owner->id)
            ->assertOk()
            ->assertSee('Inès Privée', false)
            ->assertDontSee('Secret notif', false)
            ->assertDontSee('Message privé coffre', false)
            ->assertDontSee('Relique secrète', false)
            ->assertDontSee('Notifications', false)
            ->assertDontSee('Messages', false);

        $this->actingAs($owner)->get('/profil')
            ->assertOk()
            ->assertSee('Secret notif', false)
            ->assertSee('Message privé coffre', false)
            ->assertSee('Relique secrète', false);
    }

    public function test_guest_profile_me_redirects_to_login(): void
    {
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_magazine_article_is_reachable(): void
    {
        $this->get('/n/lumen/blog/galerie-hologramme-vs-shopify')
            ->assertOk()
            ->assertSee('galerie hologramme', false);
        $this->get('/n/lumen/blog/art-lu-1')->assertOk();
    }

    public function test_forum_reply_rejects_foreign_thread(): void
    {
        $this->actingAsStaff('vera');
        $this->post('/n/vera/t/th-lu-1/reply', ['body' => 'intrus'])->assertNotFound();
    }

    public function test_bounty_claim_is_atomic_and_scoped(): void
    {
        $this->actingAsStaff('lumen');
        $id = DB::table('bounties')->insertGetId([
            'node_id' => 'lumen',
            'keyword' => 'cristal',
            'reward' => 100,
            'title_reward' => 'Expert cristal',
            'status' => 'open',
        ]);
        $this->post('/n/lumen/bounties/'.$id.'/claim')->assertRedirect();
        $this->assertDatabaseHas('bounties', ['id' => $id, 'status' => 'claimed']);
        $other = User::factory()->create();
        $this->actingAs($other)->post('/n/lumen/bounties/'.$id.'/claim')->assertForbidden();
        $this->actingAsStaff('vera')->post('/n/vera/bounties/'.$id.'/claim')->assertForbidden();
    }

    public function test_unlock_does_not_mint_a_proof(): void
    {
        $media = \App\Models\Media::query()->where('node_id', 'vera')->first();
        $this->postJson('/n/vera/v/'.$media->id.'/unlock')->assertForbidden();
        $this->assertFalse(Grantor::has('proof', 'vera'));
        $this->assertFalse(Grantor::has('media', (string) $media->id));
    }
}
