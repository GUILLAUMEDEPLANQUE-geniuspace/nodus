<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\GpNode;
use App\Models\Media;
use App\Support\Engine;
use App\Support\Grantor;
use App\Support\SignedMedia;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Widget «lieu » : teaser + une preuve + un CTA.
 * Les job boards gardent le trafic ; nous sommes la couche confiance.
 */
class EmbedController extends Controller
{
    public function show(string $slug): View|Response
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $media = Media::query()->where('node_id', $node->id)->orderBy('id')->first();
        $heritage = Engine::inherit($node);
        $src = $media ? SignedMedia::url($media, ! Grantor::canSeeMedia($media)) : '';
        $file = DriveFile::query()->where('node_id', $node->id)->where('kind', 'image')->orderBy('id')->first();
        $cta = match ($node->kind) {
            'job' => ['label' => 'Lire l’offre', 'href' => Engine::href($node)],
            'company' => ['label' => 'Voir les missions', 'href' => Engine::href($node)],
            'product', 'boutique_expert' => ['label' => 'Voir la vitrine', 'href' => '/n/'.$node->slug],
            default => ['label' => 'Entrer', 'href' => Engine::href($node)],
        };
        if ($node->slug === 'vera') {
            $cta = ['label' => 'Voir les offres', 'href' => '/n/vera/offres'];
        }
        $html = view('embed', compact('node', 'media', 'src', 'heritage', 'file', 'cta'))->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Security-Policy' => 'frame-ancestors *',
            'X-Frame-Options' => 'ALLOWALL',
        ]);
    }
}
