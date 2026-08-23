<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\WorldTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CreateController extends Controller
{
    public function form(): View
    {
        $groups = WorldTemplates::groups();
        $count = count(WorldTemplates::all());
        return view('create', compact('groups', 'count'));
    }

    public function store(Request $request): RedirectResponse
    {
        \App\Support\Acl::mustUser();
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'kind' => 'nullable|string',
            'skin' => 'nullable|in:living,vera',
            'summary' => 'nullable|string',
            'template' => 'nullable|string|max:40',
        ]);
        $tpl = $data['template'] ?? '';
        $t = $tpl ? WorldTemplates::get($tpl) : null;
        $base = Str::slug($data['title']) ?: 'club';
        $slug = $base;
        $n = 2;
        while (GpNode::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }
        $id = substr(md5($slug.microtime()), 0, 12);
        $node = GpNode::query()->create([
            'id' => $id,
            'slug' => $slug,
            'kind' => $t['kind'] ?? ($data['kind'] ?? 'series'),
            'title' => $data['title'],
            'subtitle' => $t['pitch'] ?? '',
            'summary' => $data['summary'] ?? '',
            'body' => '',
            'hero' => $t['hero'] ?? '/realms/sea-hero.jpg',
            'skin' => $t['skin'] ?? ($data['skin'] ?? 'living'),
            'template' => $tpl,
            'featured' => false,
        ]);
        if ($t) {
            WorldTemplates::apply($node, $tpl);
        } else {
            $tabs = [['vivre', 'Accueil'], ['forum', 'Forum'], ['journal', 'Magazine'], ['personnages', 'Fiches'], ['videos', 'Vidéos']];
            foreach ($tabs as $i => $row) {
                DB::table('node_tabs')->insert(['node_id' => $id, 'key' => $row[0], 'label' => $row[1], 'icon' => 'spark', 'sort' => $i, 'enabled' => 1]);
            }
            \App\Support\Chrome::ensure($id);
        }
        if (Auth::id()) {
            DB::table('node_staff')->insert(['node_id' => $id, 'user_id' => Auth::id(), 'role' => 'owner']);
        }
        return redirect('/n/'.$slug.'/monde')->with('ok', $t ? ('Template « '.$t['label'].' » posé. Habillage ouvert.') : 'Le lieu est né. Habillage ouvert.');
    }
}
