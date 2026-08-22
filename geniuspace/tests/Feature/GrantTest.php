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

    public function test_unlock_is_a_server_grant_not_js(): void
    {
        $media = Media::query()->where('node_id', 'vera')->first();
        $this->assertNotNull($media);
        $this->assertFalse(Grantor::canSeeMedia($media));

        $res = $this->postGrant('/n/vera/v/'.$media->id.'/unlock');
        $res->assertOk()->assertJsonPath('granted', true);
        $this->assertTrue(Grantor::canSeeMedia($media));
        $this->assertStringContainsString('/play?', $res->json('src'));
        $this->assertStringContainsString('preuve', json_encode($res->json()));
    }

    public function test_full_signed_url_requires_grant(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $full = SignedMedia::sign($media->path, 900, false, 'media:'.$media->id);
        $this->get($full)->assertForbidden();

        $preview = SignedMedia::sign($media->path, 900, true, 'media:'.$media->id);
        $this->get($preview)->assertOk();

        $this->postGrant('/n/lumen/v/'.$media->id.'/unlock')->assertOk();
        $this->get($full)->assertOk();
    }

    public function test_quest_unlock_opens_brief_and_carnet(): void
    {
        $media = Media::query()->where('node_id', 'vera')->first();
        $brief = DriveFile::query()->where('title', 'Brief consignation Relève')->first();
        $this->assertNotNull($brief);
        $this->assertFalse(Grantor::canSeeFile($brief));

        $this->postGrant('/n/vera/v/'.$media->id.'/unlock')->assertOk();
        $this->assertTrue(Grantor::canSeeFile($brief));

        $this->get('/n/vera/carnet')
            ->assertOk()
            ->assertSee('Épreuve consignation', false)
            ->assertSee('Vidéo ouverte', false);
    }

    public function test_drop_puts_relic_in_carnet(): void
    {
        $media = Media::query()->where('node_id', 'lumen')->first();
        $this->postGrant('/n/lumen/v/'.$media->id.'/drop', ['at' => 4])
            ->assertOk()
            ->assertJsonPath('label', 'Éclat de cristal');
        $this->get('/n/lumen/carnet')
            ->assertOk()
            ->assertSee('Éclat de cristal', false)
            ->assertDontSee('CCK');
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

    public function test_locked_drive_path_is_not_guessable_in_ui(): void
    {
        $this->get('/drive?slug=lumen')
            ->assertOk()
            ->assertSee('Certificat RWA', false)
            ->assertDontSee('private/media/certificat-rwa', false);
    }
}
