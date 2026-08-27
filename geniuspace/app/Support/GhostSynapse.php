<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Graphe associatif. Hebbian : w' = w + α(1 − w).
 * Ghost relie. Il n’écrit pas le monde.
 */
class GhostSynapse
{
    /**
     * @return array{source:string, target:string, relation:string, weight:float}
     */
    public static function reinforce(string $nodeId, string $from, string $to, string $relation = 'CO_OCCURRENCE', float $alpha = 0.15): array
    {
        $from = self::id($from);
        $to = self::id($to);
        if ($from === '' || $to === '' || $from === $to) {
            return ['source' => $from, 'target' => $to, 'relation' => $relation, 'weight' => 0];
        }
        [$a, $b] = [$from, $to];
        if (strcmp($a, $b) > 0) {
            [$a, $b] = [$b, $a];
        }
        $alpha = max(0.0, min(1.0, $alpha));
        $weight = $alpha;
        if (! Schema::hasTable('ghost_synapses')) {
            return ['source' => $a, 'target' => $b, 'relation' => $relation, 'weight' => $weight];
        }
        $row = DB::table('ghost_synapses')
            ->where('node_id', $nodeId)
            ->where('source', $a)
            ->where('target', $b)
            ->where('relation', $relation)
            ->first();
        if ($row) {
            $weight = max(0.0, min(1.0, (float) $row->weight + $alpha * (1 - (float) $row->weight)));
            $weight = round($weight, 4);
            DB::table('ghost_synapses')->where('id', $row->id)->update([
                'weight' => $weight,
                'last_reinforced_at' => now(),
            ]);
        } else {
            $weight = round($alpha, 4);
            DB::table('ghost_synapses')->insert([
                'node_id' => $nodeId,
                'source' => $a,
                'target' => $b,
                'relation' => $relation,
                'weight' => $weight,
                'last_reinforced_at' => now(),
            ]);
        }

        return ['source' => $a, 'target' => $b, 'relation' => $relation, 'weight' => $weight];
    }

    public static function hasEdge(string $nodeId, string $from, string $to, ?string $relation = null): bool
    {
        if (! Schema::hasTable('ghost_synapses')) {
            return false;
        }
        $from = self::id($from);
        $to = self::id($to);
        [$a, $b] = strcmp($from, $to) > 0 ? [$to, $from] : [$from, $to];
        $q = DB::table('ghost_synapses')->where('node_id', $nodeId)->where('source', $a)->where('target', $b);
        if ($relation) {
            $q->where('relation', $relation);
        }

        return $q->exists();
    }

    /**
     * @return list<array{id:string, weight:float, relation:string}>
     */
    public static function related(string $nodeId, string $id, float $min = 0.2): array
    {
        if (! Schema::hasTable('ghost_synapses')) {
            return [];
        }
        $id = self::id($id);
        $rows = DB::table('ghost_synapses')
            ->where('node_id', $nodeId)
            ->where(function ($q) use ($id) {
                $q->where('source', $id)->orWhere('target', $id);
            })
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $w = GhostDecay::effective((float) $r->weight, (string) $r->relation, $r->last_reinforced_at);
            if ($w < $min) {
                continue;
            }
            $other = $r->source === $id ? $r->target : $r->source;
            $out[] = ['id' => $other, 'weight' => round($w, 4), 'relation' => (string) $r->relation];
        }
        usort($out, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        return $out;
    }

    /**
     * Boost 0..1 d’un document si un terme de la requête est voisin.
     *
     * @param  list<string>  $qTokens
     */
    public static function boost(string $nodeId, array $qTokens, string $docId): float
    {
        if ($qTokens === [] || ! Schema::hasTable('ghost_synapses')) {
            return 0.0;
        }
        $best = 0.0;
        $hay = mb_strtolower($docId);
        foreach (array_slice($qTokens, 0, 6) as $t) {
            foreach (self::related($nodeId, 'tag:'.$t, 0.12) as $n) {
                $nid = mb_strtolower((string) $n['id']);
                if ($n['id'] === $docId || str_contains($hay, $nid) || str_contains($nid, $hay)) {
                    $best = max($best, (float) $n['weight']);
                }
            }
        }

        return max(0.0, min(1.0, $best));
    }

    /**
     * Paires fortes sans arête directe. Matière du rêve.
     *
     * @return list<array{a:string, b:string, wa:float, wb:float}>
     */
    public static function distantPairs(string $nodeId, int $limit = 4): array
    {
        if (! Schema::hasTable('ghost_synapses')) {
            return [];
        }
        $rows = DB::table('ghost_synapses')->where('node_id', $nodeId)->orderByDesc('weight')->limit(80)->get();
        $degree = [];
        foreach ($rows as $r) {
            $wa = GhostDecay::effective((float) $r->weight, (string) $r->relation, $r->last_reinforced_at);
            $degree[$r->source] = ($degree[$r->source] ?? 0) + $wa;
            $degree[$r->target] = ($degree[$r->target] ?? 0) + $wa;
        }
        arsort($degree);
        $ids = array_slice(array_keys($degree), 0, 16);
        $pairs = [];
        for ($i = 0; $i < count($ids); $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                if (self::hasEdge($nodeId, $ids[$i], $ids[$j])) {
                    continue;
                }
                $pairs[] = [
                    'a' => $ids[$i],
                    'b' => $ids[$j],
                    'wa' => (float) $degree[$ids[$i]],
                    'wb' => (float) $degree[$ids[$j]],
                ];
            }
        }
        usort($pairs, fn ($x, $y) => ($y['wa'] * $y['wb']) <=> ($x['wa'] * $x['wb']));

        return array_slice($pairs, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function snapshot(string $nodeId, int $limit = 24): array
    {
        if (! Schema::hasTable('ghost_synapses')) {
            return [];
        }

        return DB::table('ghost_synapses')
            ->where('node_id', $nodeId)
            ->orderByDesc('weight')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'source' => $r->source,
                'target' => $r->target,
                'relation' => $r->relation,
                'weight' => (float) $r->weight,
                'effective' => GhostDecay::effective((float) $r->weight, (string) $r->relation, $r->last_reinforced_at),
            ])
            ->all();
    }

    public static function id(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = preg_replace('/[^a-z0-9:_-]+/u', '_', $s) ?? $s;

        return Str::limit($s, 120, '');
    }
}
