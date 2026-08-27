<?php

namespace Tests\Feature;

use App\Models\DriveFile;
use App\Models\Media;
use App\Support\Grantor;
use App\Support\SignedMedia;
use Database\Seeders\DualWorldsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantTest extends TestCase
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

    private function postGrant(string $url, array $data = [])
    {
        $this->get('/');

        return $this->withSession(['_token' => 'test-token'])->postJson($url, $data + ['_token' => 'test-token']);
    }

    public function test_gated_video_hides_full_file_from_schema(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->assertNotNull($media);
        $this->get('/n/lumen/v/'.$media->id)
            ->assertOk()
            ->assertSee('Making-of Cristal', false)
            ->assertDontSee('contentUrl')
            ->assertDontSee('private/media')
            ->assertDontSee('CCK')
            ->assertDontSee('parent_of')
            ->assertDontSee('graphe');
    }

    public function test_unlock_without_proof_or_purchase_is_forbidden(): void
    {
        $media = Media::query()->where('node_id', 'vera')->first();
        $this->assertNotNull($media);
        $this->assertFalse(Grantor::canSeeMedia($media));
        $this->postGrant('/n/vera/v/'.$media->id.'/unlock')->assertForbidden();
        $this->assertFalse(Grantor::canSeeMedia($media));
        $this->assertFalse(Grantor::has('proof', 'vera'));
    }

    public function test_unlock_after_verified_proof_is_a_server_grant(): void
    {
        $media = Media::query()->where('node_id', 'vera')->first();
        $this->assertNotNull($media);
        $this->get('/');
        Grantor::recordVerifiedProof('vera', 'Épreuve consignation', 'quest');
        $this->assertFalse(Grantor::canSeeMedia($media));

        $res = $this->postGrant('/n/vera/v/'.$media->id.'/unlock');
        $res->assertOk()->assertJsonPath('granted', true);
        $this->assertTrue(Grantor::canSeeMedia($media));
        $this->assertStringContainsString('/play?', $res->json('src'));
        $this->assertStringContainsString('preuve', json_encode($res->json()));
    }

    public function test_shop_unlock_requires_purchase(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertForbidden();
        $pid = (string) \App\Models\Product::query()->where('node_id', 'lumen')->value('id');
        Grantor::grantProduct($pid, 'lumen', 'Cristal');
        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertOk()->assertJsonPath('granted', true);
        $this->assertTrue(Grantor::canSeeMedia($media));
    }

    public function test_full_signed_url_requires_grant(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $full = SignedMedia::sign($media->path, 900, false, 'media:'.$media->id);
        $this->get($full)->assertForbidden();

        $preview = SignedMedia::sign($media->path, 900, true, 'media:'.$media->id);
        $this->get($preview)->assertForbidden();

        $this->get('/n/lumen/v/'.$media->id)
            ->assertOk()
            ->assertSee('/media/teaser.mp4', false);

        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertForbidden();
        $pid = (string) \App\Models\Product::query()->where('node_id', 'lumen')->value('id');
        Grantor::grantProduct($pid, 'lumen', 'Cristal');
        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertOk();
        $this->get($full)->assertOk();
    }

    public function test_quest_unlock_opens_brief_and_carnet(): void
    {
        $media = Media::query()->where('node_id', 'vera')->first();
        $brief = DriveFile::query()->where('title', 'Brief consignation Relève')->first();
        $this->assertNotNull($brief);
        $this->assertFalse(Grantor::canSeeFile($brief));

        $this->postGrant('/n/vera/v/'.$media->id.'/unlock')->assertForbidden();
        $this->assertFalse(Grantor::canSeeFile($brief));

        $this->get('/');
        Grantor::recordVerifiedProof('vera', 'Épreuve consignation', 'quest');
        $this->assertTrue(Grantor::canSeeFile($brief));
        $this->postGrant('/n/vera/v/'.$media->id.'/unlock')->assertOk();
        $this->assertTrue(Grantor::canSeeMedia($media));

        $this->get('/n/vera/carnet')
            ->assertOk()
            ->assertSee('Épreuve consignation', false)
            ->assertSee('Vidéo ouverte', false);
    }

    public function test_drop_puts_relic_in_carnet(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->postGrant('/n/lumen/v/'.$media->id.'/drop', ['at' => 4])->assertForbidden();
        $pid = (string) \App\Models\Product::query()->where('node_id', 'lumen')->value('id');
        Grantor::grantProduct($pid, 'lumen', 'Cristal');
        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertOk();
        $this->postGrant('/n/lumen/v/'.$media->id.'/drop', ['at' => 4])
            ->assertOk()
            ->assertJsonPath('label', 'Éclat de cristal');
        $this->get('/n/lumen/carnet')
            ->assertOk()
            ->assertSee('Éclat de cristal', false)
            ->assertDontSee('CCK');
    }

    public function test_drop_without_door_is_forbidden(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $pid = (string) \App\Models\Product::query()->where('node_id', 'lumen')->value('id');
        Grantor::grantProduct($pid, 'lumen', 'Cristal');
        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertOk();
        $this->postGrant('/n/lumen/v/'.$media->id.'/drop', ['at' => 999])->assertForbidden();
        $this->assertFalse(Grantor::has('relic', 'drop-'.$media->id.'-999'));
    }

    public function test_omni_is_a_claim_not_a_verified_proof(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->postGrant('/n/lumen/v/'.$media->id.'/omni', ['kind' => 'labo', 'label' => 'Cadre tenu'])->assertForbidden();
        $pid = (string) \App\Models\Product::query()->where('node_id', 'lumen')->value('id');
        Grantor::grantProduct($pid, 'lumen', 'Cristal');
        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertOk();
        $this->postGrant('/n/lumen/v/'.$media->id.'/omni', ['kind' => 'labo', 'label' => 'Cadre tenu'])
            ->assertOk()
            ->assertJsonPath('status', 'claim');
        $this->assertTrue(Grantor::has('claim', 'omni-'.$media->id.'-labo'));
        $this->assertFalse(Grantor::has('proof', 'omni-'.$media->id.'-labo'));
    }

    public function test_embed_is_a_place_not_a_player(): void
    {
        $this->get('/embed/lumen')
            ->assertOk()
            ->assertSee('Lumen', false)
            ->assertSee('Voir la vitrine', false)
            ->assertDontSee('CCK')
            ->assertDontSee('parent_of');
        $this->get('/embed/vera')
            ->assertOk()
            ->assertSee('Voir les offres', false);
    }

    public function test_media_api_hides_engine_words(): void
    {
        $res = $this->getJson('/v1/nodes/lumen/media');
        $res->assertOk();
        $json = json_encode($res->json());
        $this->assertStringNotContainsString('CCK', $json);
        $this->assertStringNotContainsString('parent_of', $json);
        $this->assertStringContainsString('Making-of', $json);
        $this->assertTrue($res->json('medias.0.teaser'));
    }

    public function test_chapter_opens_a_place(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->get('/n/lumen/v/'.$media->id)
            ->assertOk()
            ->assertSee('Cristal Lumen', false)
            ->assertSee('Ouvrir', false);
    }

    public function test_layer_click_writes_a_visit_proof(): void
    {
        $this->postGrant('/n/lumen/visite', ['target' => 'cristal-lumen-01', 'label' => 'Cristal'])
            ->assertOk()
            ->assertJsonPath('preuve.quoi', 'A visité');
        $this->get('/n/lumen/carnet')
            ->assertOk()
            ->assertSee('A visité', false)
            ->assertSee('Cristal', false);
    }

    public function test_locked_file_signed_url_needs_grant(): void
    {
        $file = DriveFile::query()->where('title', 'Certificat RWA.pdf')->first();
        $this->assertNotNull($file);
        $this->assertTrue((bool) $file->locked);
        $url = \App\Support\Grantor::fileHref($file);
        $this->get($url)->assertForbidden();
        $this->postGrant('/n/lumen/v/'.Media::query()->where('node_id', 'lumen')->value('id').'/unlock');
        // making-of unlock does not open purchase files
        $this->get($url)->assertForbidden();
    }

    public function test_locked_drive_path_is_not_guessable_in_ui(): void
    {
        $this->get('/drive?slug=lumen')
            ->assertOk()
            ->assertSee('Certificat RWA', false)
            ->assertDontSee('private/media/certificat-rwa', false);
    }
}
