<?php

namespace App\Http\Controllers;

use App\Models\CrowdGoal;
use App\Models\DriveFile;
use App\Models\Edge;
use App\Models\GpNode;
use App\Models\GuildMessage;
use App\Models\LiveMessage;
use App\Models\Product;
use App\Models\Reply;
use App\Support\RoomSchema;
use App\Support\SignedMedia;
use App\Support\Spoiler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UniverseController extends Controller
{
    public function home(): View
    {
        $featured = GpNode::query()->where('featured', true)->get();
        $hot = \Illuminate\Support\Facades\DB::table('visits')
            ->select('node_id', \Illuminate\Support\Facades\DB::raw('count(*) as c'))
            ->groupBy('node_id')
            ->orderByDesc('c')
            ->limit(4)
            ->pluck('node_id');
        $reco = $hot->isNotEmpty()
            ? GpNode::query()->whereIn('id', $hot)->get()
            : $featured;
        return view('home', compact('featured', 'reco'));
    }

    public function explore(): View
    {
        $nodes = GpNode::query()->orderBy('title')->get();
        return view('explore', compact('nodes'));
    }

    public function show(Request $request, string $slug): View
    {
        return $this->page($request, $slug, $request->query('tab'));
    }

    public function room(Request $request, string $slug, string $salle): View
    {
        return $this->page($request, $slug, $salle);
    }

    private function page(Request $request, string $slug, ?string $tab): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['products', 'media', 'threads', 'wiki', 'quests', 'translations']);
        $node->localized($request->cookie('locale', 'fr'));
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $parentIds = Edge::query()->where('to_id', $node->id)->pluck('from_id');
        $parents = GpNode::query()->whereIn('id', $parentIds)->get();
        $goal = CrowdGoal::query()->find($node->id);
        $tids = $node->threads->pluck('id');
        $replies = Reply::query()->whereIn('thread_id', $tids)->orderByDesc('votes')->get()->groupBy('thread_id');
        $live = LiveMessage::query()->whereIn('thread_id', $tids)->get()->groupBy('thread_id');
        $files = DriveFile::query()->where('node_id', $node->id)->get();
        $guild = GuildMessage::query()->where('node_id', $node->id)->get();
        $default = $node->skin === 'vera' ? 'maison' : 'vivre';
        $tab = $tab ?: $default;
        $tid = $request->query('tid');
        $mode = $request->query('mode', 'legacy');
        $cck = \Illuminate\Support\Facades\DB::table('cck_fields')->where('node_id', $node->id)->get();
        $seoRow = \Illuminate\Support\Facades\DB::table('node_seo')->where('node_id', $node->id)->first();
        $tabs = \Illuminate\Support\Facades\DB::table('node_tabs')->where('node_id', $node->id)->orderBy('sort')->get();
        $children = Spoiler::filterNodes($children, $node->id);
        $node->setRelation('products', $node->products->filter(fn ($p) => Spoiler::ok((int) ($p->appear_order ?? 0), $node->id))->values());
        $node->setRelation('threads', $node->threads->filter(fn ($t) => Spoiler::ok((int) ($t->appear_order ?? 0), $node->id))->values());
        $arcs = DB::table('node_arcs')->where('node_id', $node->id)->orderBy('ord')->get();
        if ($arcs->isEmpty()) {
            $steps = DB::table('ats_steps')->where('node_id', $node->id)->orderBy('step')->get();
            $arcs = $steps->map(fn ($s) => (object) ['ord' => $s->step, 'label' => 'Étape '.$s->step.' · '.$s->title]);
        }
        $cursor = Spoiler::cursor($node->id);
        $openBounties = DB::table('bounties')->where('node_id', $node->id)->where('status', 'open')->count();
        $tabMeta = $tabs->firstWhere('key', $tab);
        $white = $request->is('w/*');
        \Illuminate\Support\Facades\DB::table('visits')->insert([
            'node_id' => $node->id,
            'path' => $request->path(),
            'session' => substr($request->session()->getId(), 0, 16),
        ]);
        return view('universe', compact(
            'node', 'children', 'parents', 'goal', 'replies', 'live', 'files', 'guild', 'tab', 'tid', 'mode', 'cck', 'seoRow', 'tabs', 'white', 'arcs', 'cursor', 'openBounties', 'tabMeta'
        ));
    }

    public function thread(string $slug, string $tid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $thread = $node->threads()->where('id', $tid)->firstOrFail();
        $replies = Reply::query()->where('thread_id', $tid)->orderByDesc('votes')->get();
        $live = LiveMessage::query()->where('thread_id', $tid)->get();
        return view('thread', compact('node', 'thread', 'replies', 'live'));
    }

    public function product(string $slug, string $pid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $product = Product::query()->where('id', $pid)->where('node_id', $node->id)->firstOrFail();
        $products = $node->products;
        $media = $node->media->first();
        $src = $media ? SignedMedia::url($media) : '/media/atelier.mp4';
        $goal = CrowdGoal::query()->find($node->id);
        return view('command-center', compact('node', 'product', 'products', 'media', 'src', 'goal'));
    }

    public function fiche(string $slug, string $fiche): View
    {
        $club = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node = GpNode::query()->where('slug', $fiche)->firstOrFail();
        abort_unless(Edge::query()->where('from_id', $club->id)->where('to_id', $node->id)->exists(), 404);
        abort_unless(Spoiler::ok((int) ($node->appear_order ?? 0), $club->id), 403, 'Spoiler. Recule le curseur d’arc.');
        $cck = \Illuminate\Support\Facades\DB::table('cck_fields')->where('node_id', $node->id)->get();
        $parentIds = Edge::query()->where('to_id', $node->id)->pluck('from_id');
        $parents = GpNode::query()->whereIn('id', $parentIds)->get();
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $sibs = GpNode::query()->whereIn('id', Edge::query()->where('from_id', $club->id)->pluck('to_id'))->get();
        return view('fiche', compact('club', 'node', 'cck', 'parents', 'children', 'sibs'));
    }

    public function video(string $slug, string $vid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['products', 'media']);
        $media = $node->media->first(fn ($m) => (string) $m->id === $vid || \Illuminate\Support\Str::slug($m->title) === $vid);
        abort_unless($media, 404);
        $src = SignedMedia::url($media);
        $related = $node->media->where('id', '!=', $media->id);
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $files = DriveFile::query()->where('node_id', $node->id)->get();
        return view('video', compact('node', 'media', 'src', 'related', 'children', 'files'));
    }

    public function guide(string $slug, string $wid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $pages = $node->wiki;
        $page = $pages->first(fn ($w) => (string) $w->id === $wid || \Illuminate\Support\Str::slug($w->title) === $wid);
        abort_unless($page, 404);
        return view('guide', compact('node', 'page'));
    }
}
