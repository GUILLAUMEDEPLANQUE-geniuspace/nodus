<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use App\Models\Product;
use Illuminate\Support\Collection;

/** Maillage + citation @nœud (fiche ou pièce). */
class Linker
{
    public static function html(GpNode $club, ?string $text): string
    {
        $text = e($text ?? '');
        foreach (self::fiches($club) as $n) {
            if (mb_strlen($n->title) < 3) {
                continue;
            }
            $href = '/n/'.$club->slug.'/f/'.$n->slug;
            $text = preg_replace(
                '/(?!<a[^>]*>)('.preg_quote($n->title, '/').')(?![^<]*<\/a>)/iu',
                '<a class="primary" href="'.$href.'">$1</a>',
                $text,
                1
            ) ?? $text;
        }
        $text = preg_replace_callback('/@([a-z0-9][a-z0-9\-]+)/i', function ($m) use ($club) {
            return self::card($club, $m[1]);
        }, $text) ?? $text;
        return $text;
    }

    public static function card(GpNode $club, string $key): string
    {
        $n = GpNode::query()->where('slug', $key)->first();
        if ($n) {
            $href = '/n/'.$club->slug.'/f/'.$n->slug;
            return '<a class="cite-card" href="'.$href.'"><strong>'.$n->title.'</strong><span>fiche</span></a>';
        }
        $p = Product::query()->where('node_id', $club->id)->where(function ($q) use ($key) {
            $q->where('id', $key)->orWhere('title', 'like', '%'.$key.'%');
        })->first();
        if ($p) {
            $uid = auth()->id() ?: 0;
            $href = '/n/'.$club->slug.'/p/'.$p->id.'?koc='.$uid;
            $csrf = csrf_token();
            return '<span class="cite-card cite-shop">'
                .'<a href="'.$href.'"><strong>'.e($p->title).'</strong></a>'
                .'<span>'.$p->price.' · 5 % pour toi si achat</span>'
                .'<form method="post" action="/cart" style="margin:.3rem 0 0">'
                .'<input type="hidden" name="_token" value="'.$csrf.'">'
                .'<input type="hidden" name="product_id" value="'.$p->id.'">'
                .'<input type="hidden" name="koc" value="'.$uid.'">'
                .'<button class="btn" type="submit">Acheter ici</button>'
                .'</form></span>';
        }
        return '@'.$key;
    }

    /** @return Collection<int,GpNode> */
    public static function fiches(GpNode $club): Collection
    {
        $ids = Edge::query()->where('from_id', $club->id)->pluck('to_id');
        return GpNode::query()->whereIn('id', $ids)->orderByRaw('length(title) desc')->get();
    }
}
