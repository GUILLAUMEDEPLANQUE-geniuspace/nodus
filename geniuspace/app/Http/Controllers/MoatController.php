<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\Product;
use App\Models\User;
use App\Support\Acl;
use App\Support\LlmJudge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MoatController extends Controller
{
    public function bounties(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $rows = DB::table('bounties')->where('node_id', $node->id)->orderByDesc('id')->get();
        $open = $rows->where('status', 'open')->count();
        return view('bounties', compact('node', 'rows', 'open'));
    }

    public function openBounty(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $d = $request->validate([
            'keyword' => 'required|string|max:120',
            'reward' => 'nullable|integer|min:50|max:50000',
        ]);
        DB::table('bounties')->insert([
            'node_id' => $node->id,
            'keyword' => $d['keyword'],
            'reward' => $d['reward'] ?? 500,
            'title_reward' => 'Expert '.$d['keyword'],
            'status' => 'open',
        ]);
        $this->pingMembers($node, 'Quête SEO : '.$d['keyword'].' — '.$node->title, '/n/'.$slug.'/bounties');
        return back()->with('ok', 'Quête de guilde ouverte. Le dock va pinger les membres.');
    }

    public function claim(string $slug, int $id): RedirectResponse
    {
        abort_unless(Auth::id(), 401);
        $b = DB::table('bounties')->where('id', $id)->firstOrFail();
        abort_unless($b->status === 'open', 403);
        DB::table('bounties')->where('id', $id)->update(['status' => 'claimed', 'claimer_id' => Auth::id()]);
        return back()->with('ok', 'Quête prise. Écris le guide (≥400 car., mot-clé, structure).');
    }

    public function submit(Request $request, string $slug, int $id): RedirectResponse
    {
        abort_unless(Auth::id(), 401);
        $b = DB::table('bounties')->where('id', $id)->firstOrFail();
        abort_unless((int) $b->claimer_id === Auth::id(), 403);
        $draft = $request->validate(['draft' => 'required|string|max:20000'])['draft'];
        $judge = LlmJudge::run($b->keyword, $draft);
        DB::table('bounties')->where('id', $id)->update([
            'draft' => $draft,
            'llm_score' => $judge['score'],
            'llm_note' => $judge['note'],
            'status' => $judge['ok'] ? 'submitted' : 'claimed',
        ]);
        return back()->with('ok', $judge['ok']
            ? 'LLM ok ('.$judge['score'].'/100). En attente admin.'
            : 'LLM refuse ('.$judge['score'].') : '.$judge['note']);
    }

    public function publish(string $slug, int $id): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $b = DB::table('bounties')->where('id', $id)->firstOrFail();
        abort_unless($b->status === 'submitted', 403);
        DB::table('wiki_pages')->insert([
            'node_id' => $node->id,
            'title' => $b->keyword,
            'body' => $b->draft,
        ]);
        DB::table('bounties')->where('id', $id)->update(['status' => 'published']);
        if ($b->claimer_id) {
            User::query()->where('id', $b->claimer_id)->increment('nodecoins', (int) $b->reward);
            DB::table('inventory')->insert([
                'user_id' => $b->claimer_id,
                'kind' => 'title',
                'node_id' => $node->id,
                'label' => $b->title_reward,
                'meta' => $b->keyword,
            ]);
            DB::table('notifications')->insert([
                'user_id' => $b->claimer_id,
                'title' => '+'.$b->reward.' NodeCoins · '.$b->title_reward,
                'url' => '/n/'.$slug.'/guides',
            ]);
        }
        return back()->with('ok', 'Guide publié (indexable) · titre et coins versés.');
    }

    public function cursor(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $c = (int) $request->validate(['cursor' => 'required|integer|min:0|max:99'])['cursor'];
        if (Auth::id()) {
            DB::table('user_cursors')->updateOrInsert(
                ['user_id' => Auth::id(), 'node_id' => $node->id],
                ['cursor' => $c]
            );
        }
        session(['gp_cursor_'.$node->id => $c]);
        return back()->with('ok', 'Curseur : tu en es à l’arc '.$c.'. Le graphe se recroqueville.');
    }

    public function mentions(Request $request, string $slug)
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $q = mb_strtolower($request->string('q')->toString());
        $ids = DB::table('edges')->where('from_id', $node->id)->pluck('to_id');
        $fiches = GpNode::query()->whereIn('id', $ids)->get()
            ->filter(fn ($n) => $q === '' || str_contains(mb_strtolower($n->title.' '.$n->slug), $q))
            ->take(8)
            ->map(fn ($n) => ['kind' => 'fiche', 'slug' => $n->slug, 'title' => $n->title, 'href' => '/n/'.$slug.'/f/'.$n->slug]);
        $prods = Product::query()->where('node_id', $node->id)->get()
            ->filter(fn ($p) => $q === '' || str_contains(mb_strtolower($p->title), $q))
            ->take(8)
            ->map(fn ($p) => ['kind' => 'pièce', 'slug' => $p->id, 'title' => $p->title.' · '.$p->price, 'href' => '/n/'.$slug.'/p/'.$p->id]);
        return response()->json($fiches->concat($prods)->values());
    }

    public function split(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $d = $request->validate([
            'product_id' => 'required|string',
            'email' => 'required|email',
            'percent' => 'required|integer|min:1|max:99',
        ]);
        $u = User::query()->where('email', $d['email'])->firstOrFail();
        $sum = (int) DB::table('product_splits')->where('product_id', $d['product_id'])->sum('percent');
        abort_if($sum + $d['percent'] > 100, 422, 'Split > 100 %');
        DB::table('product_splits')->insert([
            'product_id' => $d['product_id'],
            'user_id' => $u->id,
            'percent' => $d['percent'],
        ]);
        return back()->with('ok', 'Split '.$d['percent'].' % → '.$u->name);
    }

    private function pingMembers(GpNode $node, string $title, string $url): void
    {
        $ids = DB::table('node_staff')->where('node_id', $node->id)->pluck('user_id');
        foreach ($ids as $uid) {
            DB::table('notifications')->insert(['user_id' => $uid, 'title' => $title, 'url' => $url]);
        }
    }
}
