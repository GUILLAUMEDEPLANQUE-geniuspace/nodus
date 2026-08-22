<?php

namespace App\Support;

use App\Models\Article;
use App\Models\GpNode;
use App\Models\WikiPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fiches du lieu pour le Ghost : wiki + magazine + table optionnelle geniuspedia_cards.
 * Jamais un crawl web. Jamais Wikipedia. Uniquement ce qui appartient au Node.
 */
class Geniuspedia
{
    /** @return list<array{titre: string, extrait: string, url: string, source: string}> */
    public static function cards(GpNode $node, int $limit = 8): array
    {
        $out = [];
        if (Schema::hasTable('geniuspedia_cards')) {
            foreach (DB::table('geniuspedia_cards')->where('node_id', $node->id)->orderByDesc('id')->limit($limit)->get() as $c) {
                $out[] = [
                    'titre' => (string) $c->title,
                    'extrait' => Str::limit((string) ($c->summary ?: $c->body), 220),
                    'url' => self::localUrl((string) ($c->url ?: ''), $node),
                    'source' => 'pack',
                ];
            }
        }
        if (class_exists(WikiPage::class)) {
            foreach (WikiPage::query()->where('node_id', $node->id)->limit($limit)->get() as $w) {
                $out[] = [
                    'titre' => $w->title,
                    'extrait' => Str::limit((string) $w->body, 220),
                    'url' => '/n/'.$node->slug.'/guide/'.$w->id,
                    'source' => 'guide',
                ];
            }
        }
        if (class_exists(Article::class)) {
            foreach (Article::query()->where('node_id', $node->id)->limit($limit)->get() as $a) {
                $out[] = [
                    'titre' => $a->title,
                    'extrait' => Str::limit((string) ($a->resume ?: $a->body), 220),
                    'url' => '/n/'.$node->slug.'/blog',
                    'source' => 'magazine',
                ];
            }
        }

        $seen = [];
        $uniq = [];
        foreach ($out as $card) {
            if (Spoiler::veiled($card['titre'].' '.$card['extrait'], $node)) {
                continue;
            }
            $k = mb_strtolower($card['titre']);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $uniq[] = $card;
            if (count($uniq) >= $limit) {
                break;
            }
        }

        return $uniq;
    }

    /** Refuse une URL externe : le coffre ne sort pas du lieu. */
    private static function localUrl(string $url, GpNode $node): string
    {
        $fallback = '/n/'.$node->slug;
        if ($url === '') {
            return $fallback;
        }
        if (str_starts_with($url, '/')) {
            return $url;
        }

        return $fallback;
    }
}
