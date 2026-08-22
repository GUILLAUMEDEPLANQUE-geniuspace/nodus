<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\Product;
use App\Support\Chrome;
use App\Support\Engine;
use App\Support\Flagships;
use App\Support\Ghost;
use App\Support\Lore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlagshipController extends Controller
{
    public function ghost(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $msg = (string) $request->input('message', '');

        return response()->json(Ghost::reply($node, $msg, $request->input('history', [])));
    }

    public function lore(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'field' => 'required|string|max:80',
            'value' => 'required|string|max:240',
            'stake' => 'nullable|integer|min:0|max:500',
        ]);

        return response()->json(Lore::propose($node, $data['field'], $data['value'], (int) ($data['stake'] ?? 0)));
    }

    public function bible(): View
    {
        $flags = Flagships::all();

        return view('flagships.bible', compact('flags'));
    }

    public static function vaultPage(GpNode $node, Product $product): View
    {
        $flag = Flagships::of($node) ?? Flagships::all()['vault'];
        $chrome = Chrome::bag($node);
        $fields = Engine::fields($node->id);
        $piece = GpNode::query()->where('kind', 'product')->where('title', $product->title)->first();
        if ($piece) {
            $fields = array_merge($fields, Engine::fields($piece->id));
        }
        $neighbors = Engine::neighbors($piece ?? $node);
        $also = Engine::alsoInWorld($piece ?? $node, 4);
        $schema = self::productSchema($node, $product, $neighbors);
        $ghost = Flagships::of($node)['ghost'] ?? ['name' => Ghost::hostName($node), 'wake' => ''];
        $lorePending = Lore::pending($node->id);
        $hotspots = self::hotspots($fields);

        return view('flagships.vault', compact(
            'node', 'product', 'flag', 'chrome', 'fields', 'neighbors', 'also',
            'schema', 'ghost', 'lorePending', 'hotspots', 'piece'
        ));
    }

    private static function productSchema(GpNode $node, Product $product, array $neighbors): array
    {
        $related = [];
        foreach (array_merge($neighbors['fait_partie_de'] ?? [], $neighbors['contient'] ?? []) as $n) {
            $related[] = [
                '@type' => 'Thing',
                'name' => $n['titre'],
                'url' => $n['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => url('/n/'.$node->slug.'/p/'.$product->id),
            'name' => $product->title,
            'image' => url($product->image ?: $node->hero),
            'description' => $product->summary ?: $node->summary,
            'brand' => ['@type' => 'Brand', 'name' => $node->title],
            'offers' => [
                '@type' => 'Offer',
                'priceCurrency' => 'EUR',
                'price' => $product->priceAmount(),
                'availability' => 'https://schema.org/InStock',
                'url' => url('/n/'.$node->slug.'/p/'.$product->id),
            ],
            'isRelatedTo' => $related,
        ];
    }

    private static function hotspots(array $fields): array
    {
        $out = [];
        $i = 0;
        foreach ($fields as $k => $f) {
            if (trim((string) ($f->value ?? '')) === '') {
                continue;
            }
            $out[] = [
                'name' => $f->name,
                'value' => $f->value,
                'top' => 18 + ($i * 22) % 60,
                'right' => $i % 2 ? 18 : 58,
            ];
            $i++;
            if ($i >= 3) {
                break;
            }
        }

        return $out;
    }
}
