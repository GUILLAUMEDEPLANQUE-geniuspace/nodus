<?php

namespace Tests\Unit;

use App\Support\Invariants;
use Tests\TestCase;

/**
 * Sens des flèches. Engine ne connaît pas Ghost.
 * Une nouvelle `use` dans le mauvais fichier casse ici, pas en prod.
 */
class KernelBoundaryTest extends TestCase
{
    public function test_engine_does_not_call_actors(): void
    {
        $src = file_get_contents(app_path('Support/Engine.php'));
        foreach (['Grantor', 'Ghost::', 'Chrome::', 'GhostAction::', 'GhostEdit::', 'GhostBiz::'] as $ban) {
            $this->assertStringNotContainsString($ban, $src, $ban);
        }
    }

    public function test_chrome_does_not_call_ghost_or_grantor(): void
    {
        $src = file_get_contents(app_path('Support/Chrome.php'));
        foreach (['Ghost::', 'Grantor::', 'GhostAction::'] as $ban) {
            $this->assertStringNotContainsString($ban, $src, $ban);
        }
        $this->assertStringContainsString('Engine::', $src);
    }

    public function test_grantor_may_read_engine_not_ghost(): void
    {
        $src = file_get_contents(app_path('Support/Grantor.php'));
        $this->assertStringContainsString('Engine::', $src);
        foreach (['Ghost::', 'GhostAction::', 'Chrome::'] as $ban) {
            $this->assertStringNotContainsString($ban, $src, $ban);
        }
    }

    public function test_ghost_does_not_write_grants_or_fiche(): void
    {
        $src = file_get_contents(app_path('Support/Ghost.php'));
        foreach (['Grantor::give', 'Grantor::unlock', 'StudioController::cck', "DB::table('cck_fields')->insert"] as $ban) {
            $this->assertStringNotContainsString($ban, $src, $ban);
        }
    }

    public function test_acl_never_auto_inserts_owner(): void
    {
        $src = file_get_contents(app_path('Support/Acl.php'));
        $this->assertDoesNotMatchRegularExpression('/node_staff[^\n]*insert/s', $src);
        $this->assertStringNotContainsString("role' => 'owner'", $src);
    }

    public function test_invariants_file_exists_with_fifteen_keys(): void
    {
        $this->assertCount(15, array_keys(Invariants::rules()));
        foreach (array_keys(Invariants::rules()) as $id) {
            $this->assertMatchesRegularExpression('/^I-[A-Z-]+$/', $id);
        }
    }
}
