<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\GpNode;
use App\Models\Media;
use App\Support\SignedMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DriveController extends Controller
{
    public function index(Request $request): View
    {
        $slug = $request->query('slug', 'one-piece');
        $node = GpNode::query()->where('slug', $slug)->first() ?: GpNode::query()->first();
        $folderId = $request->query('folder');
        $folders = DB::table('folders')->where('node_id', $node->id)
            ->when($folderId, fn ($q) => $q->where('parent_id', $folderId), fn ($q) => $q->whereNull('parent_id'))
            ->get();
        $files = DriveFile::query()->where('node_id', $node->id)
            ->when($folderId, fn ($q) => $q->where('folder_id', $folderId), fn ($q) => $q->whereNull('folder_id'))
            ->get();
        $parent = $folderId ? DB::table('folders')->where('id', $folderId)->first() : null;
        $nodes = GpNode::query()->orderBy('title')->get();
        return view('drive', compact('node', 'folders', 'files', 'parent', 'nodes', 'slug'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:512000',
            'node_slug' => 'nullable|string',
            'folder_id' => 'nullable|integer',
        ]);
        $path = SignedMedia::storeUpload($request->file('file'));
        $slug = $request->string('node_slug')->toString() ?: 'one-piece';
        $n = GpNode::query()->where('slug', $slug)->first();
        $mime = $request->file('file')->getMimeType() ?: 'application/octet-stream';
        DriveFile::query()->create([
            'node_id' => $n->id ?? 'onepiece',
            'title' => $request->file('file')->getClientOriginalName(),
            'path' => '/'.$path,
            'kind' => str_starts_with($mime, 'image/') ? 'image' : (str_starts_with($mime, 'video/') ? 'video' : 'file'),
            'locked' => $request->boolean('locked'),
            'folder_id' => $request->integer('folder_id') ?: null,
            'mime' => $mime,
            'size' => $request->file('file')->getSize() ?: 0,
            'user_id' => Auth::id(),
        ]);
        if (str_starts_with($mime, 'video/')) {
            Media::query()->create([
                'node_id' => $n->id ?? 'onepiece',
                'title' => $request->file('file')->getClientOriginalName(),
                'path' => $path,
                'mode' => 'lore',
                'access' => $request->boolean('locked') ? 'freemium' : 'free',
                'teaser_sec' => $request->boolean('locked') ? 6 : 0,
                'price' => '',
                'duration' => '',
                'chapters' => '',
                'transcript' => 'Upload Drive',
            ]);
        }
        return back()->with('ok', 'Fichier sur le disque du serveur.');
    }

    public function folder(Request $request): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $request->string('slug'))->firstOrFail();
        DB::table('folders')->insert([
            'node_id' => $node->id,
            'parent_id' => $request->integer('parent_id') ?: null,
            'title' => $request->validate(['title' => 'required|string'])['title'],
        ]);
        return back()->with('ok', 'Dossier créé.');
    }

    public function rename(Request $request): RedirectResponse
    {
        DriveFile::query()->where('id', $request->integer('id'))->update([
            'title' => $request->validate(['title' => 'required|string'])['title'],
        ]);
        return back()->with('ok', 'Renommé.');
    }

    public function lock(Request $request): RedirectResponse
    {
        $f = DriveFile::query()->findOrFail($request->integer('id'));
        $f->locked = ! $f->locked;
        $f->save();
        return back()->with('ok', $f->locked ? 'Locké (paywall / rôle).' : 'Ouvert.');
    }
}
