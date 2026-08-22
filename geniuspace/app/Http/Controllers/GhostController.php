<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Ghost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Ghost : un agent par lieu, ancré sur le moteur.
 */
class GhostController extends Controller
{
    public function chat(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'message' => 'required|string|max:800',
            'history' => 'nullable|array|max:12',
            'history.*.role' => 'required_with:history|in:user,assistant',
            'history.*.content' => 'required_with:history|string|max:2000',
        ]);

        $out = Ghost::reply($node, $data['message'], $data['history'] ?? []);
        $out['lieu'] = ['titre' => $node->title, 'slug' => $node->slug];

        return response()->json($out);
    }

    public function context(string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'profile' => Ghost::profile($node),
            'system' => Ghost::systemPrompt($node),
            'context' => Ghost::context($node),
        ]);
    }

    public function hello(string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();

        return response()->json(Ghost::reply($node, 'bonjour'));
    }
}
