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

        return self::defaultCursor($clubId);
    }

    public static function defaultCursor(string $clubId): int
    {
        $node = GpNode::query()->find($clubId);
        if ($node && Flagships::canvas($node) === 'atelier') {
            return 1;
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

    /** @return array{cursor: int, label: string, arcs: list<array{ord:int,label:string}>} */
    public static function curtain(GpNode $node): array
    {
        $arcs = DB::table('node_arcs')->where('node_id', $node->id)->orderBy('ord')->get();
        $cursor = self::cursor($node->id);
        $label = 'Tout vu';
        foreach ($arcs as $a) {
            if ((int) $a->ord <= $cursor) {
                $label = $a->label;
            }
        }

        return [
            'cursor' => $cursor,
            'label' => $label,
            'arcs' => $arcs->map(fn ($a) => ['ord' => (int) $a->ord, 'label' => $a->label])->values()->all(),
        ];
    }

    /** True si le texte appartient à un arc pas encore ouvert. */
    public static function veiled(?string $text, GpNode $node): bool
    {
        if (! $text) {
            return false;
        }
        $cursor = self::cursor($node->id);
        foreach (DB::table('node_arcs')->where('node_id', $node->id)->where('ord', '>', $cursor)->get() as $a) {
            if ($a->label !== '' && mb_stripos($text, $a->label) !== false) {
                return true;
            }
        }

        return false;
    }

    /** Titres cachés derrière le rideau (fiches enfants). */
    public static function hiddenTitles(GpNode $node): array
    {
        $ids = DB::table('edges')->where('from_id', $node->id)->pluck('to_id');
        if ($ids->isEmpty()) {
            return [];
        }

        return GpNode::query()->whereIn('id', $ids)->get()
            ->filter(fn (GpNode $n) => ! self::ok((int) ($n->appear_order ?? 0), $node->id))
            ->pluck('title')->all();
    }
}
