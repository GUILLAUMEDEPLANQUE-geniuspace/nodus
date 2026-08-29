<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Plancher d’unicité. Une URL indexable n’est pas un pack vide. */
class EntityFloor
{
    public const MIN_COPY = 80;

    public const MAX_SIBLING = 0.72;

    public static function published(GpNode $node): bool
    {
        $copy = trim((string) ($node->body ?: $node->summary ?: $node->subtitle ?: ''));
        if (mb_strlen($copy) < self::MIN_COPY) {
            return false;
        }
        $statut = self::field($node->id, 'statut');
        if ($statut !== '' && ! in_array(mb_strtolower($statut), ['publie', 'publié', 'published', 'public'], true)) {
            return false;
        }

        return true;
    }

    public static function uniqueEnough(GpNode $world, GpNode $fiche): bool
    {
        $mine = self::fingerprint($fiche);
        if ($mine === '') {
            return false;
        }
        foreach (self::siblings($world, $fiche) as $sib) {
            if (GhostConsistency::jaccard($mine, self::fingerprint($sib)) >= self::MAX_SIBLING) {
                return false;
            }
        }

        return true;
    }

    public static function indexable(GpNode $world, GpNode $fiche): bool
    {
        return self::published($fiche) && self::uniqueEnough($world, $fiche);
    }

    public static function roomHasContent(GpNode $world, string $key): bool
    {
        return match ($key) {
            'vivre', 'maison', 'home' => true,
            'forum' => DB::table('threads')->where('node_id', $world->id)->where('kind', 'forum')->exists(),
            'journal', 'blog' => Schema::hasTable('articles') && DB::table('articles')->where('node_id', $world->id)->exists(),
            'videos', 'stories', 'audio' => Schema::hasTable('media') && DB::table('media')->where('node_id', $world->id)->exists(),
            'boutique', 'boutique_expert', 'classifieds', 'merch' => Schema::hasTable('products') && DB::table('products')->where('node_id', $world->id)->exists(),
            'guides' => Schema::hasTable('wiki_pages') && DB::table('wiki_pages')->where('node_id', $world->id)->exists(),
            'personnages', 'collections' => self::children($world)->contains(fn (GpNode $n) => self::indexable($world, $n)),
            'gallery', 'reliques' => Schema::hasTable('drive_files') && DB::table('drive_files')->where('node_id', $world->id)->exists(),
            'carnet' => true,
            default => false,
        };
    }

    public static function robots(GpNode $world, ?GpNode $fiche = null, ?string $room = null): string
    {
        if ($fiche) {
            return self::indexable($world, $fiche) ? 'index,follow' : 'noindex,follow';
        }
        if ($room && $room !== 'vivre') {
            return self::roomHasContent($world, $room) ? 'index,follow' : 'noindex,follow';
        }

        return 'index,follow';
    }

    public static function children(GpNode $world)
    {
        $ids = Edge::query()->where('from_id', $world->id)->pluck('to_id');

        return GpNode::query()->whereIn('id', $ids)->get();
    }

    private static function siblings(GpNode $world, GpNode $fiche): array
    {
        return self::children($world)->filter(fn (GpNode $n) => $n->id !== $fiche->id && $n->kind === $fiche->kind)->values()->all();
    }

    private static function fingerprint(GpNode $node): string
    {
        $bits = [$node->title, $node->summary, $node->body];
        if (Schema::hasTable('cck_fields')) {
            foreach (DB::table('cck_fields')->where('node_id', $node->id)->get() as $f) {
                $v = trim((string) ($f->value ?? ''));
                if ($v !== '' && ($f->field_key ?? '') !== 'statut') {
                    $bits[] = $v;
                }
            }
        }

        return trim(implode(' ', array_filter($bits)));
    }

    private static function field(string $nodeId, string $key): string
    {
        if (! Schema::hasTable('cck_fields')) {
            return '';
        }

        return trim((string) (DB::table('cck_fields')->where('node_id', $nodeId)->where('field_key', $key)->value('value') ?? ''));
    }
}
