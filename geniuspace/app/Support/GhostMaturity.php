<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Capacités mesurables. Pas un QI. L’autonomie reste plafonnée :
 * Ghost ne passe jamais PREPARE → ACT tout seul.
 */
class GhostMaturity
{
    /**
     * @return array<string, int>
     */
    public static function of(?GpNode $node = null): array
    {
        $facts = 0;
        $episodes = 0;
        $fails = 0;
        $skills = count(GhostSkills::all());
        $approved = 0;
        $runs = 0;
        $wins = 0;

        if (Schema::hasTable('ghost_facts')) {
            $q = DB::table('ghost_facts')->where('status', '!=', 'revoked');
            if ($node) {
                $q->where('node_id', $node->id);
            }
            $facts = $q->count();
        }
        if (Schema::hasTable('ghost_experiences')) {
            $episodes = DB::table('ghost_experiences')->count();
        }
        if (Schema::hasTable('ghost_failures')) {
            $fails = DB::table('ghost_failures')->count();
        }
        if (Schema::hasTable('ghost_skill_stats')) {
            $row = DB::table('ghost_skill_stats')->selectRaw('SUM(runs) r, SUM(wins) w')->first();
            $runs = (int) ($row->r ?? 0);
            $wins = (int) ($row->w ?? 0);
            $skills = max($skills, (int) DB::table('ghost_skill_stats')->count());
        }
        if (Schema::hasTable('ghost_skill_candidates')) {
            $approved = DB::table('ghost_skill_candidates')->where('status', 'approved')->count();
            $skills += $approved;
        }

        $n = max(1, $runs);
        $reliability = (int) round(100 * $wins / $n);
        $grounding = (int) max(0, min(100, 100 - (int) round(100 * $fails / max(1, $episodes ?: $n))));

        return [
            'knowledge' => min(100, 20 + $facts * 4),
            'reasoning' => $reliability,
            'planning' => $reliability,
            'tools' => $runs ? min(100, 60 + (int) round(100 * $wins / $n) / 5) : 0,
            'grounding' => $grounding,
            'verification' => $grounding,
            'autonomy' => Invariants::AUTONOMY_CAP,
            'reliability' => $reliability,
            'skills' => $skills,
            'facts' => $facts,
            'episodes' => $episodes,
            'fails' => $fails,
            'strategies' => $approved,
        ];
    }
}
