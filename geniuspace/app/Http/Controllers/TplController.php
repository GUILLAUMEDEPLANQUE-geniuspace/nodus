<?php

namespace App\Http\Controllers;

use App\Support\WorldTemplates;
use Illuminate\Http\Response;

/** Miniature SVG unique par template — le fan voit la peau avant de choisir. */
class TplController extends Controller
{
    public function svg(string $id): Response
    {
        $t = WorldTemplates::get($id);
        abort_unless($t, 404);
        $c = $t['primary'];
        $label = htmlspecialchars($t['label'], ENT_XML1);
        $schema = htmlspecialchars($t['schema'], ENT_XML1);
        $kind = $t['schema'];
        $motif = match (true) {
            str_contains($kind, 'Job') || str_contains($kind, 'Occupation') => '<rect x="40" y="70" width="70" height="50" rx="6" fill="#fff" opacity=".12"/><rect x="125" y="70" width="70" height="50" rx="6" fill="#fff" opacity=".12"/><rect x="210" y="70" width="70" height="50" rx="6" fill="#fff" opacity=".12"/>',
            str_contains($kind, 'Video') || str_contains($kind, 'TV') => '<polygon points="150,55 190,80 150,105" fill="#fff" opacity=".7"/><rect x="36" y="48" width="248" height="78" rx="10" fill="none" stroke="#fff" opacity=".25"/>',
            str_contains($kind, 'Offer') || str_contains($kind, 'Product') || str_contains($kind, 'Store') => '<circle cx="88" cy="90" r="28" fill="#fff" opacity=".15"/><rect x="130" y="68" width="140" height="12" rx="3" fill="#fff" opacity=".35"/><rect x="130" y="88" width="90" height="8" rx="3" fill="#fff" opacity=".2"/>',
            str_contains($kind, 'Place') || str_contains($kind, 'Country') || str_contains($kind, 'Event') => '<circle cx="160" cy="88" r="22" fill="#fff" opacity=".2"/><path d="M160 58c18 0 32 14 32 32 0 24-32 48-32 48s-32-24-32-48c0-18 14-32 32-32z" fill="#fff" opacity=".35"/>',
            default => '<rect x="36" y="58" width="248" height="10" rx="4" fill="#fff" opacity=".35"/><rect x="36" y="78" width="180" height="8" rx="4" fill="#fff" opacity=".18"/><rect x="36" y="96" width="210" height="8" rx="4" fill="#fff" opacity=".12"/>',
        };
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 180" width="320" height="180">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$c}"/><stop offset="1" stop-color="#0c0d12"/></linearGradient></defs>
  <rect width="320" height="180" fill="url(#g)"/>
  <rect x="0" y="0" width="320" height="180" fill="#000" opacity=".25"/>
  {$motif}
  <text x="20" y="158" fill="#fff" font-size="16" font-family="Georgia,serif">{$label}</text>
  <text x="20" y="28" fill="#fff" opacity=".7" font-size="10" font-family="system-ui" letter-spacing="1.4">{$schema}</text>
</svg>
SVG;
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
