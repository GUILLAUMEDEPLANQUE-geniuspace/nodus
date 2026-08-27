<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Croyance = probabilité + preuves + contexte. Pas un compteur « knowledge = 87 ».
 */
class GhostBelief
{
    /**
     * @return array<string, mixed>
     */
    public static function get(GpNode $node, string $claim): array
    {
        $blank = [
            'claim' => $claim,
            'p' => 0.5,
            'supporting' => 0,
            'contradicting' => 0,
            'unknown' => 0,
            'context' => '',
            'last_observed' => null,
            'decay' => 0.02,
        ];
        if (! Schema::hasTable('ghost_beliefs')) {
            return $blank;
        }
        $row = DB::table('ghost_beliefs')->where('node_id', $node->id)->where('claim', Str::limit($claim, 180))->first();
        if (! $row) {
            return $blank;
        }

        $p = (float) $row->p;
        if ($row->last_observed) {
            $months = max(0, now()->diffInDays($row->last_observed) / 30);
            $p = max(0.05, $p - ((float) $row->decay * $months));
        }

        return [
            'claim' => $row->claim,
            'p' => round($p, 4),
            'supporting' => (int) $row->supporting,
            'contradicting' => (int) $row->contradicting,
            'unknown' => (int) $row->unknown,
            'context' => (string) $row->context,
            'last_observed' => $row->last_observed,
            'decay' => (float) $row->decay,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $belief
     * @return array<string, mixed>
     */
    public static function observe(GpNode $node, string $claim, string $polarity, string $context = '', ?array $belief = null): array
    {
        $b = $belief ?? self::get($node, $claim);
        if ($polarity === 'support') {
            $b['supporting']++;
        } elseif ($polarity === 'contradict') {
            $b['contradicting']++;
        } else {
            $b['unknown']++;
        }
        $n = max(1, $b['supporting'] + $b['contradicting'] + $b['unknown']);
        $b['p'] = round(($b['supporting'] + 1) / ($n + 2), 4);
        $b['context'] = $context;
        $b['last_observed'] = now()->toDateString();
        self::store($node, $b);

        return $b;
    }

    /**
     * @param  array<string, mixed>  $b
     */
    public static function store(GpNode $node, array $b): void
    {
        if (! Schema::hasTable('ghost_beliefs')) {
            return;
        }
        DB::table('ghost_beliefs')->updateOrInsert(
            ['node_id' => $node->id, 'claim' => Str::limit((string) $b['claim'], 180)],
            [
                'p' => $b['p'],
                'supporting' => $b['supporting'],
                'contradicting' => $b['contradicting'],
                'unknown' => $b['unknown'],
                'context' => Str::limit((string) ($b['context'] ?? ''), 180),
                'decay' => $b['decay'] ?? 0.02,
                'last_observed' => $b['last_observed'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
