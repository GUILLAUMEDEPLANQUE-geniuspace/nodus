<?php

namespace App\Http\Controllers;

use App\Models\CrowdGoal;
use App\Models\Edge;
use App\Models\GpNode;
use App\Models\Product;
use App\Support\SignedMedia;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniverseController extends Controller
{
    public function home(): View
    {
        $featured = GpNode::query()->where('featured', true)->get();
        return view('home', compact('featured'));
    }

    public function explore(): View
    {
        $nodes = GpNode::query()->orderBy('title')->get();
        return view('explore', compact('nodes'));
    }

    public function show(Request $request, string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $node->load(['products', 'media', 'threads', 'wiki', 'quests', 'translations']);
        $node->localized($request->cookie('locale', 'fr'));
        $childIds = Edge::query()->where('from_id', $node->id)->pluck('to_id');
        $children = GpNode::query()->whereIn('id', $childIds)->get();
        $parentIds = Edge::query()->where('to_id', $node->id)->pluck('from_id');
        $parents = GpNode::query()->whereIn('id', $parentIds)->get();
        $goal = CrowdGoal::query()->find($node->id);
        return view('universe', compact('node', 'children', 'parents', 'goal'));
    }

    public function thread(string $slug, string $tid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $thread = $node->threads()->where('id', $tid)->firstOrFail();
        return view('thread', compact('node', 'thread'));
    }

    public function product(string $slug, string $pid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $product = Product::query()->where('id', $pid)->where('node_id', $node->id)->firstOrFail();
        $products = $node->products;
        $media = $node->media->first();
        $src = $media ? SignedMedia::sign($media->path) : '/media/atelier.mp4';
        $goal = CrowdGoal::query()->find($node->id);
        return view('command-center', compact('node', 'product', 'products', 'media', 'src', 'goal'));
    }

    public function video(string $slug, int $vid): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $media = $node->media()->where('id', $vid)->firstOrFail();
        $src = SignedMedia::sign($media->path);
        return view('video', compact('node', 'media', 'src'));
    }
}
