<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Acl;
use App\Support\RoomCatalog;
use App\Support\SignedMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AtelierController extends Controller
{
    public function show(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $chosen = DB::table('node_tabs')->where('node_id', $node->id)->get()->keyBy('key');
        return view('atelier', [
            'node' => $node,
            'groups' => RoomCatalog::groups(),
            'chosen' => $chosen,
            'packs' => RoomCatalog::packs(),
        ]);
    }

    public function save(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'summary' => 'nullable|string|max:400',
            'rooms' => 'array',
            'rooms.*' => 'string',
            'style' => 'array',
            'cover' => 'nullable|image|max:8192',
        ]);
        $node->title = $data['title'];
        $node->summary = $data['summary'] ?? '';
        if ($node->kind === 'boutique_expert' && $node->skin === 'living') {
            $node->kind = 'boutique_expert';
        }
        if ($request->file('cover')) {
            $node->hero = '/'.SignedMedia::storeUpload($request->file('cover'));
        }
        $node->save();
        $catalog = RoomCatalog::all();
        $keys = array_values(array_unique(array_merge(['vivre'], $data['rooms'] ?? ['forum'])));
        DB::table('node_tabs')->where('node_id', $node->id)->delete();
        foreach ($keys as $i => $key) {
            $st = $data['style'][$key] ?? [];
            $bg = '';
            if ($request->file('bg_'.$key)) {
                $bg = '/'.SignedMedia::storeUpload($request->file('bg_'.$key));
            }
            $label = $key === 'vivre' ? 'Accueil' : RoomCatalog::label($key);
            DB::table('node_tabs')->insert([
                'node_id' => $node->id,
                'key' => $key,
                'label' => $label,
                'icon' => 'spark',
                'sort' => $i,
                'color' => $st['color'] ?? '#c9a36a',
                'bg' => $bg,
                'animate' => ! empty($st['animate']),
                'seo_title' => $st['seo_title'] ?? ($label.' — '.$node->title),
                'seo_desc' => $st['seo_desc'] ?? ($catalog[$key]['hint'] ?? ''),
            ]);
        }
        return redirect('/n/'.$node->slug)->with('ok', 'Club habillé. Chaque salle a sa peau.');
    }
}
