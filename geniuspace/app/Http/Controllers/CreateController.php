<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\ElementCatalog;
use App\Support\RoomCatalog;
use App\Support\WorldGerms;
use App\Support\WorldGermsFinance;
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
        $groups = WorldGermsFinance::groupsWith(WorldTemplates::groups());
        $count = count(WorldTemplates::all()) + count(WorldGermsFinance::asTemplates());
        $germs = WorldGerms::all();
        $library = [
            'rooms' => RoomCatalog::all(),
            'entities' => ElementCatalog::entities(),
            'slots' => ElementCatalog::slots(),
            'presets' => collect(WorldGerms::IDS)->mapWithKeys(fn ($id) => [$id => ElementCatalog::packPreset($id)])->all(),
        ];

        return view('create', compact('groups', 'count', 'germs', 'library'));
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
            'rooms' => 'nullable|array',
            'rooms.*' => 'string|max:40',
            'entities' => 'nullable|array',
            'entities.*' => 'string|max:40',
        ]);
        $tpl = $data['template'] ?? '';
        $t = $tpl ? (WorldTemplates::get($tpl) ?? WorldGermsFinance::template($tpl)) : null;
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
        $opts = [
            'rooms' => $data['rooms'] ?? null,
            'entities' => $data['entities'] ?? null,
        ];
        if ($t && WorldTemplates::get($tpl)) {
            WorldTemplates::apply($node, $tpl);
            \App\Support\WorldApply::shape($node, $opts);
        } elseif ($t) {
            WorldTemplates::apply($node, $tpl);
            $node->template = $tpl;
            $node->kind = $t['kind'];
            $node->skin = $t['skin'];
            $node->hero = $t['hero'];
            $node->subtitle = $t['pitch'];
            $node->save();
            DB::table('node_tabs')->where('node_id', $node->id)->delete();
            $rooms = $t['rooms'];
            if (! in_array('carnet', $rooms, true)) {
                $rooms[] = 'carnet';
            }
            foreach ($rooms as $i => $key) {
                $meta = RoomCatalog::all()[$key] ?? ['label' => $key];
                DB::table('node_tabs')->insert([
                    'node_id' => $node->id,
                    'key' => $key,
                    'label' => $meta['label'] ?? $key,
                    'icon' => 'spark',
                    'sort' => $i,
                    'color' => $t['primary'] ?? '#334155',
                    'enabled' => 1,
                ]);
            }
            $pack = match ($tpl) {
                'crypto-onchain' => 'protocole',
                'immo-agence' => 'bien',
                'banque-fintech' => 'actif',
                'cabinet-droit' => 'dossier',
                default => null,
            };
            if ($pack) {
                \App\Support\FieldTemplates::apply($node->id, $pack);
            }
            \App\Support\Chrome::applyPreset($node, \App\Support\Chrome::presetFromTemplate($tpl) ?? 'living');
            \App\Support\WorldApply::shape($node, $opts);
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

        return redirect('/n/'.$slug.'/monde')->with('ok', $t
            ? ('Template « '.$t['label'].' » posé. Les salles vides restent hors index. L’hôte lira seulement ce coffre.')
            : 'Le lieu est né. Habillage ouvert.');
    }
}
