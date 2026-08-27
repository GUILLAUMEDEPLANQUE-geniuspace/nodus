<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\Media;
use App\Support\Engine;
use App\Support\Grantor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Déblocage réel : une ligne en base, pas granted=true en JS.
 * L’épreuve tenue, l’achat, le drop d’un chapitre : même coffre.
 */
class GrantController extends Controller
{
    public function unlock(Request $request, string $slug, string $vid): JsonResponse
    {
        $media = $this->media($slug, $vid);
        abort_unless(Grantor::mayUnlock($media), 403, 'Preuve ou achat requis.');
        $reason = Grantor::isShop($media) ? 'purchase' : Grantor::reasonFor($media);

        return response()->json(Grantor::unlockMedia($media, $reason));
    }

    public function drop(Request $request, string $slug, string $vid): JsonResponse
    {
        $media = $this->media($slug, $vid);
        abort_unless(Grantor::canSeeMedia($media), 403, 'Il faut ouvrir la vidéo avant.');
        $at = (int) $request->input('at', 0);
        $door = Grantor::doorAt($media, $at, 'drop');
        abort_unless($door, 403, 'Cette relique n’est pas disponible.');

        return response()->json(Grantor::drop($media, $door));
    }

    public function omni(Request $request, string $slug, string $vid): JsonResponse
    {
        $media = $this->media($slug, $vid);
        abort_unless(Grantor::canSeeMedia($media) || ! Grantor::isGated($media), 403, 'Cadre hors vidéo ouverte.');
        $kind = (string) $request->input('kind', 'labo');
        $label = (string) $request->input('label', 'Cadre tenu');

        return response()->json(Grantor::claimOmni($media, $kind, $label, (int) $request->input('at', 0)));
    }

    public function visit(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'target' => 'nullable|string|max:80',
            'label' => 'nullable|string|max:120',
        ]);

        return response()->json(Grantor::visit($node->id, (string) ($data['target'] ?? ''), (string) ($data['label'] ?? '')));
    }

    public function carnet(Request $request, string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $mine = Grantor::mine($node->id);
        $world = Grantor::mine();
        $graph = $node->slug === 'vera' ? Engine::passport('carnet-karim') : ['titre' => 'Carnet', 'preuves' => [], 'details' => []];

        return view('carnet', [
            'node' => $node,
            'tab' => 'carnet',
            'mine' => $mine,
            'world' => $world,
            'carnet' => $graph,
            'chrome' => \App\Support\Chrome::bag($node),
        ]);
    }

    public function export(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $payload = Grantor::export($node->id);
        $payload['lieu'] = ['titre' => $node->title, 'url' => url(Engine::href($node))];
        if ($node->slug === 'vera') {
            $payload['carnet_maison'] = Engine::passport('carnet-karim');
        }

        return response()->json($payload)
            ->header('Content-Disposition', 'attachment; filename="carnet-'.$node->slug.'.json"');
    }

    private function media(string $slug, string $vid): Media
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $media = $node->media->first(fn ($m) => (string) $m->id === $vid || \Illuminate\Support\Str::slug($m->title) === $vid);
        abort_unless($media, 404);

        return $media;
    }
}
