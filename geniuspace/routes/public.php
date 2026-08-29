<?php

use App\Http\Controllers\GhostLiteController;
use App\Models\GpNode;
use App\Support\RoomSchema;
use App\Support\SeoCompiler;
use Illuminate\Support\Facades\Route;

Route::get('/n/{slug}/ghost', [GhostLiteController::class, 'hello']);
Route::post('/n/{slug}/ghost', [GhostLiteController::class, 'chat']);

Route::get('/n/{slug}/sitemap.xml', function (string $slug) {
    $n = GpNode::query()->where('slug', $slug)->firstOrFail();

    return response(SeoCompiler::sitemapXml($n), 200, ['Content-Type' => 'application/xml']);
});

Route::get('/n/{slug}/llms.txt', function (string $slug) {
    $n = GpNode::query()->where('slug', $slug)->firstOrFail();

    return response(SeoCompiler::llmsTxt($n), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
});

Route::get('/n/{slug}/schema.json', function (string $slug) {
    $n = GpNode::query()->where('slug', $slug)->firstOrFail();
    $cluster = SeoCompiler::worldGraph($n);
    $base = RoomSchema::document($n);
    $graph = array_merge($base['@graph'] ?? [], $cluster['@graph'] ?? []);

    return response()->json([
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
});
