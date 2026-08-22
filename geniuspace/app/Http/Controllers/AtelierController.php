<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Acl;
use App\Support\SignedMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Atelier fan — 3 questions en français.
 * Le God Canvas reste derrière « mode pro ».
 */
class AtelierController extends Controller
{
    public const ROOMS = [
        'forum' => ['Parler ensemble', 'Un garage où on discute, comme un forum'],
        'personnages' => ['Les fiches', 'Voitures, persos, offres… une page chacun'],
        'videos' => ['Vidéos', 'Tes films, essais, replays'],
        'boutique' => ['Vendre / petites annonces', 'Pièces, prints, services'],
        'guides' => ['Guides', 'Tutos, wiki, astuces'],
        'reliques' => ['Photos & fichiers', 'Un Drive pour le club'],
        'journal' => ['Le journal', 'Actus du club'],
    ];

    public function show(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        return view('atelier', ['node' => $node, 'rooms' => self::ROOMS]);
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
            'cover' => 'nullable|image|max:8192',
        ]);
        $node->title = $data['title'];
        $node->summary = $data['summary'] ?? '';
        if ($request->file('cover')) {
            $path = SignedMedia::storeUpload($request->file('cover'));
            $node->hero = '/'.$path;
        }
        $node->save();
        DB::table('node_tabs')->where('node_id', $node->id)->delete();
        $chosen = array_values(array_unique(array_merge(['vivre'], $data['rooms'] ?? ['forum'])));
        $labels = ['vivre' => 'Accueil'] + array_map(fn ($r) => $r[0], self::ROOMS);
        foreach ($chosen as $i => $key) {
            DB::table('node_tabs')->insert([
                'node_id' => $node->id,
                'key' => $key,
                'label' => $labels[$key] ?? $key,
                'icon' => 'spark',
                'sort' => $i,
            ]);
        }
        return redirect('/n/'.$node->slug)->with('ok', 'C’est ouvert. Invite tes potes.');
    }
}
