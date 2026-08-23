<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Edge;
use App\Models\GpNode;
use App\Support\Acl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Magazine du Node : articles + vidéos, template chef-de-secteur. */
class MagazineController extends Controller
{
    public function index(Request $request, string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $articles = Article::query()->where('node_id', $node->id)->orderByDesc('published_at')->get();
        $themes = $articles->groupBy('theme');
        $tabs = DB::table('node_tabs')->where('node_id', $node->id)->orderBy('sort')->get();
        $tab = 'journal';
        $node->load('media');
        return view('magazine.index', compact('node', 'articles', 'themes', 'tabs', 'tab'));
    }

    public function show(string $slug, string $aid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $article = Article::query()->where('node_id', $node->id)
            ->where(fn ($q) => $q->where('slug', $aid)->orWhere('id', $aid))
            ->firstOrFail();
        $article->increment('views');
        $related = Article::query()->where('node_id', $node->id)->where('id', '!=', $article->id)->where('theme', $article->theme)->limit(4)->get();
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $cluster = GpNode::query()->whereIn('id', $childIds)->limit(8)->get();
        $tabs = DB::table('node_tabs')->where('node_id', $node->id)->orderBy('sort')->get();
        $tab = 'journal';
        return view('magazine.show', compact('node', 'article', 'related', 'cluster', 'tabs', 'tab'));
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id, 'admin');
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'resume' => 'required|string|max:800',
            'body' => 'required|string|max:20000',
            'theme' => 'required|string|max:40',
            'definition_term' => 'nullable|string|max:80',
            'definition' => 'nullable|string|max:600',
            'faq' => 'nullable|string|max:4000',
            'longtail' => 'nullable|string|max:2000',
            'toc' => 'nullable|string|max:1000',
            'video_path' => 'nullable|string|max:200',
        ]);
        $s = Str::slug($data['title']);
        $id = 'art-'.substr(md5($s.microtime()), 0, 10);
        Article::query()->create([
            'id' => $id,
            'node_id' => $node->id,
            'slug' => $s,
            'title' => $data['title'],
            'theme' => $data['theme'],
            'dossier' => 'Dossier club',
            'resume' => $data['resume'],
            'body' => $data['body'],
            'definition_term' => $data['definition_term'] ?? '',
            'definition' => $data['definition'] ?? '',
            'toc' => $data['toc'] ?? '',
            'longtail' => $data['longtail'] ?? '',
            'faq' => $data['faq'] ?? '',
            'cover' => $node->hero,
            'video_path' => $data['video_path'] ?? '',
            'author' => Auth::user()->name ?? 'Membre',
            'author_role' => 'Membre',
            'reading_min' => max(4, (int) ceil(str_word_count($data['body']) / 180)),
            'views' => 1,
            'published_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect('/n/'.$node->slug.'/blog/'.$s)->with('ok', 'Article publié — URL indexable.');
    }
}
