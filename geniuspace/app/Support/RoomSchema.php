<?php

namespace App\Support;

use App\Models\Article;
use App\Models\GpNode;
use App\Models\Product;
use App\Models\Thread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Générateur JSON-LD. Une salle = un type schema.org réel, pas un onglet.
 * /n/{slug}/schema.json sert le graphe entier (effet Wikidata).
 */
class RoomSchema
{
    public static function graph(GpNode $node, string $tab, $tabs = null): array
    {
        $spec = RoomCatalog::all()[$tab] ?? ['label' => $tab, 'schema' => 'CollectionPage'];
        $label = optional($tabs?->firstWhere('key', $tab))->label ?? ($spec['label'] ?? $tab);
        $url = url('/n/'.$node->slug.($tab && ! in_array($tab, ['vivre', 'maison'], true) ? '/'.$tab : ''));
        $type = $spec['schema'] ?? 'CollectionPage';
        $item = [
            '@'.'type' => $type,
            'name' => $label.' — '.$node->title,
            'url' => $url,
            'description' => $node->summary,
            'isPartOf' => ['@'.'type' => 'CreativeWork', 'name' => $node->title, 'url' => url('/n/'.$node->slug)],
        ];

        return match ($tab) {
            'forum' => self::forum($node, $item),
            'journal', 'blog' => self::blog($node, $item),
            'videos' => self::videos($node, $item),
            'audio' => array_merge($item, ['@'.'type' => 'PodcastSeries', 'name' => $node->title.' Radio']),
            'boutique', 'boutique_expert', 'classifieds', 'merch' => self::offers($node, $item, $tab),
            'guides' => self::guides($node, $item),
            'personnages', 'collections' => self::fiches($node, $item),
            'agenda' => array_merge($item, ['@'.'type' => 'Event', 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode', 'location' => ['@'.'type' => 'Place', 'name' => $node->title]]),
            'carte' => array_merge($item, ['@'.'type' => 'Place', 'name' => $node->title]),
            'offres', 'epreuve' => array_merge($item, ['@'.'type' => 'ItemList', 'itemListElement' => []]),
            'reviews' => array_merge($item, ['@'.'type' => 'ItemList']),
            'gallery' => array_merge($item, ['@'.'type' => 'ImageGallery']),
            'reliques' => array_merge($item, ['@'.'type' => 'DataCatalog']),
            'guilde' => array_merge($item, ['@'.'type' => 'Organization', 'memberOf' => $node->title]),
            'stories' => array_merge($item, ['@'.'type' => 'ItemList']),
            default => $item,
        };
    }

    public static function document(GpNode $node): array
    {
        $tabs = DB::table('node_tabs')->where('node_id', $node->id)->orderBy('sort')->get();
        $graph = [
            ['@'.'type' => 'BreadcrumbList', 'itemListElement' => [
                ['@'.'type' => 'ListItem', 'position' => 1, 'name' => 'Geniuspace', 'item' => url('/')],
                ['@'.'type' => 'ListItem', 'position' => 2, 'name' => $node->title, 'item' => url('/n/'.$node->slug)],
            ]],
            ['@'.'type' => $node->kind === 'company' ? 'Organization' : 'CreativeWork', 'name' => $node->title, 'url' => url('/n/'.$node->slug), 'description' => $node->summary],
        ];
        foreach ($tabs as $t) {
            if ($t->key === 'vivre') {
                continue;
            }
            $graph[] = self::graph($node, $t->key, $tabs);
        }
        return ['@'.'context' => 'https://schema.org', '@'.'graph' => $graph];
    }

    public static function example(string $key, GpNode $node): array
    {
        return self::graph($node, $key);
    }

    private static function forum(GpNode $node, array $item): array
    {
        $threads = Thread::query()->where('node_id', $node->id)->where('kind', 'forum')->limit(12)->get();
        $item['@'.'type'] = 'ItemList';
        $item['itemListElement'] = $threads->values()->map(fn ($t, $i) => [
            '@'.'type' => 'ListItem',
            'position' => $i + 1,
            'url' => url('/n/'.$node->slug.'/t/'.$t->id),
            'name' => $t->title,
            'item' => ['@'.'type' => 'DiscussionForumPosting', 'headline' => $t->title, 'url' => url('/n/'.$node->slug.'/t/'.$t->id)],
        ])->all();
        return $item;
    }

    private static function blog(GpNode $node, array $item): array
    {
        $arts = Article::query()->where('node_id', $node->id)->limit(12)->get();
        $item['@'.'type'] = 'Blog';
        $item['blogPost'] = $arts->map(fn ($a) => [
            '@'.'type' => 'BlogPosting',
            'headline' => $a->title,
            'url' => url('/n/'.$node->slug.'/blog/'.$a->slug),
        ])->values()->all();
        return $item;
    }

    private static function videos(GpNode $node, array $item): array
    {
        $item['@'.'type'] = 'ItemList';
        $item['itemListElement'] = $node->media->take(12)->values()->map(fn ($m, $i) => [
            '@'.'type' => 'ListItem',
            'position' => $i + 1,
            'url' => url('/n/'.$node->slug.'/v/'.Str::slug($m->title)),
            'item' => ['@'.'type' => 'VideoObject', 'name' => $m->title, 'duration' => $m->duration],
        ])->all();
        return $item;
    }

    private static function offers(GpNode $node, array $item, string $tab): array
    {
        $item['@'.'type'] = $tab === 'boutique_expert' ? 'Store' : 'OfferCatalog';
        $item['makesOffer'] = Product::query()->where('node_id', $node->id)->limit(20)->get()->map(fn ($p) => [
            '@'.'type' => 'Offer',
            'name' => $p->title,
            'url' => url('/n/'.$node->slug.'/p/'.$p->id),
            'price' => preg_replace('/[^\d.]/', '', (string) $p->price),
            'priceCurrency' => 'EUR',
            'areaServed' => $p->city ?: null,
        ])->values()->all();
        return $item;
    }

    private static function guides(GpNode $node, array $item): array
    {
        $item['@'.'type'] = 'ItemList';
        $item['itemListElement'] = $node->wiki->take(12)->values()->map(fn ($w, $i) => [
            '@'.'type' => 'ListItem',
            'position' => $i + 1,
            'url' => url('/n/'.$node->slug.'/guide/'.Str::slug($w->title)),
            'item' => ['@'.'type' => 'HowTo', 'name' => $w->title],
        ])->all();
        return $item;
    }

    private static function fiches(GpNode $node, array $item): array
    {
        $ids = \App\Models\Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $kids = GpNode::query()->whereIn('id', $ids)->get();
        $item['@'.'type'] = 'ItemList';
        $item['itemListElement'] = $kids->values()->map(fn ($c, $i) => [
            '@'.'type' => 'ListItem',
            'position' => $i + 1,
            'url' => url('/n/'.$node->slug.'/f/'.$c->slug),
            'name' => $c->title,
        ])->all();
        return $item;
    }
}
