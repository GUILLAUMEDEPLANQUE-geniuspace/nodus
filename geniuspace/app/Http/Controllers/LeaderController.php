<?php

namespace App\Http\Controllers;

use App\Models\Edge;
use App\Models\GpNode;
use App\Models\Product;
use App\Support\Acl;
use App\Support\Chapters;
use App\Support\RoomCatalog;
use App\Support\Searcher;
use App\Support\SeoRadar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaderController extends Controller
{
    public function sitemap(string $slug): Response
    {
        $club = GpNode::query()->where('slug', $slug)->firstOrFail();
        $urls = [url('/n/'.$slug)];
        foreach (DB::table('node_tabs')->where('node_id', $club->id)->get() as $t) {
            if ($t->key !== 'vivre') {
                $urls[] = url('/n/'.$slug.'/'.$t->key);
            }
        }
        $ids = Edge::query()->where('from_id', $club->id)->pluck('to_id');
        foreach (GpNode::query()->whereIn('id', $ids)->get() as $n) {
            $urls[] = url('/n/'.$n->slug);
        }
        foreach (DB::table('threads')->where('node_id', $club->id)->get() as $th) {
            $urls[] = url('/n/'.$slug.'/t/'.$th->id);
        }
        foreach (DB::table('products')->where('node_id', $club->id)->get() as $p) {
            $urls[] = url('/n/'.$slug.'/p/'.$p->id);
        }
        foreach (DB::table('media')->where('node_id', $club->id)->get() as $m) {
            $urls[] = url('/n/'.$slug.'/v/'.$m->id);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (array_unique($urls) as $u) {
            $xml .= '<url><loc>'.e($u).'</loc></url>';
        }
        $xml .= '</urlset>';
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function radar(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $issues = SeoRadar::run($node);
        return view('radar', compact('node', 'issues'));
    }

    public function digest(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $tids = DB::table('threads')->where('node_id', $node->id)->pluck('id');
        $best = DB::table('replies')->whereIn('thread_id', $tids)->orderByDesc('votes')->limit(12)->get();
        return view('digest', compact('node', 'best'));
    }

    public function digestStore(string $slug)
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id, 'mod');
        $tids = DB::table('threads')->where('node_id', $node->id)->pluck('id');
        $best = DB::table('replies')->whereIn('thread_id', $tids)->orderByDesc('votes')->limit(8)->get();
        $body = $best->map(fn ($r) => '## '.$r->author."\n".$r->body)->implode("\n\n");
        $id = 'dg-'.substr(md5($slug.now()), 0, 8);
        DB::table('threads')->insert([
            'id' => $id,
            'node_id' => $node->id,
            'kind' => 'blog',
            'title' => 'Digest Legacy — '.now()->toDateString(),
            'body' => $body ?: 'Rien à élever cette semaine.',
            'cover' => $node->hero,
            'author' => 'Legacy',
        ]);
        return redirect('/n/'.$slug.'/t/'.$id)->with('ok', 'Digest publié (indexable).');
    }

    public function compare(string $slug, string $a, string $b): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $left = GpNode::query()->where('slug', $a)->firstOrFail();
        $right = GpNode::query()->where('slug', $b)->firstOrFail();
        $cckA = DB::table('cck_fields')->where('node_id', $left->id)->get();
        $cckB = DB::table('cck_fields')->where('node_id', $right->id)->get();
        return view('compare', compact('node', 'left', 'right', 'cckA', 'cckB'));
    }

    public function search(Request $request, string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $q = $request->string('q')->toString();
        $hits = Searcher::inClub($node, $q);
        return view('club-search', compact('node', 'q', 'hits'));
    }

    public function graph(Request $request, string $slug)
    {
        $n = GpNode::query()->where('slug', $slug)->firstOrFail();
        $out = Edge::query()->where('from_id', $n->id)->get();
        $in = Edge::query()->where('to_id', $n->id)->get();
        $ids = $out->pluck('to_id')->merge($in->pluck('from_id'))->unique()->push($n->id);
        $nodes = GpNode::query()->whereIn('id', $ids)->get()->keyBy('id');
        $payload = [
            'id' => $n->id,
            'slug' => $n->slug,
            'title' => $n->title,
            'kind' => $n->kind,
            'url' => url('/n/'.$n->slug),
            'parents' => $in->map(fn ($e) => ['kind' => $e->kind, 'label' => $e->label, 'node' => $nodes[$e->from_id]->only(['slug', 'title', 'kind']) ?? null])->values(),
            'children' => $out->map(fn ($e) => ['kind' => $e->kind, 'label' => $e->label, 'node' => $nodes[$e->to_id]->only(['slug', 'title', 'kind']) ?? null])->values(),
        ];
        if ($request->wantsJson() || $request->is('g/*.json') || str_ends_with($request->path(), '.json')) {
            return response()->json($payload);
        }
        return view('graph', ['node' => $n, 'payload' => $payload]);
    }

    public function white(string $slug)
    {
        return app(UniverseController::class)->show(request(), $slug);
    }

    public function host(Request $request)
    {
        $host = $request->validate(['host' => 'required|alpha_dash|max:40'])['host'];
        $slug = $request->validate(['slug' => 'required'])['slug'];
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id, 'owner');
        $node->host = $host;
        $node->save();
        return back()->with('ok', $host.'.geniuspace.com — DNS A/CNAME vers ce serveur.');
    }
}
