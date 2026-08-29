<?php

namespace Tests\Unit;

use Tests\TestCase;

class KernelHostTest extends TestCase
{
    public function test_host_does_not_write_the_world(): void
    {
        $src = file_get_contents(app_path('Support/GhostHost.php'));
        foreach (['Grantor::give', 'Grantor::unlock', "DB::table('cck_fields')", "DB::table('grants')->insert"] as $ban) {
            $this->assertStringNotContainsString($ban, $src, $ban);
        }
        $engine = file_get_contents(app_path('Support/Engine.php'));
        $this->assertStringNotContainsString('GhostHost', $engine);
    }

    public function test_core_still_exists_and_host_is_a_surface(): void
    {
        $core = file_get_contents(app_path('Support/GhostCore.php'));
        $this->assertStringContainsString('Ghost::groundedReply', $core);
        $this->assertStringContainsString('LOOP', $core);
        $host = file_get_contents(app_path('Support/GhostHost.php'));
        $this->assertStringContainsString('publicSurface', $host);
        $this->assertStringNotContainsString('GhostDream', $host);
        foreach (['growth', 'belief', 'situation', 'critic', 'simulations'] as $ban) {
            $this->assertStringNotContainsString("'{$ban}'", $host, $ban);
        }
        $this->assertStringContainsString('ghost|studio|builder|monde|radar', $host);
        $lite = file_get_contents(app_path('Support/GhostLite.php'));
        $this->assertStringContainsString('GhostTribunal', $lite);
        $this->assertStringContainsString("'mode' => 'lite'", $lite);
    }

    public function test_public_chat_uses_the_surface(): void
    {
        $ctrl = file_get_contents(app_path('Http/Controllers/GhostLiteController.php'));
        $this->assertStringContainsString('GhostLite::reply', $ctrl);
        $this->assertStringContainsString('GhostHost::publicSurface', $ctrl);
        $this->assertGreaterThanOrEqual(2, substr_count($ctrl, 'GhostLite::reply'));
        $boot = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('routes/public.php', $boot);
        $pub = file_get_contents(base_path('routes/public.php'));
        $this->assertStringContainsString('GhostLiteController', $pub);
        $this->assertStringContainsString('SeoCompiler::sitemapXml', $pub);
        $this->assertStringContainsString('SeoCompiler::llmsTxt', $pub);
    }
}
