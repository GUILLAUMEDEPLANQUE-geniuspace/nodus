<?php

namespace App\Http\Controllers;

use App\Llm\CckCatalog;
use App\Llm\Toolbelt;
use App\Llm\WorldCompiler;
use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BuilderController extends Controller
{
    public function show(Request $request, string $slug = 'one-piece'): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $count = DB::table('spatial_nodes')->where('universe_id', $node->id)->count();
        $fresh = $request->boolean('new') || $count <= 1;
        $catalog = CckCatalog::all();
        $tools = Toolbelt::schema();
        return view('builder', compact('node', 'fresh', 'catalog', 'tools'));
    }

    public function state(string $slug): JsonResponse
    {
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $spatial = DB::table('spatial_nodes')->where('universe_id', $uni->id)->get();
        if ($spatial->isEmpty()) {
            DB::table('spatial_nodes')->insert([
                'universe_id' => $uni->id, 'node_id' => $uni->id, 'kind' => 'core',
                'x' => 0, 'y' => 0, 'z' => 0, 'radius' => 0, 'angle' => 0, 'speed' => 0,
            ]);
            $spatial = DB::table('spatial_nodes')->where('universe_id', $uni->id)->get();
        }
        $ids = $spatial->pluck('node_id');
        $nodes = GpNode::query()->whereIn('id', $ids)->get()->keyBy('id');
        $cck = DB::table('cck_fields')->whereIn('node_id', $ids)->get()->groupBy('node_id');
        $payload = $spatial->map(function ($s) use ($nodes, $cck) {
            $n = $nodes[$s->node_id] ?? null;
            return [
                'sid' => $s->id, 'id' => $s->node_id, 'kind' => $s->kind,
                'title' => $n->title ?? $s->kind, 'slug' => $n->slug ?? '',
                'x' => (float) $s->x, 'y' => (float) $s->y, 'z' => (float) $s->z,
                'radius' => (float) $s->radius, 'angle' => (float) $s->angle, 'speed' => (float) $s->speed,
                'fields' => $cck->get($s->node_id, collect())->values(),
            ];
        });
        $edges = Edge::query()->whereIn('from_id', $ids)->orWhereIn('to_id', $ids)->get(['from_id', 'to_id', 'kind', 'label']);
        return response()->json([
            'universe' => ['id' => $uni->id, 'slug' => $uni->slug, 'title' => $uni->title, 'skin' => $uni->skin, 'summary' => $uni->summary],
            'nodes' => $payload,
            'edges' => $edges,
        ]);
    }

    public function bang(Request $request, string $slug): JsonResponse
    {
        $prompt = $request->validate(['prompt' => 'required|string|max:800'])['prompt'];
        $compiled = WorldCompiler::run($slug, $prompt);
        $state = $this->state($slug)->getData(true);
        $state['compile'] = $compiled;
        return response()->json($state);
    }

    public function compile(Request $request, string $slug): JsonResponse
    {
        return $this->bang($request, $slug);
    }

    public function add(Request $request, string $slug): JsonResponse
    {
        $type = $request->validate(['type' => 'required|string'])['type'];
        $title = $request->input('title');
        Toolbelt::spawn(['slug' => $slug, 'type' => $type, 'title' => $title]);
        return $this->state($slug);
    }

    public function link(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate(['from' => 'required|string', 'to' => 'required|string']);
        Toolbelt::link($data['from'], $data['to']);
        return $this->state($slug);
    }

    public function sync(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|string',
            'title' => 'nullable|string',
            'summary' => 'nullable|string',
            'field_type' => 'nullable|string',
            'field_name' => 'nullable|string',
            'field_value' => 'nullable|string',
        ]);
        $n = GpNode::query()->findOrFail($data['id']);
        if (! empty($data['title'])) {
            $n->title = $data['title'];
        }
        if (! empty($data['summary'])) {
            $n->summary = $data['summary'];
        }
        $n->save();
        if (! empty($data['field_type']) || ! empty($data['field_name'])) {
            Toolbelt::field([
                'node_id' => $n->id,
                'type' => $data['field_type'] ?: 'text',
                'name' => $data['field_name'] ?: $data['field_type'],
                'value' => $data['field_value'] ?? '',
            ]);
        }
        Toolbelt::seo([
            'node_id' => $n->id,
            'title' => $n->title.' | Geniuspace',
            'description' => $n->summary ?: $n->title,
        ]);
        return $this->state($slug);
    }

    public function media(Request $request, string $slug): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:512000', 'node_id' => 'nullable|string']);
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $target = $request->string('node_id')->toString();
        $node = $target ? GpNode::query()->find($target) : $uni;
        $res = Toolbelt::storeUpload($node ?? $uni, $request->file('file'));
        $state = $this->state($slug)->getData(true);
        $state['upload'] = $res;
        return response()->json($state);
    }

    public function tools(): JsonResponse
    {
        return response()->json(['tools' => Toolbelt::schema()]);
    }
}
