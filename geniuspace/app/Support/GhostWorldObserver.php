<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lecture du monde. Pas une estimation.
 * Engine = vérité métier. Ici : comptages tenus (preuves, salon, médias, visites).
 * N’écrit rien. N’appelle pas Grantor.
 */
class GhostWorldObserver
{
    /**
     * @return array<string, mixed>
     */
    public static function of(string $nodeId): array
    {
        $empty = self::blank($nodeId);
        if ($nodeId === '') {
            return $empty;
        }

        $media = Schema::hasTable('media')
            ? DB::table('media')->where('node_id', $nodeId)->get()
            : collect();
        $gated = $media->filter(fn ($m) => ($m->access ?? '') !== 'free' && ($m->mode ?? '') !== 'lore');
        $free = $media->filter(fn ($m) => ($m->access ?? '') === 'free' || ($m->mode ?? '') === 'lore');
        $grants = Schema::hasTable('grants')
            ? DB::table('grants')->where('node_id', $nodeId)->get()
            : collect();
        $threads = Schema::hasTable('threads')
            ? DB::table('threads')->where('node_id', $nodeId)->get()
            : collect();
        $tids = $threads->pluck('id')->all();
        $replies = ($tids && Schema::hasTable('replies'))
            ? (int) DB::table('replies')->whereIn('thread_id', $tids)->count()
            : (int) $threads->sum('replies_count');
        $products = Schema::hasTable('products')
            ? DB::table('products')->where('node_id', $nodeId)->get()
            : collect();
        $visits = Schema::hasTable('visits')
            ? (int) DB::table('visits')->where('node_id', $nodeId)->count()
            : 0;
        $files = Schema::hasTable('drive_files')
            ? DB::table('drive_files')->where('node_id', $nodeId)->get()
            : collect();
        $fields = Engine::fields($nodeId, null);

        $threadViews = (int) $threads->sum('views');
        $mediaViews = (int) $media->sum('views');
        $grantN = $grants->count();
        $mediaN = $media->count();

        $hot = $threads->filter(fn ($t) => (int) ($t->fires ?? 0) > 0 || (int) ($t->replies_count ?? 0) > 0);
        $cold = $threads->filter(fn ($t) => (int) ($t->fires ?? 0) === 0 && (int) ($t->replies_count ?? 0) === 0);

        return [
            'node_id' => $nodeId,
            'source' => 'engine',
            'media' => $mediaN,
            'media_gated' => $gated->count(),
            'media_free' => $free->count(),
            'media_views' => $mediaViews,
            'grants' => $grantN,
            'completion' => $mediaViews > 0 ? round($grantN / $mediaViews, 4) : ($mediaN > 0 ? round($grantN / $mediaN, 4) : 0.0),
            'threads' => $threads->count(),
            'replies' => $replies,
            'thread_views' => $threadViews,
            'participation' => $threadViews > 0 ? round($replies / $threadViews, 4) : ($threads->count() > 0 ? round($replies / $threads->count(), 4) : 0.0),
            'products' => $products->count(),
            'product_votes' => (int) $products->sum('votes'),
            'visits' => $visits,
            'files_locked' => $files->where('locked', 1)->count(),
            'files_open' => $files->where('locked', 0)->count(),
            'fields' => count($fields),
            'cohorts' => [
                'threads_hot' => $hot->count(),
                'threads_cold' => $cold->count(),
                'media_gated' => $gated->count(),
                'media_free' => $free->count(),
            ],
        ];
    }

    /**
     * Associations tenues dans le monde. Pas le prior BASE.
     *
     * @param  array<string, mixed>  $world
     * @return list<array<string, mixed>>
     */
    public static function associations(array $world): array
    {
        $out = [];
        $c = $world['cohorts'] ?? [];
        if (($c['threads_hot'] ?? 0) >= 1 && ($c['threads_cold'] ?? 0) >= 1) {
            $hot = (int) $c['threads_hot'];
            $cold = (int) $c['threads_cold'];
            $delta = $hot / max(1, $hot + $cold) - $cold / max(1, $hot + $cold);
            $out[] = [
                'cause' => 'social',
                'effect' => 'participation',
                'delta' => round($delta, 4),
                'method' => 'natural_experiment',
                'n' => $hot + $cold,
                'underpowered' => ($hot + $cold) < 4,
            ];
        }
        if (($c['media_free'] ?? 0) >= 1 && ($c['media_gated'] ?? 0) >= 1) {
            $out[] = [
                'cause' => 'friction',
                'effect' => 'completion',
                'delta' => round(($c['media_free'] - $c['media_gated']) / max(1, $c['media_free'] + $c['media_gated']), 4),
                'method' => 'natural_experiment',
                'n' => (int) $c['media_free'] + (int) $c['media_gated'],
                'underpowered' => ((int) $c['media_free'] + (int) $c['media_gated']) < 4,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private static function blank(string $nodeId): array
    {
        return [
            'node_id' => $nodeId,
            'source' => 'engine',
            'media' => 0,
            'media_gated' => 0,
            'media_free' => 0,
            'media_views' => 0,
            'grants' => 0,
            'completion' => 0.0,
            'threads' => 0,
            'replies' => 0,
            'thread_views' => 0,
            'participation' => 0.0,
            'products' => 0,
            'product_votes' => 0,
            'visits' => 0,
            'files_locked' => 0,
            'files_open' => 0,
            'fields' => 0,
            'cohorts' => ['threads_hot' => 0, 'threads_cold' => 0, 'media_gated' => 0, 'media_free' => 0],
        ];
    }
}
