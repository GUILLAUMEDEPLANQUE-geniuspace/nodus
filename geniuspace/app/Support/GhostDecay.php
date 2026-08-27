<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Oubli. w_eff = w × 2^(−Δt / demi-vie).
 * Les liens de rêve s’effacent vite s’ils ne sont pas reforcés.
 */
class GhostDecay
{
    public const HALF_LIFE = [
        'SERENDIPITY' => 14,
        'TAG' => 30,
        'CO_OCCURRENCE' => 60,
        'MEMORY' => 90,
        'SUPPORT' => 180,
        'CONTRADICTION' => 180,
        'DREAM_CONNECTION' => 14,
    ];

    public const MIN_WEIGHT = 0.03;

    public static function effective(float $weight, string $relation, mixed $last): float
    {
        $hl = self::HALF_LIFE[$relation] ?? 90;
        $ts = is_numeric($last) ? (int) $last : (is_string($last) ? strtotime($last) : time());
        $dtDays = max(0.0, (time() - $ts) / 86400);

        return $weight * (2 ** (-$dtDays / max(1, $hl)));
    }

    public static function prune(string $nodeId, int $max = 400): int
    {
        if (! Schema::hasTable('ghost_synapses')) {
            return 0;
        }
        $deleted = 0;
        $rows = DB::table('ghost_synapses')->where('node_id', $nodeId)->get();
        $dead = [];
        foreach ($rows as $r) {
            $eff = self::effective((float) $r->weight, (string) $r->relation, $r->last_reinforced_at);
            if ($eff < self::MIN_WEIGHT) {
                $dead[] = ['id' => $r->id, 'eff' => $eff];
            }
        }
        usort($dead, fn ($a, $b) => $a['eff'] <=> $b['eff']);
        foreach (array_slice($dead, 0, $max) as $d) {
            DB::table('ghost_synapses')->where('id', $d['id'])->delete();
            $deleted++;
        }

        return $deleted;
    }
}
