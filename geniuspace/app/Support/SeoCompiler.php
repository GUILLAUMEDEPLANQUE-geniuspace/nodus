<?php

namespace App\Support;

use App\Models\Article;
use App\Models\GpNode;
use App\Models\Product;
use App\Models\Thread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Projette le graphe en URLs / schema / llms.txt.
 * N’écrit pas le monde. GSC observe ailleurs (PilotController).
 */
class SeoCompiler
{
    /** @return list<array{loc:string, robots:string}> */
    public static function urls(GpNode $world): array
    {
        $out = [['loc' => url('/n/'.$world->slug), 'robots' => 'index,follow']];
        $tabs = DB::table('node_tabs')->where('node_id', $world->id)->orderBy('sort')->get();
        foreach ($tabs as $t) {
            if (in_array($t->key, ['vivre', 'maison', 'home'], true)) {
                continue;
            }
            $robots = EntityFloor::robots($world, null, $t->key);
            if ($robots === 'noindex,follow') {
                continue;
            }
            $out[] = ['loc' => url('/n/'.$world->slug.'/'.$t->key), 'robots' => $robots];
        }
        foreach (ClusterGraph::of($world)['spokes'] as $s) {
            if (! $s['indexable']) {
                continue;
            }
            $out[] = ['loc' => $s['url'], 'robots' => 'index,follow'];
        }
        if (Schema::hasTable('threads')) {
            foreach (Thread::query()->where('node_id', $world->id)->get() as $th) {
                $out[] = ['loc' => url('/n/'.$world->slug.'/t/'.$th->id), 'robots' => 'index,follow'];
            }
        }
        if (Schema::hasTable('products')) {
            foreach (Product::query()->where('node_id', $world->id)->get() as $p) {
                $out[] = ['loc' => url('/n/'.$world->slug.'/p/'.$p->id), 'robots' => 'index,follow'];
            }
        }
        if (Schema::hasTable('articles')) {
            foreach (Article::query()->where('node_id', $world->id)->get() as $a) {
                $out[] = ['loc' => url('/n/'.$world->slug.'/blog/'.$a->slug), 'robots' => 'index,follow'];
            }
        }
        if (Schema::hasTable('wiki_pages')) {
            foreach (DB::table('wiki_pages')->where('node_id', $world->id)->get() as $w) {
                $out[] = ['loc' => url('/n/'.$world->slug.'/guide/'.Str::slug($w->title)), 'robots' => 'index,follow'];
            }
        }

        return $out;
    }

    public static function sitemapXml(GpNode $world): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (self::urls($world) as $u) {
            $xml .= '<url><loc>'.e($u['loc']).'</loc></url>';
        }
        $xml .= '</urlset>';

        return $xml;
    }

    public static function llmsTxt(GpNode $world): string
    {
        $g = ClusterGraph::of($world);
        $lines = [
            '# '.$world->title,
            '',
            '> '.trim((string) ($world->summary ?: $world->subtitle ?: 'Univers Nodus.')),
            '',
            'Monde Nodus. L’hôte ne sort pas du coffre. Les fiches indexées sont des intentions distinctes.',
            '',
            '## Hub',
            '- ['.$world->title.']('.url('/n/'.$world->slug).'): hub du cluster',
            '',
            '## Fiches',
        ];
        $n = 0;
        foreach ($g['spokes'] as $s) {
            if (! $s['indexable']) {
                continue;
            }
            $lines[] = '- ['.$s['title'].']('.$s['url'].'): '.$s['entity'];
            $n++;
            if ($n >= 40) {
                break;
            }
        }
        if ($n === 0) {
            $lines[] = '- Aucune fiche publiée. Le pack n’indexe pas de pages vides.';
        }
        $lines[] = '';
        $lines[] = '## Salles';
        foreach ($g['indexes'] as $idx) {
            if (! $idx['indexable']) {
                continue;
            }
            $lines[] = '- ['.$idx['label'].']('.$idx['url'].')';
        }

        return implode("\n", $lines)."\n";
    }

    public static function worldGraph(GpNode $world): array
    {
        $g = ClusterGraph::of($world);
        $graph = [
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Nodus', 'item' => url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $world->title, 'item' => $g['hub']['url']],
                ],
            ],
            [
                '@type' => 'CreativeWork',
                'name' => $world->title,
                'url' => $g['hub']['url'],
                'description' => $g['hub']['summary'],
            ],
        ];
        $list = [];
        $pos = 1;
        foreach ($g['spokes'] as $s) {
            if (! $s['indexable']) {
                continue;
            }
            $list[] = ['@type' => 'ListItem', 'position' => $pos++, 'url' => $s['url'], 'name' => $s['title']];
            $graph[] = [
                '@type' => $s['schema'],
                'name' => $s['title'],
                'url' => $s['url'],
                'isPartOf' => ['@type' => 'CreativeWork', 'name' => $world->title, 'url' => $g['hub']['url']],
            ];
        }
        if ($list) {
            $graph[] = ['@type' => 'ItemList', 'name' => 'Fiches — '.$world->title, 'itemListElement' => $list];
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    public static function entityGraph(GpNode $world, GpNode $fiche): array
    {
        $cluster = ClusterGraph::of($world);
        $spoke = collect($cluster['spokes'])->firstWhere('id', $fiche->id);
        $type = $spoke['schema'] ?? 'CreativeWork';
        $related = [];
        foreach ($cluster['links'] as $l) {
            if (($l['from'] ?? '') === ($spoke['url'] ?? '') && $l['rel'] !== 'hub' && $l['rel'] !== 'index') {
                $related[] = $l['to'];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => $world->title, 'item' => url('/n/'.$world->slug)],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $fiche->title, 'item' => url('/n/'.$world->slug.'/f/'.$fiche->slug)],
                    ],
                ],
                [
                    '@type' => $type,
                    'name' => $fiche->title,
                    'url' => url('/n/'.$world->slug.'/f/'.$fiche->slug),
                    'description' => $fiche->summary,
                    'isPartOf' => ['@type' => 'CreativeWork', 'name' => $world->title, 'url' => url('/n/'.$world->slug)],
                    'relatedLink' => array_slice($related, 0, 8),
                ],
            ],
        ];
    }
}
