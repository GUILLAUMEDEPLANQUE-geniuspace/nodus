<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Preuve de lore : miser une réputation pour corriger une métadonnée.
 * Pas de blockchain. Si c’est tenu, la fiche bouge. Si c’est troll, la mise tombe.
 */
class Lore
{
    public static function propose(GpNode $node, string $field, string $value, int $stake = 0): array
    {
        $field = trim($field);
        $value = trim($value);
        abort_unless($field !== '' && $value !== '', 422, 'Il faut un champ et une valeur.');
        $flag = Flagships::of($node);
        $need = (int) ($flag['proof']['stake'] ?? 0);
        $stake = $stake > 0 ? $stake : $need;
        $uid = Auth::id();
        if ($uid && $stake > 0) {
            $coins = (int) DB::table('users')->where('id', $uid)->value('nodecoins');
            abort_unless($coins >= $stake, 403, 'Pas assez de pièces pour miser.');
            DB::table('users')->where('id', $uid)->decrement('nodecoins', $stake);
        }
        $id = DB::table('lore_proposals')->insertGetId([
            'node_id' => $node->id,
            'user_id' => $uid,
            'session_id' => Grantor::guestId(),
            'field_name' => $field,
            'value' => $value,
            'stake' => $stake,
            'status' => 'pending',
            'created_at' => now(),
        ]);
        Grantor::give('stake', (string) $id, 'lore', $node->id, $field, ['value' => $value, 'stake' => $stake]);

        return [
            'ok' => true,
            'quoi' => 'Mise posée. Si la maison valide, la fiche bouge.',
            'champ' => $field,
            'valeur' => $value,
            'mise' => $stake,
        ];
    }

    public static function pending(string $nodeId)
    {
        return DB::table('lore_proposals')->where('node_id', $nodeId)->where('status', 'pending')->orderByDesc('id')->get();
    }
}
