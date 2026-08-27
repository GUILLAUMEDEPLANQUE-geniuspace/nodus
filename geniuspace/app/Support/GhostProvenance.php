<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque transition d’état a une provenance lisible par machine.
 *
 * STATE₁ ← produced_by ACTION
 *        ← verified_by VERIFICATION
 *        ← supported_by EVIDENCE
 */
class GhostProvenance
{
    /**
     * @param  array<string, mixed>  $state
     */
    public static function hash(array $state): string
    {
        return sha1((string) json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array<string, mixed>  $action
     */
    public static function record(GpNode $node, array $action, string $status = 'applied'): void
    {
        if (! Schema::hasTable('ghost_transitions')) {
            return;
        }
        $id = GhostAction::id('gt');
        $verification = $action['verification'] ?? ($action['contract']['verification'] ?? null);
        try {
            DB::table('ghost_transitions')->insert([
                'id' => $id,
                'action_id' => $action['id'] ?? $id,
                'node_id' => $node->id,
                'actor_id' => Auth::id(),
                'produced_by' => $action['id'] ?? $id,
                'verified_by' => $verification ? json_encode($verification, JSON_UNESCAPED_UNICODE) : null,
                'evidence' => json_encode($action['contract']['observed_state'] ?? $action['after'] ?? [], JSON_UNESCAPED_UNICODE),
                'before_hash' => $verification['before_hash'] ?? null,
                'after_hash' => $verification['after_hash'] ?? null,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // non bloquant : l’écriture métier a déjà eu lieu
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function of(string $actionId): ?array
    {
        if (! Schema::hasTable('ghost_transitions')) {
            return null;
        }
        $row = DB::table('ghost_transitions')->where('action_id', $actionId)->orderByDesc('created_at')->first();
        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'produced_by' => $row->produced_by,
            'verified_by' => $row->verified_by ? json_decode($row->verified_by, true) : null,
            'evidence' => $row->evidence ? json_decode($row->evidence, true) : null,
            'before_hash' => $row->before_hash,
            'after_hash' => $row->after_hash,
            'status' => $row->status,
        ];
    }
}
