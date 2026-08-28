<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lecture du monde. Pas une estimation. Pas une expérience.
 *
 * Comptages + trous (gaps) du graphe. Un contraste de catalogue n’est
 * pas une expérience naturelle et ne produit jamais un observed_gain.
 */
class GhostWorldObserver
{
    public const MIN_N = 4;

    /**
     * @return array<string, mixed>
     */
    public static function of(string $nodeId): array
    {
        $empty = self::blank($nodeId);
        if ($nodeId === '') {
            return $empty;
        }
        $node = GpNode::query()->find($nodeId);
        $profile = $node ? Ghost::profile($node) : 'guide';

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
        $validated = Schema::hasTable('edges')
            ? (int) Edge::query()->where('kind', 'validated')->whereIn('to_id', self::childIds($nodeId))->count()
            : 0;

        $base = [
            'node_id' => $nodeId,
            'source' => 'engine',
            'profile' => $profile,
            'media' => $mediaN,
            'media_gated' => $gated->count(),
            'media_free' => $free->count(),
            'media_views' => $mediaViews,
            'grants' => $grantN,
            'validated' => $validated,
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
            'preuves_tenues' => $grantN + $validated,
            'cohorts' => [
                'threads_hot' => $hot->count(),
                'threads_cold' => $cold->count(),
                'media_gated' => $gated->count(),
                'media_free' => $free->count(),
            ],
        ];
        $gaps = self::gaps($node, $profile, $base, $products, $media, $files, $fields);
        $base['gaps'] = $gaps;
        $base['contrasts'] = self::contrasts($base, $gaps);
        $base['family'] = GhostStrategy::family($profile);

        return $base;
    }

    /**
     * Contrastes de catalogue. Jamais une expérience. Jamais un observed.
     *
     * @param  array<string, mixed>  $world
     * @return list<array<string, mixed>>
     */
    public static function associations(array $world): array
    {
        return $world['contrasts'] ?? self::contrasts($world, $world['gaps'] ?? []);
    }

    /**
     * Événements datés. C’est ça, le matériau d’une vraie observation.
     *
     * @return array{grants:int, replies:int, visits:int, n:int}
     */
    public static function events(string $nodeId, mixed $since = null): array
    {
        $grants = 0;
        $replies = 0;
        $visits = 0;
        if ($nodeId !== '' && Schema::hasTable('grants')) {
            $q = DB::table('grants')->where('node_id', $nodeId);
            if ($since && Schema::hasColumn('grants', 'created_at')) {
                $q->where('created_at', '>=', $since);
            }
            $grants = (int) $q->count();
        }
        if ($nodeId !== '' && Schema::hasTable('threads') && Schema::hasTable('replies')) {
            $tids = DB::table('threads')->where('node_id', $nodeId)->pluck('id');
            if ($tids->isNotEmpty()) {
                $rq = DB::table('replies')->whereIn('thread_id', $tids);
                if ($since && Schema::hasColumn('replies', 'created_at')) {
                    $rq->where('created_at', '>=', $since);
                }
                $replies = (int) $rq->count();
            }
        }
        if ($nodeId !== '' && Schema::hasTable('visits')) {
            $vq = DB::table('visits')->where('node_id', $nodeId);
            if ($since && Schema::hasColumn('visits', 'created_at')) {
                $vq->where('created_at', '>=', $since);
            }
            $visits = (int) $vq->count();
        }

        return [
            'grants' => $grants,
            'replies' => $replies,
            'visits' => $visits,
            'n' => $grants + $replies + $visits,
        ];
    }

    /**
     * Une intervention réelle (APPLY d’un strategy.deploy). Preview ≠ intervention.
     */
    public static function intervenedAt(string $nodeId, string $code = ''): mixed
    {
        if ($nodeId === '' || ! Schema::hasTable('ghost_actions')) {
            return null;
        }
        $q = DB::table('ghost_actions')
            ->where('node_id', $nodeId)
            ->where('status', 'applied')
            ->where(function ($w) use ($code) {
                $w->where('action', 'strategy.deploy');
                if ($code !== '') {
                    $w->orWhere('ops', 'like', '%'.$code.'%');
                }
            });
        $row = $q->orderByDesc('id')->first();

        return $row->updated_at ?? $row->created_at ?? null;
    }

    /**
     * @param  array<string, mixed>  $world
     * @param  array<string, array<string, mixed>>  $gaps
     * @return list<array<string, mixed>>
     */
    private static function contrasts(array $world, array $gaps): array
    {
        $out = [];
        foreach ($gaps as $lever => $g) {
            $n = (int) ($g['n'] ?? 0);
            $hit = (int) ($g['hit'] ?? 0);
            $miss = max(0, $n - $hit);
            if ($n < 2 || ($hit === 0 && $miss === 0)) {
                continue;
            }
            $out[] = [
                'lever' => $lever,
                'cause' => $lever,
                'effect' => $g['metric'] ?? 'gap',
                'hit' => $hit,
                'miss' => $miss,
                'n' => $n,
                'size' => (float) ($g['size'] ?? 0),
                'method' => 'catalog_contrast',
                'underpowered' => $n < self::MIN_N,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, array<string, mixed>>
     */
    private static function gaps(?GpNode $node, string $profile, array $base, $products, $media, $files, array $fields): array
    {
        if ($profile === 'rh') {
            return self::gapsRh($node, $base);
        }
        if ($profile === 'marchand') {
            return self::gapsShop($base, $products, $media, $files);
        }

        return self::gapsGuide($base, $fields);
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, array<string, mixed>>
     */
    private static function gapsRh(?GpNode $node, array $base): array
    {
        $jobs = $node ? self::jobsOf($node->id) : collect();
        $n = max(0, $jobs->count());
        $salaire = 0;
        $delai = 0;
        $honneur = 0;
        $missingProof = 0;
        $wanted = 0;
        foreach ($jobs as $job) {
            $f = Engine::fields($job->id);
            if (trim((string) ($f['salaire']->value ?? '')) !== '') {
                $salaire++;
            }
            $inh = Engine::inherit($job);
            if ($inh['delayDays']) {
                $delai++;
            }
            if ($inh['honor']) {
                $honneur++;
            }
            $carnet = GpNode::query()->where('id', 'carnet-karim')->exists() ? 'carnet-karim' : '';
            $al = Engine::align($carnet, $job->id);
            $missingProof += count($al['missing'] ?? []);
            $wanted += count($al['hits'] ?? []) + count($al['missing'] ?? []);
        }
        $epreuve = (int) ($base['validated'] ?? 0);

        return [
            'clarte_salaire' => self::gap($n - $salaire, $n, 'jobs sans salaire publié', 'preuves_tenues'),
            'delai' => self::gap($n - $delai, $n, 'offres sans délai hérité', 'preuves_tenues'),
            'honneur' => self::gap($n - $honneur, $n, 'offres sans fiabilité héritée', 'preuves_tenues'),
            'epreuve' => self::gap($n > 0 && $epreuve === 0 ? $n : max(0, $n - $epreuve), max($n, 1), 'épreuves non tenues', 'preuves_tenues'),
            'preuves' => self::gap($missingProof, max($wanted, 1), 'preuves manquantes du carnet', 'preuves_tenues'),
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, array<string, mixed>>
     */
    private static function gapsShop(array $base, $products, $media, $files): array
    {
        $pn = max(0, $products->count());
        $floor = 0;
        $range = 0;
        foreach ($products as $p) {
            $price = (string) ($p->price ?? '');
            if (preg_match('/\d/', $price)) {
                $floor++;
            }
            if (isset($p->min_val, $p->max_val) || preg_match('/\d.+\d/', $price)) {
                $range++;
            }
        }
        $gated = (int) ($base['media_gated'] ?? 0);
        $free = (int) ($base['media_free'] ?? 0);
        $locked = (int) ($base['files_locked'] ?? 0);
        $open = (int) ($base['files_open'] ?? 0);
        $certs = $files->filter(fn ($f) => str_contains(mb_strtolower((string) ($f->title ?? $f->name ?? '')), 'certif'))->count();

        return [
            'plancher' => self::gap($pn - $floor, max($pn, 1), 'pièces sans prix tenu', 'completion'),
            'fourchette' => self::gap($pn - $range, max($pn, 1), 'pièces sans fourchette', 'completion'),
            'teaser' => self::gap($gated, max($gated + $free, 1), 'médias lockés sans teaser public', 'completion'),
            'certificat' => self::gap(max(0, $pn - $certs), max($pn, 1), 'pièces sans certificat au coffre', 'completion'),
            'rarete' => self::gap($open, max($locked + $open, 1), 'fichiers ouverts vs tenus', 'completion'),
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, array<string, mixed>>
     */
    private static function gapsGuide(array $base, array $fields): array
    {
        $threads = (int) ($base['threads'] ?? 0);
        $cold = (int) (($base['cohorts']['threads_cold'] ?? 0));
        $fiches = (int) ($base['fields'] ?? 0);
        $grants = (int) ($base['grants'] ?? 0);
        $gated = (int) ($base['media_gated'] ?? 0);
        $media = (int) ($base['media'] ?? 0);

        return [
            'salon' => self::gap($cold, max($threads, 1), 'sujets sans réponse', 'participation'),
            'fiches' => self::gap($fiches === 0 ? 1 : 0, 1, 'fiche monde vide', 'participation'),
            'rideau' => self::gap($gated, max($media, 1), 'médias encore derrière le rideau', 'participation'),
            'portes' => self::gap($media === 0 ? 1 : 0, 1, 'pas de porte média', 'participation'),
            'carnet' => self::gap($grants === 0 ? 1 : 0, 1, 'carnet vide sur ce lieu', 'preuves_tenues'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function gap(int $miss, int $n, string $label, string $metric): array
    {
        $n = max(1, $n);
        $miss = max(0, $miss);

        return [
            'miss' => $miss,
            'hit' => max(0, $n - $miss),
            'n' => $n,
            'size' => round(min(1, $miss / $n), 4),
            'label' => $label,
            'metric' => $metric,
        ];
    }

    /**
     * @return list<string>
     */
    private static function childIds(string $nodeId): array
    {
        if ($nodeId === '' || ! Schema::hasTable('edges')) {
            return [];
        }

        return Edge::query()->where('from_id', $nodeId)->pluck('to_id')->filter()->values()->all();
    }

    private static function jobsOf(string $nodeId)
    {
        $ids = self::childIds($nodeId);
        $jobs = $ids ? GpNode::query()->whereIn('id', $ids)->where('kind', 'job')->get() : collect();
        if ($jobs->isNotEmpty()) {
            return $jobs;
        }
        $second = [];
        foreach ($ids as $id) {
            $second = array_merge($second, self::childIds((string) $id));
        }
        if ($second) {
            $jobs = GpNode::query()->whereIn('id', $second)->where('kind', 'job')->get();
        }

        return $jobs;
    }

    /**
     * @return array<string, mixed>
     */
    private static function blank(string $nodeId): array
    {
        return [
            'node_id' => $nodeId,
            'source' => 'engine',
            'profile' => 'guide',
            'media' => 0,
            'media_gated' => 0,
            'media_free' => 0,
            'media_views' => 0,
            'grants' => 0,
            'validated' => 0,
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
            'preuves_tenues' => 0,
            'cohorts' => ['threads_hot' => 0, 'threads_cold' => 0, 'media_gated' => 0, 'media_free' => 0],
            'gaps' => [],
            'contrasts' => [],
            'family' => GhostStrategy::LEVERS_GUIDE,
        ];
    }
}
