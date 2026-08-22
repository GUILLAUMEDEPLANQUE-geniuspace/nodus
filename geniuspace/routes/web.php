<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UniverseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [UniverseController::class, 'home'])->name('home');
Route::get('/explore', [UniverseController::class, 'explore'])->name('explore');
Route::get('/n/{slug}', [UniverseController::class, 'show'])->name('node.show');
Route::get('/n/{slug}/t/{tid}', [UniverseController::class, 'thread'])->name('thread.show');
Route::get('/n/{slug}/p/{pid}', [UniverseController::class, 'product'])->name('product.show');
Route::get('/n/{slug}/v/{vid}', [UniverseController::class, 'video'])->name('video.show');
Route::get('/play', [MediaController::class, 'play'])->name('media.play');
Route::get('/drive', [DriveController::class, 'index'])->name('drive');
Route::post('/drive', [DriveController::class, 'store'])->name('drive.store');
Route::post('/forum', [\App\Http\Controllers\ForumController::class, 'thread']);
Route::post('/n/{slug}/t/{tid}/reply', [\App\Http\Controllers\ForumController::class, 'reply']);
Route::post('/n/{slug}/t/{tid}/live', [\App\Http\Controllers\ForumController::class, 'live']);
Route::post('/n/{slug}/guilde', [\App\Http\Controllers\ForumController::class, 'guild']);
Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::get('/sitemap.xml', function () {
    $nodes = \App\Models\GpNode::all();
    $urls = $nodes->map(fn ($n) => url('/n/'.$n->slug));
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) {
        $xml .= '<url><loc>'.e($u).'</loc></url>';
    }
    $xml .= '</urlset>';
    return response($xml, 200, ['Content-Type' => 'application/xml']);
});
