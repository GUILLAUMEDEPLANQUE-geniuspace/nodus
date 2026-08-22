<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

class SeoRadar
{
    public static function run(GpNode $club): array
    {
        $issues = [];
        $seo = DB::table('node_seo')->where('node_id', $club->id)->first();
        if (! $seo || strlen($seo->description ?? '') < 40) {
            $issues[] = ['niveau' => 'haut', 'msg' => 'Le club n’a pas de description SEO (≥ 40 car.).', 'href' => '/n/'.$club->slug.'/studio'];
        }
        $tabs = DB::table('node_tabs')->where('node_id', $club->id)->get();
        foreach ($tabs as $t) {
            if ($t->key === 'vivre') {
                continue;
            }
            if (strlen($t->seo_title ?? '') < 8) {
                $issues[] = ['niveau' => 'moyen', 'msg' => 'Salle « '.$t->label.' » sans titre SEO.', 'href' => '/atelier/'.$club->slug];
            }
        }
        $childIds = Edge::query()->where('from_id', $club->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $incoming = Edge::query()->whereIn('to_id', $childIds)->where('from_id', '!=', $club->id)->pluck('to_id')->unique();
        foreach ($children as $c) {
            if (! $incoming->contains($c->id) && $children->count() > 1) {
                $issues[] = ['niveau' => 'moyen', 'msg' => 'Fiche orpheline : '.$c->title.' (pas de lien depuis une autre fiche).', 'href' => '/n/'.$c->slug];
            }
            $cs = DB::table('node_seo')->where('node_id', $c->id)->first();
            if (! $cs || strlen($cs->description ?? '') < 20) {
                $issues[] = ['niveau' => 'haut', 'msg' => 'Fiche « '.$c->title.' » invisible (description trop courte).', 'href' => '/n/'.$c->slug];
            }
        }
        $bodies = DB::table('threads')->where('node_id', $club->id)->pluck('body')->implode(' ');
        $words = array_count_values(array_filter(preg_split('/\W+/u', mb_strtolower($bodies)), fn ($w) => mb_strlen($w) > 4));
        arsort($words);
        $titles = $children->map(fn ($c) => mb_strtolower($c->title))->all();
        $i = 0;
        foreach ($words as $w => $n) {
            if ($i++ > 8) {
                break;
            }
            $hit = false;
            foreach ($titles as $t) {
                if (str_contains($t, $w) || str_contains($w, $t)) {
                    $hit = true;
                    break;
                }
            }
            if (! $hit && $n >= 2) {
                $issues[] = ['niveau' => 'haut', 'msg' => 'Le forum parle souvent de « '.$w.' » ('.$n.'×) — aucune fiche. Crée-la.', 'href' => '/atelier/'.$club->slug];
            }
        }
        $noGeo = DB::table('products')->where('node_id', $club->id)->where(function ($q) {
            $q->whereNull('city')->orWhere('city', '');
        })->count();
        if ($noGeo) {
            $issues[] = ['niveau' => 'moyen', 'msg' => $noGeo.' annonce(s) sans ville — pas de SEO local.', 'href' => '/n/'.$club->slug.'/boutique'];
        }
        if (! $issues) {
            $issues[] = ['niveau' => 'ok', 'msg' => 'Radar calme. Enrichis encore les fiches.', 'href' => '/n/'.$club->slug];
        }
        return $issues;
    }
}
