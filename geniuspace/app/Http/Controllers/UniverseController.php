<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MagazineController;
use App\Http\Controllers\VeraController;
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

    public function explore(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $nodes = GpNode::query()->orderBy('title');
        if ($q !== '') {
            $nodes->where(fn ($w) => $w->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%'));
        }
        $nodes = $nodes->get();
        return view('explore', compact('nodes', 'q'));
    }

    public function show(Request $request, string $slug): View|\Illuminate\Http\RedirectResponse
    {
        return $this->page($request, $slug, $request->query('tab'));
    }

    public function room(Request $request, string $slug, string $salle): View|\Illuminate\Http\RedirectResponse
    {
        if (in_array($salle, ['journal', 'blog'], true)) {
            return app(MagazineController::class)->index($request, $slug);
        }
        return $this->page($request, $slug, $salle);
    }

    private function page(Request $request, string $slug, ?string $tab): View|\Illuminate\Http\RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['products', 'media', 'threads', 'wiki', 'quests', 'translations']);
        $node->localized($request->cookie('locale', 'fr'));
        if ($node->slug === 'vera') {
            return app(VeraController::class)->room($request, $node, $tab ?: 'home');
        }
        if ($node->kind === 'job' && \App\Support\VeraCatalog::job($node->slug)) {
            return redirect('/n/vera/offres/'.$node->slug);
        }
        if ($node->kind === 'company' && str_starts_with((string) $node->id, 'vc-')) {
            return redirect('/n/vera/maisons/'.preg_replace('/^maison-/', '', $node->slug));
        }
        if ($node->id === 'carnet-karim') {
            return redirect('/n/vera/carnet');
        }
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $parentIds = Edge::query()->where('to_id', $node->id)->pluck('from_id');
        $parents = GpNode::query()->whereIn('id', $parentIds)->get();
        $goal = CrowdGoal::query()->find($node->id);
        $tids = $node->threads->pluck('id');
        $replies = Reply::query()->whereIn('thread_id', $tids)->orderByDesc('votes')->get()->groupBy('thread_id');
        $live = LiveMessage::query()->whereIn('thread_id', $tids)->get()->groupBy('thread_id');
        $files = DriveFile::query()->where('node_id', $node->id)->get()
            ->filter(fn ($f) => Spoiler::ok((int) ($f->appear_order ?? 0), $node->id))
            ->values();
        $guild = GuildMessage::query()->where('node_id', $node->id)->get();
        $tabs = \Illuminate\Support\Facades\DB::table('node_tabs')->where('node_id', $node->id)->where('enabled', 1)->orderBy('sort')->get();
        $tab = $tab ?: ($tabs->first()->key ?? ($node->skin === 'vera' ? 'maison' : 'vivre'));
        if (in_array($tab, ['carnet', 'passport', 'guilde'], true)) {
            return app(GrantController::class)->carnet($request, $slug);
        }
        $tid = $request->query('tid');
        $mode = $request->query('mode', 'legacy');
        $cck = \Illuminate\Support\Facades\DB::table('cck_fields')->where('node_id', $node->id)->get();
        $seoRow = \Illuminate\Support\Facades\DB::table('node_seo')->where('node_id', $node->id)->first();
        $chrome = \App\Support\Chrome::bag($node);
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
            'node', 'children', 'parents', 'goal', 'replies', 'live', 'files', 'guild', 'tab', 'tid', 'mode', 'cck', 'seoRow', 'tabs', 'white', 'arcs', 'cursor', 'openBounties', 'tabMeta', 'chrome'
        ));
    }

    public function thread(string $slug, string $tid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $thread = $node->threads()->where('id', $tid)->firstOrFail();
        $node->load(['products', 'media']);
        $replies = Reply::query()->where('thread_id', $tid)->orderByDesc('votes')->get();
        $live = LiveMessage::query()->where('thread_id', $tid)->get();
        $files = DriveFile::query()->where('node_id', $node->id)->get();
        return view('thread', compact('node', 'thread', 'replies', 'live', 'files'));
    }

    public function product(string $slug, string $pid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $product = Product::query()->where('id', $pid)->where('node_id', $node->id)->firstOrFail();
        $products = $node->products;
        $media = $node->media->first();
        $src = $media ? SignedMedia::url($media) : '/media/atelier.mp4';
        $goal = CrowdGoal::query()->find($node->id);
        $granted = $media ? \App\Support\Grantor::canSeeMedia($media) : true;
        return view('command-center', compact('node', 'product', 'products', 'media', 'src', 'goal', 'granted'));
    }

    public function fiche(string $slug, string $fiche): View
    {
        $club = GpNode::query()->where('slug', $slug)->firstOrFail();
        if ($club->slug === 'vera') {
            return app(VeraController::class)->jobShow($fiche);
        }
        $node = GpNode::query()->where('slug', $fiche)->firstOrFail();
        abort_unless(Edge::query()->where('from_id', $club->id)->where('to_id', $node->id)->exists(), 404);
        abort_unless(Spoiler::ok((int) ($node->appear_order ?? 0), $club->id), 403, 'Spoiler. Recule le curseur d’arc.');
        $cck = \Illuminate\Support\Facades\DB::table('cck_fields')->where('node_id', $node->id)->get();
        $parentIds = Edge::query()->where('to_id', $node->id)->pluck('from_id');
        $parents = GpNode::query()->whereIn('id', $parentIds)->get();
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $sibs = GpNode::query()->whereIn('id', Edge::query()->where('from_id', $club->id)->pluck('to_id'))->get();
        $trail = \App\Support\Engine::trail($node);
        $also = \App\Support\Engine::alsoInWorld($node, 3);
        $heritage = \App\Support\Engine::inherit($node);
        return view('fiche', compact('club', 'node', 'cck', 'parents', 'children', 'sibs', 'trail', 'also', 'heritage'));
    }

    public function video(string $slug, string $vid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['products', 'media']);
        $media = $node->media->first(fn ($m) => (string) $m->id === $vid || \Illuminate\Support\Str::slug($m->title) === $vid);
        abort_unless($media, 404);
        $granted = \App\Support\Grantor::canSeeMedia($media);
        $src = SignedMedia::url($media, ! $granted);
        $related = $node->media->where('id', '!=', $media->id);
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $files = DriveFile::query()->where('node_id', $node->id)->get()
            ->filter(fn ($f) => Spoiler::ok((int) ($f->appear_order ?? 0), $node->id))
            ->values();
        $chrome = \App\Support\Chrome::bag($node);
        $doors = \App\Support\Grantor::doors($media);
        $chapters = \App\Support\Chapters::parse($media->chapters);

        return view('video', compact('node', 'media', 'src', 'related', 'children', 'files', 'chrome', 'granted', 'doors', 'chapters'));
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
