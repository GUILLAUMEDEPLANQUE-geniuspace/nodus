<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Curseur temporel : masque fiches / produits / sujets au-delà de l’arc du fan. */
class Spoiler
{
    public static function cursor(string $clubId): int
    {
        if (Auth::id()) {
            $row = DB::table('user_cursors')->where('user_id', Auth::id())->where('node_id', $clubId)->first();
            if ($row) {
                return (int) $row->cursor;
            }
        }
        $key = 'gp_cursor_'.$clubId;
        if (session()->has($key)) {
            return (int) session($key);
        }
        return 99;
    }

    public static function ok(int $appear, string $clubId): bool
    {
        if ($appear <= 0) {
            return true;
        }
        return $appear <= self::cursor($clubId);
    }

    public static function filterNodes($nodes, string $clubId)
    {
        return $nodes->filter(fn (GpNode $n) => self::ok((int) ($n->appear_order ?? 0), $clubId))->values();
    }
}
