<?php

namespace App\Http\Controllers;

use App\Models\Edge;
use App\Models\GpNode;
use App\Support\Acl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * God Canvas — la 3D n'est qu'une peau.
 * Chaque mesh = un nœud / un type CCK / une arête parent_enfant.
 */
class BuilderController extends Controller
{
    public function show(string $slug = 'one-piece'): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        return view('builder', compact('node'));
    }

    public function state(string $slug): JsonResponse
    {
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $spatial = DB::table('spatial_nodes')->where('universe_id', $uni->id)->get();
        if ($spatial->isEmpty()) {
            DB::table('spatial_nodes')->insert([
                'universe_id' => $uni->id,
                'node_id' => $uni->id,
                'kind' => 'core',
                'x' => 0, 'y' => 0, 'z' => 0,
                'radius' => 0, 'angle' => 0, 'speed' => 0,
            ]);
            $spatial = DB::table('spatial_nodes')->where('universe_id', $uni->id)->get();
        }
        $ids = $spatial->pluck('node_id');
        $nodes = GpNode::query()->whereIn('id', $ids)->get()->keyBy('id');
        $payload = $spatial->map(function ($s) use ($nodes) {
            $n = $nodes[$s->node_id] ?? null;
            return [
                'sid' => $s->id,
                'id' => $s->node_id,
                'kind' => $s->kind,
                'title' => $n->title ?? $s->kind,
                'slug' => $n->slug ?? '',
                'x' => (float) $s->x, 'y' => (float) $s->y, 'z' => (float) $s->z,
                'radius' => (float) $s->radius,
                'angle' => (float) $s->angle,
                'speed' => (float) $s->speed,
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
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $prompt = $request->validate(['prompt' => 'required|string|max:500'])['prompt'];
        $skin = str_contains(mb_strtolower($prompt), 'job') || str_contains(mb_strtolower($prompt), 'recrut')
            ? 'vera'
            : 'living';
        $uni->summary = $prompt;
        $uni->skin = $skin;
        $uni->save();
        DB::table('cck_fields')->insert([
            'node_id' => $uni->id,
            'name' => 'Prompt Big Bang',
            'type' => 'rich',
            'value' => $prompt,
            'target_kind' => 'node',
            'target_id' => '',
            'sort' => 0,
        ]);
        return $this->state($slug);
    }

    public function add(Request $request, string $slug): JsonResponse
    {
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $type = $request->validate(['type' => 'required|string'])['type'];
        $map = [
            'job' => ['kind' => 'job', 'title' => 'Offre / Quête'],
            'crypto' => ['kind' => 'product', 'title' => 'Actif RWA'],
            'video' => ['kind' => 'work', 'title' => 'Holo-Fiche'],
            'character' => ['kind' => 'character', 'title' => 'Personnage'],
            'shop' => ['kind' => 'product', 'title' => 'Produit boutique'],
        ];
        $meta = $map[$type] ?? ['kind' => 'concept', 'title' => 'Nœud'];
        $id = substr(md5($type.microtime()), 0, 12);
        $nslug = Str::slug($meta['title']).'-'.substr($id, 0, 4);
        GpNode::query()->create([
            'id' => $id,
            'slug' => $nslug,
            'kind' => $meta['kind'],
            'title' => $meta['title'],
            'subtitle' => $type,
            'summary' => 'Nœud sculpté dans le God Canvas.',
            'body' => '',
            'hero' => $uni->hero,
            'skin' => $uni->skin,
            'featured' => false,
        ]);
        Edge::query()->create(['from_id' => $uni->id, 'to_id' => $id, 'kind' => 'parent_of', 'label' => $type]);
        $angle = (float) ($request->input('angle', mt_rand(0, 628) / 100));
        $radius = (float) ($request->input('radius', 50 + mt_rand(0, 40)));
        DB::table('spatial_nodes')->insert([
            'universe_id' => $uni->id,
            'node_id' => $id,
            'kind' => $type,
            'x' => cos($angle) * $radius,
            'y' => (mt_rand(-10, 10)),
            'z' => sin($angle) * $radius,
            'radius' => $radius,
            'angle' => $angle,
            'speed' => 0.002 + mt_rand(0, 5) / 1000,
        ]);
        if (Auth::id()) {
            DB::table('node_staff')->insertOrIgnore(['node_id' => $id, 'user_id' => Auth::id(), 'role' => 'owner']);
        }
        return $this->state($slug);
    }

    public function link(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate(['from' => 'required|string', 'to' => 'required|string']);
        Edge::query()->create([
            'from_id' => $data['from'],
            'to_id' => $data['to'],
            'kind' => 'parent_of',
            'label' => 'rayon',
        ]);
        return $this->state($slug);
    }

    public function sync(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'id' => 'required|string',
            'title' => 'nullable|string',
            'summary' => 'nullable|string',
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
        if (! empty($data['field_name'])) {
            DB::table('cck_fields')->insert([
                'node_id' => $n->id,
                'name' => $data['field_name'],
                'type' => 'text',
                'value' => $data['field_value'] ?? '',
                'target_kind' => 'node',
                'target_id' => '',
                'sort' => 0,
            ]);
        }
        return $this->state($slug);
    }
}
