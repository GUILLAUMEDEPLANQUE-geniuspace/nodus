<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
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
        return view('create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'kind' => 'required|string',
            'skin' => 'required|in:living,vera',
            'summary' => 'nullable|string',
        ]);
        $slug = Str::slug($data['title']);
        $id = substr(md5($slug.microtime()), 0, 12);
        GpNode::query()->create([
            'id' => $id,
            'slug' => $slug,
            'kind' => $data['kind'],
            'title' => $data['title'],
            'subtitle' => '',
            'summary' => $data['summary'] ?? '',
            'body' => '',
            'hero' => $data['skin'] === 'vera' ? '/realms/studio-hero.jpg' : '/realms/sea-hero.jpg',
            'skin' => $data['skin'],
            'featured' => false,
        ]);
        $tabs = $data['skin'] === 'vera'
            ? [['maison','Maison'],['salon','Salon'],['offres','Offres'],['epreuve','Quêtes'],['forum','Forum'],['videos','Vidéos'],['drive','Drive']]
            : [['vivre','Univers'],['personnages','Personnages'],['forum','Forum'],['journal','Journal'],['guilde','Guilde'],['guides','Guides'],['boutique','Boutique'],['videos','Vidéos'],['reliques','Drive']];
        foreach ($tabs as $i => $t) {
            DB::table('node_tabs')->insert(['node_id' => $id, 'key' => $t[0], 'label' => $t[1], 'icon' => 'spark', 'sort' => $i]);
        }
        if (Auth::id()) {
            DB::table('node_staff')->insert(['node_id' => $id, 'user_id' => Auth::id(), 'role' => 'owner']);
        }
        return redirect('/builder/'.$slug.'?new=1')->with('ok', 'Univers créé. Sculptez-le dans le God Canvas.');
    }
}
