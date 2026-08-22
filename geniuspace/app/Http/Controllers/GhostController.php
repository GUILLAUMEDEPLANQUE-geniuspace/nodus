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
            'editor_context' => 'nullable|array',
            'editor_context.selected_block' => 'nullable|string',
            'editor_context.cursor' => 'nullable|array',
            'editor_context.cursor.block' => 'nullable|string',
            'editor_context.cursor.position' => 'nullable|in:before,after',
        ]);

        $out = Ghost::reply($node, $data['message'], $data['history'] ?? [], [
            'editor_context' => $data['editor_context'] ?? [],
        ]);
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

    public function editor(string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $blocks = \App\Support\GhostEdit::read($node);

        return response()->json(\App\Support\GhostManifest::of($node, $blocks) + ['blocks' => $blocks]);
    }

    public function plan(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'message' => 'required|string|max:800',
            'editor_context' => 'nullable|array',
            'editor_context.selected_block' => 'nullable|string',
            'editor_context.cursor.block' => 'nullable|string',
            'editor_context.cursor.position' => 'nullable|in:before,after',
        ]);
        $ctx = $data['editor_context'] ?? [];
        $biz = \App\Support\GhostBiz::route($data['message']);
        $action = $biz
            ? \App\Support\GhostBiz::plan($data['message'])
            : \App\Support\GhostEdit::parse($data['message'], \App\Support\GhostEdit::read($node), $ctx);
        \App\Support\GhostEdit::store($node, $action);

        return response()->json([
            'action' => $action,
            'manifest' => \App\Support\GhostManifest::of($node),
        ]);
    }

    public function apply(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        \App\Support\Acl::guard($node->id, 'admin');
        $id = $request->validate(['id' => 'required|string'])['id'];
        $action = \App\Support\GhostEdit::load($id);
        abort_unless($action, 404);
        abort_unless(($action['status'] ?? '') === 'preview', 422, 'Ce plan n’est plus applicable.');
        $op = $action['action'] ?? '';
        abort_unless(\App\Support\GhostAction::may($op) || \App\Support\GhostAction::may($action['ops'][0]['op'] ?? $op), 403, 'Refusé.');
        $done = \App\Support\GhostEdit::commit($node, $action);

        return response()->json(['action' => $done, 'blocks' => \App\Support\GhostEdit::read($node)]);
    }

    public function undo(Request $request, string $slug): JsonResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        \App\Support\Acl::guard($node->id, 'admin');
        $id = $request->validate(['id' => 'required|string'])['id'];
        $action = \App\Support\GhostEdit::load($id);
        abort_unless($action, 404);
        $done = \App\Support\GhostEdit::revert($node, $action);

        return response()->json(['action' => $done, 'blocks' => \App\Support\GhostEdit::read($node)]);
    }
}
