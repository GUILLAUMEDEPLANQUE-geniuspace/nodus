<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\User;
use App\Support\Acl;
use App\Support\Chrome;
use App\Support\FieldTemplates;
use App\Support\RoomCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Éditeur de monde (Structure / Design / Action / Motion).
 * Custom = identité, scène, navigation, boutons, fiches, droits.
 * Figé = panier, unlock, grants, paiement.
 */
class WorldEditorController extends Controller
{
    public function show(Request $request, string $slug): View
    {
        $node = Acl::nodeOfSlug($slug);
        Acl::guard($node->id, 'mod');
        Chrome::ensure($node->id);
        $mode = $request->query('mode', 'design');
        if (! in_array($mode, ['structure', 'design', 'action', 'motion'], true)) {
            $mode = 'design';
        }
        $theme = Chrome::theme($node->id);
        $actions = Chrome::allActions($node->id);
        $layers = Chrome::allLayers($node->id);
        $tabs = DB::table('node_tabs')->where('node_id', $node->id)->orderBy('sort')->get();
        $staff = DB::table('node_staff')->where('node_id', $node->id)->get();
        $users = $staff->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $staff->pluck('user_id'))->get()->keyBy('id');
        $childIds = DB::table('edges')->where('from_id', $node->id)->pluck('to_id');
        $children = $childIds->isEmpty() ? collect() : GpNode::query()->whereIn('id', $childIds)->get();
        $seo = DB::table('node_seo')->where('node_id', $node->id)->first();
        $cck = DB::table('cck_fields')->where('node_id', $node->id)->orderBy('sort')->get();
        $role = Acl::role($node->id);
        $can = Acl::canWrite($node->id, 'admin') || Acl::canWrite($node->id, 'mod');
        $chrome = Chrome::bag($node);
        $checklist = $this->checklist($node, $theme, $tabs, $actions, $children, $staff, $seo);
        $rooms = RoomCatalog::all();
        $templates = FieldTemplates::all();
        $flagship = $node->slug === 'vera';

        return view('world-editor', compact(
            'node', 'mode', 'theme', 'actions', 'layers', 'tabs', 'staff', 'users',
            'children', 'seo', 'cck', 'role', 'can', 'chrome', 'checklist', 'rooms',
            'templates', 'flagship'
        ));
    }

    public function theme(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate([
            'primary' => 'nullable|string|max:20',
            'bg' => 'nullable|string|max:20',
            'fg' => 'nullable|string|max:20',
            'muted' => 'nullable|string|max:20',
            'logo' => 'nullable|string|max:240',
            'favicon' => 'nullable|string|max:240',
            'hero' => 'nullable|string|max:240',
            'hero_video' => 'nullable|string|max:240',
            'poster' => 'nullable|string|max:240',
            'skin' => 'nullable|in:living,vera',
            'dock' => 'nullable|in:bottom,top,side',
            'display_font' => 'nullable|string|max:80',
        ]);
        Chrome::ensure($node->id);
        $row = Chrome::theme($node->id);
        DB::table('node_theme')->updateOrInsert(['node_id' => $node->id], [
            'primary' => $data['primary'] ?: $row->primary,
            'bg' => $data['bg'] ?: $row->bg,
            'fg' => $data['fg'] ?: $row->fg,
            'muted' => $data['muted'] ?: $row->muted,
            'logo' => $data['logo'] ?? $row->logo,
            'favicon' => $data['favicon'] ?? $row->favicon,
            'hero' => $data['hero'] ?? $row->hero,
            'hero_video' => $data['hero_video'] ?? $row->hero_video,
            'poster' => $data['poster'] ?? $row->poster,
            'skin' => $data['skin'] ?? $row->skin,
            'dock' => $data['dock'] ?? $row->dock,
            'display_font' => $data['display_font'] ?? $row->display_font,
        ]);
        if (! empty($data['hero'])) {
            $node->hero = $data['hero'];
        }
        if (! empty($data['skin'])) {
            $node->skin = $data['skin'];
        }
        $node->save();

        return $this->back($slug, $request, 'Identité enregistrée.');
    }

    public function action(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate([
            'id' => 'required|integer',
            'label' => 'required|string|max:80',
            'variant' => 'nullable|in:primary,ghost,line,icon-only',
            'href' => 'nullable|string|max:240',
            'enabled' => 'nullable',
            'paywall_title' => 'nullable|string|max:120',
            'paywall_body' => 'nullable|string|max:400',
        ]);
        $row = DB::table('node_actions')->where('node_id', $node->id)->where('id', $data['id'])->first();
        abort_unless($row, 404);
        $extra = Chrome::extra($row);
        if ($request->filled('paywall_title')) {
            $extra['paywall_title'] = $data['paywall_title'];
        }
        if ($request->filled('paywall_body')) {
            $extra['paywall_body'] = $data['paywall_body'];
        }
        DB::table('node_actions')->where('id', $row->id)->update([
            'label' => $data['label'],
            'variant' => $data['variant'] ?? $row->variant,
            'href' => $data['href'] ?? $row->href,
            'enabled' => $request->boolean('enabled') ? 1 : 0,
            'extra_json' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : '',
        ]);

        return $this->back($slug, $request, 'Bouton enregistré.');
    }

    public function actionMove(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $id = $request->integer('id');
        $dir = $request->string('dir')->toString() === 'up' ? -1 : 1;
        $row = DB::table('node_actions')->where('node_id', $node->id)->where('id', $id)->first();
        abort_unless($row, 404);
        $swap = DB::table('node_actions')
            ->where('node_id', $node->id)
            ->where('scope', $row->scope)
            ->where('sort', $dir < 0 ? '<' : '>', $row->sort)
            ->orderBy('sort', $dir < 0 ? 'desc' : 'asc')
            ->first();
        if ($swap) {
            DB::table('node_actions')->where('id', $row->id)->update(['sort' => $swap->sort]);
            DB::table('node_actions')->where('id', $swap->id)->update(['sort' => $row->sort]);
        }

        return $this->back($slug, $request, 'Ordre des boutons mis à jour.');
    }

    public function layer(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate([
            'id' => 'nullable|integer',
            'kind' => 'required|in:image,video,text,button,shape',
            'label' => 'nullable|string|max:80',
            'src' => 'nullable|string|max:240',
            'body' => 'nullable|string|max:400',
            'x' => 'nullable|numeric',
            'y' => 'nullable|numeric',
            'w' => 'nullable|numeric',
            'h' => 'nullable|numeric',
            'z' => 'nullable|integer',
            'opacity' => 'nullable|numeric',
            'action_key' => 'nullable|string|max:40',
            'action_target' => 'nullable|string|max:240',
            'motion' => 'nullable|in:none,fade,slide',
            'delay_ms' => 'nullable|integer|min:0|max:4000',
            'visible' => 'nullable',
            'tab' => 'nullable|string|max:40',
        ]);
        $payload = [
            'kind' => $data['kind'],
            'label' => $data['label'] ?? '',
            'src' => $data['src'] ?? '',
            'body' => $data['body'] ?? '',
            'x' => $data['x'] ?? 10,
            'y' => $data['y'] ?? 10,
            'w' => $data['w'] ?? 30,
            'h' => $data['h'] ?? 12,
            'z' => $data['z'] ?? 1,
            'opacity' => $data['opacity'] ?? 1,
            'action_key' => $data['action_key'] ?? '',
            'action_target' => $data['action_target'] ?? '',
            'motion' => $data['motion'] ?? 'fade',
            'delay_ms' => $data['delay_ms'] ?? 0,
            'visible' => $request->boolean('visible') ? 1 : 0,
            'tab' => $data['tab'] ?? '',
        ];
        if (! empty($data['id'])) {
            $existing = DB::table('node_scene_layers')->where('node_id', $node->id)->where('id', $data['id'])->first();
            abort_unless($existing, 404);
            if (! $request->exists('visible')) {
                $payload['visible'] = $existing->visible;
            }
            DB::table('node_scene_layers')->where('id', $data['id'])->update($payload);
        } else {
            $payload['node_id'] = $node->id;
            $payload['locked'] = 0;
            $payload['visible'] = 1;
            DB::table('node_scene_layers')->insert($payload);
        }

        return $this->back($slug, $request, 'Calque enregistré.');
    }

    public function layerDelete(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        DB::table('node_scene_layers')->where('node_id', $node->id)->where('id', $request->integer('id'))->delete();

        return $this->back($slug, $request, 'Calque retiré.');
    }

    public function tab(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate([
            'id' => 'required|integer',
            'label' => 'required|string|max:60',
            'icon' => 'nullable|string|max:40',
            'enabled' => 'nullable',
        ]);
        DB::table('node_tabs')->where('node_id', $node->id)->where('id', $data['id'])->update([
            'label' => $data['label'],
            'icon' => $data['icon'] ?? 'spark',
            'enabled' => $request->boolean('enabled') ? 1 : 0,
        ]);

        return $this->back($slug, $request, 'Salle enregistrée.');
    }

    public function tabMove(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $id = $request->integer('id');
        $dir = $request->string('dir')->toString() === 'up' ? -1 : 1;
        $row = DB::table('node_tabs')->where('node_id', $node->id)->where('id', $id)->first();
        abort_unless($row, 404);
        $swap = DB::table('node_tabs')
            ->where('node_id', $node->id)
            ->where('sort', $dir < 0 ? '<' : '>', $row->sort)
            ->orderBy('sort', $dir < 0 ? 'desc' : 'asc')
            ->first();
        if ($swap) {
            DB::table('node_tabs')->where('id', $row->id)->update(['sort' => $swap->sort]);
            DB::table('node_tabs')->where('id', $swap->id)->update(['sort' => $row->sort]);
        }

        return $this->back($slug, $request, 'Plan du lieu mis à jour.');
    }

    public function tabAdd(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate(['key' => 'required|string|max:40', 'label' => 'required|string|max:60']);
        $sort = (int) DB::table('node_tabs')->where('node_id', $node->id)->max('sort') + 1;
        DB::table('node_tabs')->insert([
            'node_id' => $node->id,
            'key' => $data['key'],
            'label' => $data['label'],
            'icon' => 'spark',
            'sort' => $sort,
            'enabled' => 1,
        ]);

        return $this->back($slug, $request, 'Salle ajoutée.');
    }

    public function child(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'kind' => 'required|in:person,job,product,company',
            'summary' => 'nullable|string|max:400',
        ]);
        $base = Str::slug($data['title']) ?: 'fiche';
        $childSlug = $base;
        $n = 2;
        while (GpNode::query()->where('slug', $childSlug)->exists()) {
            $childSlug = $base.'-'.$n++;
        }
        $id = substr(md5($childSlug.microtime()), 0, 12);
        GpNode::query()->create([
            'id' => $id,
            'slug' => $childSlug,
            'kind' => $data['kind'],
            'title' => $data['title'],
            'subtitle' => '',
            'summary' => $data['summary'] ?? '',
            'body' => '',
            'hero' => $node->hero,
            'skin' => $node->skin,
            'featured' => false,
        ]);
        $label = match ($data['kind']) {
            'job' => 'Offre',
            'person' => 'Personne',
            'product' => 'Œuvre',
            default => 'Fiche',
        };
        DB::table('edges')->insert(['from_id' => $node->id, 'to_id' => $id, 'kind' => 'parent_of', 'label' => $label]);
        $pack = match ($data['kind']) {
            'job' => 'offre-tech',
            'person' => 'personnage',
            'product' => 'produit',
            'company' => 'maison',
            default => null,
        };
        if ($pack) {
            FieldTemplates::apply($id, $pack);
        }

        return $this->back($slug, $request, 'Fiche « '.$data['title'].' » liée. Renseignez les détails.');
    }

    public function staff(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug, 'owner');
        $data = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:80',
            'role' => 'required|in:admin,mod',
        ]);
        $user = User::query()->where('email', $data['email'])->first();
        if (! $user) {
            $user = User::query()->create([
                'name' => $data['name'] ?: Str::before($data['email'], '@'),
                'email' => $data['email'],
                'password' => Hash::make(Str::random(16)),
            ]);
        }
        DB::table('node_staff')->updateOrInsert(
            ['node_id' => $node->id, 'user_id' => $user->id],
            ['role' => $data['role']]
        );

        return $this->back($slug, $request, $user->name.' est '.$data['role'].' de ce lieu.');
    }

    public function seo(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $data = $request->validate([
            'title' => 'nullable|string|max:80',
            'description' => 'nullable|string|max:180',
        ]);
        DB::table('node_seo')->updateOrInsert(['node_id' => $node->id], [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
        ]);

        return $this->back($slug, $request, 'Fiche Google enregistrée.');
    }

    public function preset(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $key = $request->validate(['preset' => 'required|in:living,merch,vera'])['preset'];
        Chrome::applyPreset($node, $key);

        return $this->back($slug, $request, 'Apparence « '.$key.' » posée. Vous pouvez tout retoucher.');
    }

    public function pack(Request $request, string $slug): RedirectResponse
    {
        $node = $this->guard($slug);
        $key = $request->validate(['template' => 'required|string'])['template'];
        $n = FieldTemplates::apply($node->id, $key);
        $label = FieldTemplates::get($key)['label'] ?? $key;

        return $this->back($slug, $request, $n ? $n.' détails « '.$label.' » posés.' : 'Ce modèle est déjà posé.');
    }

    private function guard(string $slug, string $min = 'admin'): GpNode
    {
        $node = Acl::nodeOfSlug($slug);
        Acl::guard($node->id, $min);

        return $node;
    }

    private function back(string $slug, Request $request, string $ok): RedirectResponse
    {
        $mode = $request->input('mode', $request->query('mode', 'design'));

        return redirect('/n/'.$slug.'/monde?mode='.$mode)->with('ok', $ok);
    }

    private function checklist(GpNode $node, object $theme, $tabs, $actions, $children, $staff, $seo): array
    {
        $heroN = $actions->where('scope', 'hero')->where('enabled', 1)->count();
        $shopN = $actions->where('scope', 'shop_card')->where('enabled', 1)->count();
        $pw = $actions->firstWhere('scope', 'player_paywall');
        $extra = $pw ? Chrome::extra($pw) : [];

        return [
            ['ok' => (bool) ($theme->primary && ($theme->hero || $theme->hero_video || $theme->logo)), 'label' => 'Identité — couleur, fond, logo'],
            ['ok' => $tabs->count() > 0, 'label' => 'Plan — salles nommées, certaines masquées'],
            ['ok' => $heroN >= 1, 'label' => 'Entrée — boutons du hero'],
            ['ok' => $children->count() > 0, 'label' => 'Fiches — un personnage, une offre ou une œuvre'],
            ['ok' => (bool) ($extra['paywall_title'] ?? false), 'label' => 'Player — texte du paywall'],
            ['ok' => $shopN >= 1, 'label' => 'Boutique — boutons des cartes'],
            ['ok' => (bool) ($seo->title ?? false), 'label' => 'Google — titre et description'],
            ['ok' => $staff->count() > 0, 'label' => 'Équipe — au moins un gardien'],
        ];
    }
}
