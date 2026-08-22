<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

class Searcher
{
    public static function inClub(GpNode $club, string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $needle = mb_strtolower($q);
        $out = [];
        $ids = Edge::query()->where('from_id', $club->id)->pluck('to_id')->push($club->id);
        foreach (GpNode::query()->whereIn('id', $ids)->get() as $n) {
            $s = self::score($needle, $n->title, $n->summary.' '.$n->body);
            if ($s) {
                $out[] = ['score' => $s, 'kind' => 'fiche', 'title' => $n->title, 'href' => '/n/'.$club->slug.'/f/'.$n->slug, 'blurb' => $n->summary];
            }
        }
        foreach (DB::table('threads')->where('node_id', $club->id)->get() as $t) {
            $s = self::score($needle, $t->title, $t->body);
            if ($s) {
                $out[] = ['score' => $s, 'kind' => 'sujet', 'title' => $t->title, 'href' => '/n/'.$club->slug.'/t/'.$t->id, 'blurb' => $t->body];
            }
        }
        foreach (DB::table('products')->where('node_id', $club->id)->get() as $p) {
            $s = self::score($needle, $p->title, $p->summary ?? '');
            if ($s) {
                $out[] = ['score' => $s, 'kind' => 'pièce', 'title' => $p->title, 'href' => '/n/'.$club->slug.'/p/'.$p->id, 'blurb' => $p->price];
            }
        }
        foreach (DB::table('media')->where('node_id', $club->id)->get() as $m) {
            $s = self::score($needle, $m->title, $m->transcript ?? '');
            if ($s) {
                $out[] = ['score' => $s, 'kind' => 'vidéo', 'title' => $m->title, 'href' => '/n/'.$club->slug.'/v/'.$m->id, 'blurb' => $m->transcript];
            }
        }
        foreach (DB::table('wiki_pages')->where('node_id', $club->id)->get() as $w) {
            $s = self::score($needle, $w->title, $w->body);
            if ($s) {
                $out[] = ['score' => $s, 'kind' => 'guide', 'title' => $w->title, 'href' => '/n/'.$club->slug.'/guide/'.\Illuminate\Support\Str::slug($w->title), 'blurb' => $w->body];
            }
        }
        usort($out, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($out, 0, 24);
    }

    private static function score(string $q, string $title, string $body): int
    {
        $t = mb_strtolower($title);
        $b = mb_strtolower($body);
        $s = 0;
        if ($t === $q) {
            $s += 50;
        } elseif (str_starts_with($t, $q)) {
            $s += 20;
        } elseif (str_contains($t, $q)) {
            $s += 10;
        }
        if (str_contains($b, $q)) {
            $s += 3;
        }
        return $s;
    }
}
