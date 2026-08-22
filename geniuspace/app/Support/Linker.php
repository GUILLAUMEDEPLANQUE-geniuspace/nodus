<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Collection;

/** Maillage : les titres des fiches du club deviennent des liens. Plus long d’abord. */
class Linker
{
    public static function html(GpNode $club, ?string $text): string
    {
        $text = e($text ?? '');
        $fiches = self::fiches($club);
        foreach ($fiches as $n) {
            if (mb_strlen($n->title) < 3) {
                continue;
            }
            $href = '/n/'.$n->slug;
            $text = preg_replace(
                '/(?!<a[^>]*>)('.preg_quote($n->title, '/').')(?![^<]*<\/a>)/iu',
                '<a class="primary" href="'.$href.'">$1</a>',
                $text,
                1
            ) ?? $text;
        }
        return $text;
    }

    /** @return Collection<int,GpNode> */
    public static function fiches(GpNode $club): Collection
    {
        $ids = Edge::query()->where('from_id', $club->id)->pluck('to_id');
        return GpNode::query()->whereIn('id', $ids)->orderByRaw('length(title) desc')->get();
    }
}
