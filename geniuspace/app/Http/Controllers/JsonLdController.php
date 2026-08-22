<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\RoomCatalog;
use App\Support\RoomSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Studio + endpoint public du graphe JSON-LD. */
class JsonLdController extends Controller
{
    public function document(string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['media', 'products', 'wiki']);
        return response()->json(RoomSchema::document($node), 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function room(string $slug, string $salle): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['media', 'products', 'wiki']);
        return response()->json([
            '@context' => 'https://schema.org',
            '@graph' => [RoomSchema::graph($node, $salle)],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function studio(Request $request, string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['media', 'products', 'wiki']);
        $salle = $request->query('salle', 'forum');
        $json = json_encode(RoomSchema::graph($node, $salle), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $rooms = RoomCatalog::all();
        return view('jsonld', compact('node', 'salle', 'json', 'rooms'));
    }
}
