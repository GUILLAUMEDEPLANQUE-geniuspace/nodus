<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** owner > admin > mod. Preview : le compte seedé est owner de tous les Nodes. */
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

    public static function atLeast(string $nodeId, string $min): bool
    {
        $rank = ['mod' => 1, 'admin' => 2, 'owner' => 3];
        $have = self::role($nodeId);
        if (! $have) {
            return false;
        }
        return ($rank[$have] ?? 0) >= ($rank[$min] ?? 99);
    }

    public static function canWrite(string $nodeId, string $min = 'mod'): bool
    {
        if (! Auth::check()) {
            return false;
        }
        $has = DB::table('node_staff')->where('node_id', $nodeId)->exists();
        if (! $has) {
            DB::table('node_staff')->insert(['node_id' => $nodeId, 'user_id' => Auth::id(), 'role' => 'owner']);
            return true;
        }
        return self::atLeast($nodeId, $min);
    }

    public static function guard(string $nodeId, string $min = 'mod'): void
    {
        abort_unless(self::canWrite($nodeId, $min), 403, 'Connectez-vous (Créateur) pour sculpter.');
    }

    public static function nodeOfSlug(string $slug): GpNode
    {
        return GpNode::query()->where('slug', $slug)->firstOrFail();
    }
}
