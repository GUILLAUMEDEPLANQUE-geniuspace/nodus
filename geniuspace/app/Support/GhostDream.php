<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidation hors interaction. Prune → oubli → liaisons distantes.
 * Produit des candidats. N’applique rien. Jamais d’autorité.
 */
class GhostDream
{
    /**
     * @return array{pruned:int, dreams:list<array>, hypotheses:list<array>, applied:false, indexed:int}
     */
    public static function cycle(GpNode $node): array
    {
        $indexed = GhostCortex::ingestWorld($node);
        $pruned = GhostDecay::prune((string) $node->id);
        $pairs = GhostSynapse::distantPairs((string) $node->id, 3);
        $dreams = [];
        $hyps = [];
        foreach ($pairs as $p) {
            $a = self::label($p['a']);
            $b = self::label($p['b']);
            $code = 'DRM-'.strtoupper(substr(sha1($p['a'].'|'.$p['b']), 0, 4));
            $insight = 'Deux souvenirs jamais reliés : '.$a.' ↔ '.$b.'.';
            $row = [
                'code' => $code,
                'from_id' => $p['a'],
                'to_id' => $p['b'],
                'insight' => $insight,
                'status' => 'candidate',
                'applied' => false,
                'counter' => 'La co-occurrence est un artefact de vocabulaire.',
            ];
            $dreams[] = $row;
            $hyps[] = [
                'code' => $code,
                'observation' => $insight,
                'hypothesis' => 'Relier '.$a.' et '.$b.' change la lecture du lieu.',
                'prediction' => null,
                'counter_hypothesis' => $row['counter'],
                'status' => GhostHypothesis::UNKNOWN,
            ];
            GhostSynapse::reinforce((string) $node->id, $p['a'], $p['b'], 'SERENDIPITY', 0.08);
            if (Schema::hasTable('ghost_dreams')) {
                DB::table('ghost_dreams')->updateOrInsert(
                    [
                        'node_id' => $node->id,
                        'from_id' => $p['a'],
                        'to_id' => $p['b'],
                    ],
                    [
                        'code' => $code,
                        'insight' => $insight,
                        'status' => 'candidate',
                        'applied' => false,
                        'created_at' => now(),
                    ]
                );
            }
        }

        return [
            'pruned' => $pruned,
            'dreams' => $dreams,
            'hypotheses' => $hyps,
            'applied' => false,
            'indexed' => $indexed,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function journal(GpNode $node, int $limit = 12): array
    {
        if (! Schema::hasTable('ghost_dreams')) {
            return [];
        }

        return DB::table('ghost_dreams')
            ->where('node_id', $node->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => [
                'code' => $r->code,
                'insight' => $r->insight,
                'from' => $r->from_id,
                'to' => $r->to_id,
                'status' => $r->status,
                'applied' => (bool) $r->applied,
            ])
            ->all();
    }

    private static function label(string $id): string
    {
        return GhostSynapse::label($id);
    }
}
