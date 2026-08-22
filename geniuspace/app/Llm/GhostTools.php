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
        ];
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
}
