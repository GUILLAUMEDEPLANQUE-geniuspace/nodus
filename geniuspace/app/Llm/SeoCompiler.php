<?php

namespace App\Llm;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Compilateur SEO — le moat.
 * Un nœud → title, slug, description, keywords (enfants), JSON-LD, maillage.
 */
class SeoCompiler
{
    public static function compile(GpNode $node): array
    {
        $children = GpNode::query()->whereIn(
            'id',
            Edge::query()->where('from_id', $node->id)->pluck('to_id')
        )->get();
        $parents = GpNode::query()->whereIn(
            'id',
            Edge::query()->where('to_id', $node->id)->pluck('from_id')
        )->get();
        $title = $node->title.' — '.( $parents->first()->title ?? 'Geniuspace');
        $desc = Str::limit(trim($node->summary ?: $node->body ?: $node->title), 158);
        $keys = $children->pluck('title')->merge([$node->kind, $node->title])->unique()->implode(', ');
        $ld = [
            '@context' => 'https://schema.org',
            '@type' => $node->kind === 'company' ? 'Organization' : ($node->kind === 'product' ? 'Product' : 'CreativeWork'),
            'name' => $node->title,
            'description' => $desc,
            'url' => url('/n/'.$node->slug),
            'isPartOf' => $parents->map(fn ($p) => ['@type' => 'CreativeWork', 'name' => $p->title, 'url' => url('/n/'.$p->slug)])->values(),
            'hasPart' => $children->map(fn ($c) => ['@type' => 'CreativeWork', 'name' => $c->title, 'url' => url('/n/'.$c->slug)])->values(),
        ];
        DB::table('node_seo')->updateOrInsert(['node_id' => $node->id], [
            'title' => $title,
            'description' => $desc,
            'keywords' => $keys,
            'noindex' => 0,
        ]);
        Toolbelt::field([
            'node_id' => $node->id,
            'name' => 'Auto Metadata',
            'type' => 'auto_meta',
            'value' => $title.' | '.$desc,
            'seo_title' => $title,
        ]);
        $links = $parents->concat($children)->map(fn ($n) => ['title' => $n->title, 'slug' => $n->slug])->values();
        return ['title' => $title, 'description' => $desc, 'keywords' => $keys, 'jsonld' => $ld, 'internal' => $links];
    }
}
