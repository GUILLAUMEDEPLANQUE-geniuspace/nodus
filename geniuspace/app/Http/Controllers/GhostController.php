<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Ghost;
use App\Support\GhostGym;
use App\Support\GhostLearn;
use App\Support\GhostMaturity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    public function gym(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();

        return view('ghost-gym', [
            'node' => $node,
            'tasks' => GhostGym::tasks(),
            'maturity' => GhostMaturity::of($node),
            'run' => null,
        ]);
    }

    public function gymRun(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $run = GhostGym::run($node);

        return view('ghost-gym', [
            'node' => $node,
            'tasks' => GhostGym::tasks(),
            'maturity' => $run['maturity'],
            'run' => $run,
        ]);
    }

    public function maturity(string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();

        return response()->json(GhostMaturity::of($node));
    }

    public function approveSkill(Request $request, string $slug): JsonResponse
    {
        GpNode::query()->where('slug', $slug)->firstOrFail();
        $name = $request->validate(['name' => 'required|string|max:80'])['name'];

        return response()->json(['ok' => GhostLearn::approve($name), 'name' => $name]);
    }
}
