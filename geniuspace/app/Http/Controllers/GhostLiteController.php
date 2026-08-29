<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Ghost;
use App\Support\GhostHost;
use App\Support\GhostLite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Chat public. Cycle visiteur. Le Core reste sur GhostController (staff). */
class GhostLiteController extends Controller
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
        $out = GhostLite::reply($node, $data['message'], $data['history'] ?? []);
        $out['citations'] = Ghost::publicCitations($out['citations'] ?? [], $node);

        return response()->json(GhostHost::publicSurface($out, $node));
    }

    public function hello(string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $out = GhostLite::reply($node, 'bonjour');
        $out['citations'] = Ghost::publicCitations($out['citations'] ?? [], $node);

        return response()->json(GhostHost::publicSurface($out, $node));
    }
}
