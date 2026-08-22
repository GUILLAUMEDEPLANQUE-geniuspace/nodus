<?php

namespace App\Support;

use App\Models\GpNode;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Options d’achat (taille, gravure, extra, logo).
 * Ce sont des champs audience=commande — pas une colonne SQL.
 */
class Order
{
    /** @return list<object> */
    public static function fields(string $productId): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('cck_fields', 'audience')) {
            return [];
        }
        $q = DB::table('cck_fields')->where('audience', 'commande')->orderBy('sort');
        $direct = (clone $q)->where('target_kind', 'product')->where('target_id', $productId)->get();
        if ($direct->isNotEmpty()) {
            return $direct->all();
        }
        $p = Product::query()->find($productId);
        if (! $p) {
            return [];
        }
        $piece = GpNode::query()->where('kind', 'product')->where('title', $p->title)->first();
        if (! $piece) {
            return [];
        }

        return $q->where('node_id', $piece->id)->get()->all();
    }

    /** Parse "S|M|L" ou "Sans|+0|Oui:+5". */
    public static function choices(object $field): array
    {
        $raw = trim((string) ($field->options ?? ''));
        if ($raw === '') {
            return [];
        }
        $out = [];
        foreach (explode('|', $raw) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $extra = 0;
            $label = $part;
            if (preg_match('/^(.+?)\s*:\s*\+?\s*(\d+)\s*€?$/u', $part, $m)) {
                $label = trim($m[1]);
                $extra = (int) $m[2];
            } elseif (preg_match('/^\+?(\d+)$/', $part, $m)) {
                $label = $part;
                $extra = (int) $m[1];
            }
            $out[] = ['label' => $label, 'extra' => $extra, 'raw' => $part];
        }

        return $out;
    }

    public static function extraFor(object $field, string $choice): int
    {
        foreach (self::choices($field) as $c) {
            if ($c['label'] === $choice || $c['raw'] === $choice) {
                return (int) $c['extra'];
            }
        }
        if (($field->type ?? '') === 'select') {
            return 0;
        }

        return (int) ($field->min_val ?? 0);
    }

    /**
     * Lit le POST, calcule le supplément, stocke les fichiers.
     *
     * @return array{options: array<string,string>, extra_cents: int, files: array<string,string>}
     */
    public static function capture(Request $request, string $productId): array
    {
        $options = [];
        $files = [];
        $extra = 0;
        foreach (self::fields($productId) as $f) {
            $key = $f->field_key ?: \Illuminate\Support\Str::slug($f->name);
            if (($f->type ?? '') === 'file' || ($f->type ?? '') === 'image') {
                if ($request->hasFile('options.'.$key) || $request->hasFile('opt_'.$key)) {
                    $up = $request->file('options.'.$key) ?: $request->file('opt_'.$key);
                    $path = SignedMedia::storeUpload($up, true);
                    $files[$key] = $path;
                    $options[$key] = $up->getClientOriginalName();
                }
                continue;
            }
            $val = (string) $request->input('options.'.$key, $request->input('opt_'.$key, ''));
            if ($val === '') {
                continue;
            }
            $options[$key] = $val;
            $extra += self::extraFor($f, $val);
        }

        return ['options' => $options, 'extra_cents' => $extra * 100, 'files' => $files];
    }

    public static function priceLabel(Product $p, int $extraCents): string
    {
        if ($extraCents <= 0) {
            return $p->price;
        }
        $base = (float) $p->priceAmount();
        $sum = $base + ($extraCents / 100);

        return rtrim(rtrim(number_format($sum, 2, ',', ' '), '0'), ',').' €';
    }
}
