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
use App\Support\SignedMedia;
use Illuminate\Http\Request;
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
        $white = $request->is('w/*');
        \Illuminate\Support\Facades\DB::table('visits')->insert([
            'node_id' => $node->id,
            'path' => $request->path(),
            'session' => substr($request->session()->getId(), 0, 16),
        ]);
        return view('universe', compact(
            'node', 'children', 'parents', 'goal', 'replies', 'live', 'files', 'guild', 'tab', 'tid', 'mode', 'cck', 'seoRow', 'tabs', 'white'
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

    public function video(string $slug, int $vid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['products', 'media']);
        $media = $node->media()->where('id', $vid)->firstOrFail();
        $src = SignedMedia::url($media);
        $related = $node->media->where('id', '!=', $media->id);
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $files = DriveFile::query()->where('node_id', $node->id)->get();
        return view('video', compact('node', 'media', 'src', 'related', 'children', 'files'));
    }
}
