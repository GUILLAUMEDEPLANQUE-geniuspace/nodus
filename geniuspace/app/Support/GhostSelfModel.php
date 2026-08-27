<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Métacognition. Ghost connaît ses plafonds. L’autonomie n’est pas un booléen.
 */
class GhostSelfModel
{
    /**
     * @return array<string, mixed>
     */
    public static function of(?GpNode $node = null): array
    {
        $mat = GhostMaturity::of($node);
        $stats = [];
        if (Schema::hasTable('ghost_skill_stats')) {
            foreach (DB::table('ghost_skill_stats')->get() as $r) {
                $runs = max(1, (int) $r->runs);
                $stats[$r->name] = round(((int) $r->wins) / $runs, 4);
            }
        }

        return [
            'capabilities' => [
                'read_products' => true,
                'modify_page' => true,
                'inspect_orders' => true,
                'run_campaign' => true,
                'send_email' => true,
                'refund' => false,
            ],
            'reliability' => $stats + [
                'send_email' => 0.998,
                'campaign_targeting' => $stats['run_campaign'] ?? 0.81,
                'job_matching' => $stats['match_job'] ?? 0.64,
            ],
            'weaknesses' => [
                'intentions client ambiguës',
                'preuves B2B rares',
                'campagnes inédites',
            ],
            'skills' => $mat['skills'] ?? 0,
            'known_failures' => $mat['fails'] ?? 0,
            'unknown_areas' => self::unknownAreas($stats),
            'matrix' => GhostMaturity::matrix($node),
            'cap' => Invariants::AUTONOMY_CAP,
        ];
    }

    /**
     * @param  array<string, float>  $stats
     */
    private static function unknownAreas(array $stats): int
    {
        $n = 0;
        foreach (array_keys(GhostSkills::all()) as $name) {
            if (! isset($stats[$name])) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Autonomie par action. Volume / argent → humain.
     */
    public static function autonomyFor(string $op, array $extra = []): string
    {
        return GhostAction::autonomyFor($op, $extra);
    }

    /**
     * @param  array<string, mixed>  $self
     * @param  array<string, mixed>  $simPick
     */
    public static function advise(array $self, string $skill, array $simPick): string
    {
        $rel = $self['reliability'][$skill] ?? $self['reliability']['job_matching'] ?? 0.64;
        if ($rel < 0.7) {
            return 'Je peux préparer cette tâche, mais ma fiabilité historique est '.round($rel * 100).' %. Validation humaine.';
        }
        if (($simPick['choose'] ?? '') === 'C') {
            return 'La voie C est peu documentée. Je reste en preview.';
        }

        return 'Préparation possible. Exécution : confirmation si ACT.';
    }
}
