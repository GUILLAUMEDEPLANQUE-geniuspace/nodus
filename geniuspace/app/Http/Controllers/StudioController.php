<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Acl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudioController extends Controller
{
    public function show(string $slug): View
    {
        $node = Acl::nodeOfSlug($slug);
        $tabs = DB::table('node_tabs')->where('node_id', $node->id)->orderBy('sort')->get();
        $cck = DB::table('cck_fields')->where('node_id', $node->id)->orderBy('sort')->get();
        $seo = DB::table('node_seo')->where('node_id', $node->id)->first();
        $steps = DB::table('ats_steps')->where('node_id', $node->id)->orderBy('step')->get();
        $staff = DB::table('node_staff')->where('node_id', $node->id)->get();
        $pending = DB::table('replies')->where('pending', 1)->get();
        $bans = DB::table('forum_bans')->where('node_id', $node->id)->get();
        $cats = DB::table('forum_categories')->where('node_id', $node->id)->get();
        $role = Acl::role($node->id);
        $products = $node->products;
        $splits = DB::table('product_splits')->get()->groupBy('product_id');
        return view('studio', compact('node', 'tabs', 'cck', 'seo', 'steps', 'staff', 'pending', 'bans', 'cats', 'role', 'products', 'splits'));
    }

    public function tab(Request $request, string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'admin') || Acl::atLeast($node->id, 'mod'), 403);
        $data = $request->validate(['key' => 'required', 'label' => 'required', 'icon' => 'nullable']);
        DB::table('node_tabs')->insert([
            'node_id' => $node->id,
            'key' => $data['key'],
            'label' => $data['label'],
            'icon' => $data['icon'] ?? 'spark',
            'sort' => 99,
        ]);
        return back()->with('ok', 'Onglet ajouté.');
    }

    public function cck(Request $request, string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'admin'), 403);
        $data = $request->validate([
            'name' => 'required',
            'type' => 'required',
            'value' => 'nullable',
            'target_kind' => 'nullable',
        ]);
        DB::table('cck_fields')->insert([
            'node_id' => $node->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'value' => $data['value'] ?? '',
            'target_kind' => $data['target_kind'] ?? 'node',
            'target_id' => '',
            'sort' => 0,
            'options' => '',
            'seo_title' => $data['name'],
        ]);
        return back()->with('ok', 'Champ créé.');
    }

    public function cckUpdate(Request $request, string $slug, int $id): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'admin'), 403);
        $data = $request->validate(['name' => 'required', 'value' => 'nullable']);
        DB::table('cck_fields')->where('node_id', $node->id)->where('id', $id)->update([
            'name' => $data['name'],
            'value' => $data['value'] ?? '',
        ]);
        return back()->with('ok', 'Champ enregistré.');
    }

    public function cckDelete(Request $request, string $slug, int $id): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'admin'), 403);
        DB::table('cck_fields')->where('node_id', $node->id)->where('id', $id)->delete();
        return back()->with('ok', 'Champ retiré.');
    }

    public function seo(Request $request, string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'admin'), 403);
        $data = $request->validate([
            'title' => 'nullable|string',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
        ]);
        DB::table('node_seo')->updateOrInsert(['node_id' => $node->id], [
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'keywords' => $data['keywords'] ?? '',
            'noindex' => $request->boolean('noindex'),
        ]);
        return back()->with('ok', 'SEO enregistré (owner/admin).');
    }

    public function ats(Request $request, string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'admin'), 403);
        $title = $request->validate(['title' => 'required|string'])['title'];
        $n = DB::table('ats_steps')->where('node_id', $node->id)->count();
        DB::table('ats_steps')->insert([
            'node_id' => $node->id,
            'step' => $n + 1,
            'title' => $title,
            'prompt' => $request->string('prompt')->toString(),
        ]);
        return back()->with('ok', 'Étape ATS ajoutée.');
    }

    public function seoCompile(string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        Acl::guard($node->id, 'admin');
        \App\Llm\SeoCompiler::compile($node);
        return back()->with('ok', 'SEO compilé (title, keywords enfants, JSON-LD, maillage).');
    }

    public function weave(string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        $bodies = DB::table('threads')->where('node_id', $node->id)->pluck('body')->implode(' ');
        $words = array_count_values(array_filter(preg_split('/\W+/u', mb_strtolower($bodies)), fn ($w) => mb_strlen($w) > 5));
        arsort($words);
        $top = array_slice(array_keys($words), 0, 5);
        foreach ($top as $w) {
            DB::table('wiki_pages')->insert([
                'node_id' => $node->id,
                'title' => ucfirst($w),
                'body' => 'Page née de l’éclatement sémantique des sujets forum (« '.$w.' »).',
            ]);
        }
        return back()->with('ok', 'Maillage wiki généré (heuristique LLM).');
    }

    public function ban(Request $request, string $slug): RedirectResponse
    {
        $node = Acl::nodeOfSlug($slug);
        abort_unless(Acl::atLeast($node->id, 'mod'), 403);
        DB::table('forum_bans')->insert([
            'node_id' => $node->id,
            'author' => $request->validate(['author' => 'required'])['author'],
            'reason' => $request->string('reason')->toString(),
        ]);
        return back()->with('ok', 'Ban enregistré.');
    }

    public function approve(Request $request, string $slug): RedirectResponse
    {
        abort_unless(Acl::atLeast(Acl::nodeOfSlug($slug)->id, 'mod'), 403);
        DB::table('replies')->where('id', $request->integer('id'))->update(['pending' => 0]);
        return back()->with('ok', 'Réponse publiée.');
    }
}
