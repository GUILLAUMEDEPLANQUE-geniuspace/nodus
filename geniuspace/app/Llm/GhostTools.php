<?php

namespace App\Llm;

use App\Models\GpNode;
use App\Support\Chrome;
use App\Support\Engine;
use App\Support\Grantor;
use App\Support\Order;

/**
 * Tools du Ghost visiteur : lecture + orientation.
 * Interdit : inventer un grant, écrire un unlock, sortir d'une fourchette prix.
 */
class GhostTools
{
    public static function schema(): array
    {
        return [
            ['name' => 'list_products', 'description' => 'Liste produits et prix du lieu'],
            ['name' => 'list_neighbors', 'description' => 'Lieux liés (fait partie de / contient)'],
            ['name' => 'list_media', 'description' => 'Vidéos du lieu et état ouvert/fermé'],
            ['name' => 'check_grants', 'description' => 'Preuves déjà tenues par le visiteur'],
            ['name' => 'list_rooms', 'description' => 'Salles du dock'],
            ['name' => 'list_fiches', 'description' => 'Fiches pack / guides / magazine du lieu'],
            ['name' => 'order_options', 'description' => 'Options d\'achat d\'un produit'],
            ['name' => 'compare_products', 'description' => 'Filtre produits selon contraintes (prix, style)'],
            ['name' => 'rank', 'description' => 'Classe produits ou missions selon contraintes et mémoire'],
            ['name' => 'match_user_job', 'description' => 'Score mission × preuves du carnet'],
            ['name' => 'search_graph', 'description' => 'Cherche dans fiches, champs, voisins, produits'],
            ['name' => 'find_path', 'description' => 'Chemin d\'un lieu vers un voisin'],
        ];
    }

    /**
     * Dispatch d'un outil nommé (multi-call). ACT n'est jamais ici.
     *
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $args
     * @return array{tool: string, data?: mixed, citations?: list<array>, actions?: list<array>}|null
     */
    public static function call(string $name, GpNode $node, array $ctx, array $args = []): ?array
    {
        return match ($name) {
            'list_products' => self::listProducts($node, $ctx),
            'list_neighbors' => self::listNeighbors($node, $ctx),
            'list_media' => self::listMedia($node, $ctx),
            'check_grants' => self::checkGrants($node, $ctx),
            'list_rooms' => self::listRooms($node, $ctx),
            'list_fiches' => self::listFiches($ctx),
            'order_options' => self::orderOptions((string) ($args['product_id'] ?? '')),
            'compare_products' => self::compareProducts($node, $ctx, $args['constraints'] ?? []),
            'rank' => self::rank($node, $ctx, $args),
            'match_user_job' => self::matchUserJob($node, $ctx),
            'search_graph' => self::searchGraph($node, $ctx, (string) ($args['message'] ?? '')),
            'find_path' => self::findPath($node, $ctx, (string) ($args['message'] ?? '')),
            'purchase', 'send_application', 'unlock_content', 'modify_graph' => null,
            default => self::runIntent($node, $name, (string) ($args['message'] ?? ''), $ctx),
        };
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array{tool: string, data?: mixed, citations?: list<array>, actions?: list<array>}|null
     */
    public static function runIntent(GpNode $node, string $intent, string $message, array $ctx): ?array
    {
        return match ($intent) {
            'price', 'order' => self::listProducts($node, $ctx),
            'navigate' => self::listRooms($node, $ctx),
            'carnet' => self::checkGrants($node, $ctx),
            'fiches' => self::listFiches($ctx),
            'unlock', 'certificate' => self::listMedia($node, $ctx),
            'jobs' => self::listNeighbors($node, $ctx),
            default => null,
        };
    }

    public static function listProducts(GpNode $node, array $ctx): array
    {
        $produits = $ctx['produits'] ?? [];
        $citations = [];
        foreach ($produits as $p) {
            $citations[] = ['label' => $p['titre'].' · '.$p['prix'], 'url' => $p['url']];
        }

        return [
            'tool' => 'list_products',
            'data' => ['produits' => $produits],
            'citations' => $citations,
            'actions' => [['label' => 'Boutique', 'href' => Chrome::shopPath($node)]],
        ];
    }

    public static function listNeighbors(GpNode $node, array $ctx): array
    {
        $liens = $ctx['liens'] ?? ['fait_partie_de' => [], 'contient' => []];
        $citations = [];
        foreach (array_merge($liens['contient'] ?? [], $liens['fait_partie_de'] ?? []) as $l) {
            $citations[] = ['label' => $l['titre'], 'url' => $l['url']];
        }

        return [
            'tool' => 'list_neighbors',
            'data' => $liens,
            'citations' => array_slice($citations, 0, 8),
            'actions' => [],
        ];
    }

    public static function listMedia(GpNode $node, array $ctx): array
    {
        $videos = $ctx['videos'] ?? [];
        $citations = array_map(fn ($v) => ['label' => $v['titre'], 'url' => $v['url']], $videos);

        return [
            'tool' => 'list_media',
            'data' => ['videos' => $videos, 'fichiers' => $ctx['fichiers'] ?? []],
            'citations' => $citations,
            'actions' => [['label' => 'Vidéos', 'href' => '/n/'.$node->slug.'/videos']],
        ];
    }

    public static function checkGrants(GpNode $node, array $ctx): array
    {
        $mine = $ctx['preuves_visiteur'] ?? Grantor::mine($node->id);

        return [
            'tool' => 'check_grants',
            'data' => ['preuves' => $mine],
            'citations' => [['label' => 'Carnet', 'url' => '/n/'.$node->slug.'/carnet']],
            'actions' => [['label' => 'Ouvrir le carnet', 'href' => '/n/'.$node->slug.'/carnet']],
        ];
    }

    public static function listFiches(array $ctx): array
    {
        $cards = $ctx['fiches'] ?? [];

        return [
            'tool' => 'list_fiches',
            'data' => ['fiches' => $cards],
            'citations' => array_map(fn ($c) => ['label' => $c['titre'], 'url' => $c['url']], $cards),
            'actions' => $cards ? [['label' => $cards[0]['titre'], 'href' => $cards[0]['url']]] : [],
        ];
    }

    public static function listRooms(GpNode $node, array $ctx): array
    {
        $salles = $ctx['salles'] ?? [];

        return [
            'tool' => 'list_rooms',
            'data' => ['salles' => $salles],
            'citations' => array_map(fn ($s) => ['label' => $s['label'], 'url' => $s['url']], $salles),
            'actions' => array_slice(array_map(fn ($s) => ['label' => $s['label'], 'href' => $s['url']], $salles), 0, 4),
        ];
    }

    public static function orderOptions(string $productId): array
    {
        if ($productId === '') {
            return ['tool' => 'order_options', 'data' => []];
        }
        $fields = Order::fields($productId);
        $out = [];
        foreach ($fields as $f) {
            $out[] = [
                'label' => $f->name,
                'key' => $f->field_key,
                'choices' => Order::choices($f),
            ];
        }

        return ['tool' => 'order_options', 'data' => $out];
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    public static function compareProducts(GpNode $node, array $ctx, array $constraints): array
    {
        $max = isset($constraints['max_price']) ? (int) $constraints['max_price'] : 0;
        $style = mb_strtolower((string) ($constraints['style'] ?? ''));
        $out = [];
        foreach ($ctx['produits'] ?? [] as $p) {
            $prix = (int) round((float) preg_replace('/[^\d.,]/', '', (string) ($p['prix'] ?? '0')));
            if ($max && $prix && $prix > $max) {
                continue;
            }
            $hay = mb_strtolower(($p['titre'] ?? '').' '.($p['prix'] ?? ''));
            $score = 0.5;
            if ($style !== '' && str_contains($hay, $style)) {
                $score += 0.3;
            }
            if ($max && $prix) {
                $score += max(0, 1 - ($prix / max($max, 1))) * 0.2;
            }
            $out[] = $p + ['score' => round($score, 3), 'prix_n' => $prix];
        }
        usort($out, fn ($a, $b) => ($b['score'] <=> $a['score']));

        return [
            'tool' => 'compare_products',
            'data' => ['produits' => $out, 'compared' => $out],
            'citations' => array_map(fn ($p) => ['label' => $p['titre'].' · '.$p['prix'], 'url' => $p['url'] ?? ''], array_slice($out, 0, 4)),
            'actions' => $out ? [['label' => $out[0]['titre'], 'href' => $out[0]['url'] ?? Chrome::shopPath($node)]] : [['label' => 'Boutique', 'href' => Chrome::shopPath($node)]],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    public static function rank(GpNode $node, array $ctx, array $args): array
    {
        $items = $args['data']['compared'] ?? $args['data']['matches'] ?? $ctx['produits'] ?? $ctx['compared'] ?? $ctx['matches'] ?? [];
        if (! is_array($items) || $items === []) {
            $items = $ctx['produits'] ?? [];
        }
        $style = mb_strtolower((string) (($args['constraints']['style'] ?? '') ?: ($args['working']['constraints']['style'] ?? '')));
        $ranked = [];
        foreach ($items as $it) {
            if (! is_array($it)) {
                continue;
            }
            $s = (float) ($it['score'] ?? 0.5);
            $hay = mb_strtolower(json_encode($it, JSON_UNESCAPED_UNICODE) ?: '');
            if ($style !== '' && str_contains($hay, $style)) {
                $s += 0.15;
            }
            $ranked[] = $it + ['score' => round($s, 3)];
        }
        usort($ranked, fn ($a, $b) => ($b['score'] <=> $a['score']));

        return [
            'tool' => 'rank',
            'data' => ['ranked' => $ranked, 'produits' => $ctx['produits'] ?? $ranked],
            'citations' => array_map(fn ($p) => ['label' => $p['titre'] ?? $p['title'] ?? 'item', 'url' => $p['url'] ?? ''], array_slice($ranked, 0, 3)),
            'actions' => [],
        ];
    }

    public static function matchUserJob(GpNode $node, array $ctx): array
    {
        $proofs = collect($ctx['preuves_visiteur'] ?? [])->map(fn ($p) => mb_strtolower((string) ($p['titre'] ?? $p['quoi'] ?? '')))->filter()->all();
        $jobs = collect($ctx['liens']['contient'] ?? [])->filter(function ($l) {
            $n = mb_strtolower((string) ($l['nature'] ?? ''));
            $t = mb_strtolower((string) ($l['titre'] ?? ''));

            return $n === 'offre' || str_contains($t, 'mission') || str_contains($t, 'emploi');
        })->values();
        if ($jobs->isEmpty()) {
            $jobs = collect($ctx['liens']['contient'] ?? [])->take(8);
        }
        $matches = $jobs->map(function ($job) use ($proofs) {
            $title = (string) ($job['titre'] ?? '');
            $hay = mb_strtolower($title.' '.($job['nature'] ?? ''));
            $hit = 0;
            foreach ($proofs as $pr) {
                if ($pr !== '' && (str_contains($hay, $pr) || str_contains($pr, mb_substr($hay, 0, 8)))) {
                    $hit++;
                }
            }
            $need = max(1, 2 - $hit);
            $missing = $hit ? [] : ['preuve métier datée'];
            $score = $proofs ? min(0.95, 0.4 + $hit * 0.25) : 0.45;

            return [
                'titre' => $title,
                'url' => $job['url'] ?? '',
                'score' => $score,
                'strengths' => $hit ? array_slice($proofs, 0, $hit) : [],
                'missing' => $missing,
                'evidence' => array_slice($proofs, 0, 3),
            ];
        })->sortByDesc('score')->values()->all();

        return [
            'tool' => 'match_user_job',
            'data' => ['matches' => $matches],
            'citations' => array_map(fn ($m) => ['label' => $m['titre'], 'url' => $m['url']], array_slice($matches, 0, 3)),
            'actions' => $matches ? [['label' => $matches[0]['titre'], 'href' => $matches[0]['url']]] : [],
        ];
    }

    public static function searchGraph(GpNode $node, array $ctx, string $message): array
    {
        $q = mb_strtolower($message);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $hits = [];
        $scan = function (string $label, string $url, string $hay) use (&$hits, $words) {
            $h = mb_strtolower($hay);
            $s = 0;
            foreach ($words as $w) {
                if (mb_strlen($w) > 2 && str_contains($h, $w)) {
                    $s++;
                }
            }
            if ($s > 0) {
                $hits[] = ['titre' => $label, 'url' => $url, 'score' => $s];
            }
        };
        foreach ($ctx['fiches'] ?? [] as $c) {
            $scan($c['titre'] ?? 'fiche', $c['url'] ?? '', ($c['titre'] ?? '').' '.($c['extrait'] ?? ''));
        }
        foreach ($ctx['details'] ?? [] as $d) {
            $scan($d['label'] ?? 'champ', $ctx['lieu']['url'] ?? '', ($d['label'] ?? '').' '.($d['value'] ?? ''));
        }
        foreach ($ctx['produits'] ?? [] as $p) {
            $scan($p['titre'] ?? 'œuvre', $p['url'] ?? '', ($p['titre'] ?? '').' '.($p['prix'] ?? ''));
        }
        foreach (array_merge($ctx['liens']['contient'] ?? [], $ctx['liens']['fait_partie_de'] ?? []) as $l) {
            $scan($l['titre'] ?? 'lieu', $l['url'] ?? '', $l['titre'] ?? '');
        }
        usort($hits, fn ($a, $b) => $b['score'] <=> $a['score']);
        $hits = array_slice($hits, 0, 8);

        return [
            'tool' => 'search_graph',
            'data' => ['hits' => $hits],
            'citations' => array_map(fn ($h) => ['label' => $h['titre'], 'url' => $h['url']], $hits),
            'actions' => $hits ? [['label' => $hits[0]['titre'], 'href' => $hits[0]['url']]] : [],
        ];
    }

    public static function findPath(GpNode $node, array $ctx, string $message): array
    {
        $neighbors = array_merge($ctx['liens']['contient'] ?? [], $ctx['liens']['fait_partie_de'] ?? []);
        $path = [['titre' => $ctx['lieu']['titre'] ?? $node->title, 'url' => $ctx['lieu']['url'] ?? '']];
        $q = mb_strtolower($message);
        foreach ($neighbors as $l) {
            $t = mb_strtolower((string) ($l['titre'] ?? ''));
            if ($t !== '' && (str_contains($q, $t) || str_contains($t, mb_substr($q, 0, 8)))) {
                $path[] = ['titre' => $l['titre'], 'url' => $l['url'] ?? ''];
                break;
            }
        }
        if (count($path) === 1 && $neighbors) {
            $path[] = ['titre' => $neighbors[0]['titre'], 'url' => $neighbors[0]['url'] ?? ''];
        }

        return [
            'tool' => 'find_path',
            'data' => ['path' => $path],
            'citations' => array_map(fn ($p) => ['label' => $p['titre'], 'url' => $p['url']], $path),
            'actions' => count($path) > 1 ? [['label' => $path[1]['titre'], 'href' => $path[1]['url']]] : [],
        ];
    }
}
