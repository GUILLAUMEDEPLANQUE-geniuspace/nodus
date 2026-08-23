<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * owner > admin > mod.
 * Un staff s’écrit à la création du lieu (CreateController) ou par un owner.
 * Jamais d’auto-promotion : un compte connecté n’est pas owner d’un lieu vide.
 */
class Acl
{
    public static function role(?string $nodeId): ?string
    {
        $user = Auth::user();
        if (! $user || ! $nodeId) {
            return null;
        }
        $row = DB::table('node_staff')->where('node_id', $nodeId)->where('user_id', $user->id)->first();

        return $row->role ?? null;
    }

    public static function atLeast(?string $nodeId, string $min): bool
    {
        if (! $nodeId) {
            return false;
        }
        $rank = ['mod' => 1, 'admin' => 2, 'owner' => 3];
        $have = self::role($nodeId);
        if (! $have) {
            return false;
        }

        return ($rank[$have] ?? 0) >= ($rank[$min] ?? 99);
    }

    public static function canWrite(?string $nodeId, string $min = 'mod'): bool
    {
        return self::atLeast($nodeId, $min);
    }

    public static function mustUser(): void
    {
        abort_unless(Auth::check(), 403, 'Connectez-vous pour écrire.');
    }

    public static function guard(?string $nodeId, string $min = 'mod'): void
    {
        abort_unless($nodeId, 404);
        abort_unless(self::canWrite($nodeId, $min), 403, 'Connectez-vous (Créateur) pour sculpter.');
    }

    public static function nodeOfSlug(string $slug): GpNode
    {
        return GpNode::query()->where('slug', $slug)->firstOrFail();
    }
}
