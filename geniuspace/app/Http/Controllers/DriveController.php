<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\GpNode;
use App\Models\Media;
use App\Support\Acl;
use App\Support\Grantor;
use App\Support\SignedMedia;
use App\Support\Spoiler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DriveController extends Controller
{
    public function index(Request $request): View
    {
        $slug = $request->query('slug', 'lumen');
        $node = GpNode::query()->where('slug', $slug)->first() ?: GpNode::query()->first();
        $folderId = $request->query('folder');
        $folders = DB::table('folders')->where('node_id', $node->id)
            ->when($folderId, fn ($q) => $q->where('parent_id', $folderId), fn ($q) => $q->whereNull('parent_id'))
            ->get();
        $files = DriveFile::query()->where('node_id', $node->id)
            ->when($folderId, fn ($q) => $q->where('folder_id', $folderId), fn ($q) => $q->whereNull('folder_id'))
            ->get()
            ->filter(fn ($f) => Spoiler::ok((int) ($f->appear_order ?? 0), $node->id))
            ->values();
        $parent = $folderId ? DB::table('folders')->where('id', $folderId)->first() : null;
        $nodes = GpNode::query()->whereIn('id', ['vera', 'lumen'])->orWhere('featured', true)->orderBy('title')->get();
        $staff = Acl::atLeast($node->id, 'mod');
        $view = $request->query('view', 'grid');

        return view('drive', compact('node', 'folders', 'files', 'parent', 'nodes', 'slug', 'staff', 'view'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|max:512000',
            'node_slug' => 'nullable|string',
            'folder_id' => 'nullable|integer',
        ]);
        $slug = $request->string('node_slug')->toString() ?: 'lumen';
        $n = Acl::nodeOfSlug($slug);
        Acl::guard($n->id, 'mod');
        $locked = $request->boolean('locked');
        $path = SignedMedia::storeUpload($request->file('file'), $locked);
        $mime = $request->file('file')->getClientOriginalExtension();
        $mimeType = $request->file('file')->getMimeType() ?: 'application/octet-stream';
        $kind = str_starts_with($mimeType, 'image/') ? 'image' : (str_starts_with($mimeType, 'video/') ? 'video' : 'file');
        $thumb = $kind === 'image' ? SignedMedia::thumb($path, 'image') : '';
        $file = DriveFile::query()->create([
            'node_id' => $n->id ?? 'lumen',
            'title' => $request->file('file')->getClientOriginalName(),
            'path' => $locked ? $path : '/'.$path,
            'kind' => $kind,
            'locked' => $locked,
            'folder_id' => $request->integer('folder_id') ?: null,
            'mime' => $mimeType,
            'size' => $request->file('file')->getSize() ?: 0,
            'user_id' => Auth::id(),
            'lock_kind' => $locked ? ($request->string('lock_kind')->toString() ?: 'quest') : '',
            'lock_ref' => $request->string('lock_ref')->toString() ?: '',
            'thumb_path' => $thumb,
            'appear_order' => 0,
        ]);
        if (str_starts_with($mimeType, 'video/')) {
            Media::query()->create([
                'node_id' => $n->id ?? 'lumen',
                'title' => $request->file('file')->getClientOriginalName(),
                'path' => $path,
                'mode' => 'lore',
                'access' => $locked ? 'freemium' : 'free',
                'teaser_sec' => $locked ? 6 : 0,
                'price' => '',
                'duration' => '',
                'chapters' => '',
                'transcript' => 'Upload Drive',
            ]);
        }

        return back()->with('ok', $locked ? 'Fichier au coffre — il se mérite.' : 'Fichier sur le disque du serveur.');
    }

    public function folder(Request $request): RedirectResponse
    {
        $node = Acl::nodeOfSlug($request->string('slug')->toString());
        Acl::guard($node->id, 'mod');
        DB::table('folders')->insert([
            'node_id' => $node->id,
            'parent_id' => $request->integer('parent_id') ?: null,
            'title' => $request->validate(['title' => 'required|string'])['title'],
        ]);

        return back()->with('ok', 'Dossier créé.');
    }

    public function rename(Request $request): RedirectResponse
    {
        $f = DriveFile::query()->findOrFail($request->integer('id'));
        $node = GpNode::query()->findOrFail($f->node_id);
        Acl::guard($node->id, 'mod');
        DriveFile::query()->where('id', $f->id)->update([
            'title' => $request->validate(['title' => 'required|string'])['title'],
        ]);

        return back()->with('ok', 'Renommé.');
    }

    public function lock(Request $request): RedirectResponse
    {
        $f = DriveFile::query()->findOrFail($request->integer('id'));
        $node = GpNode::query()->findOrFail($f->node_id);
        Acl::guard($node->id, 'mod');
        $f->locked = ! $f->locked;
        if ($f->locked && ! $f->lock_kind) {
            $f->lock_kind = 'quest';
        }
        if (! $f->locked) {
            $f->lock_kind = '';
        }
        $f->save();

        return back()->with('ok', $f->locked ? 'Au coffre — il se mérite.' : 'Ouvert à tous.');
    }

    public function hero(Request $request): RedirectResponse
    {
        $f = DriveFile::query()->findOrFail($request->integer('id'));
        abort_unless($f->kind === 'image', 422);
        $node = GpNode::query()->findOrFail($f->node_id);
        Acl::guard($node->id, 'mod');
        $src = $f->thumb_path ?: $f->path;
        if (str_starts_with(ltrim($f->path, '/'), 'private/')) {
            abort_unless(Grantor::canSeeFile($f) || Acl::atLeast($node->id, 'mod'), 403);
        }
        $node->hero = str_starts_with($src, '/') ? $src : '/'.$src;
        $node->save();

        return back()->with('ok', 'Image posée en hero du lieu.');
    }

    public function layer(Request $request): RedirectResponse
    {
        $f = DriveFile::query()->findOrFail($request->integer('id'));
        abort_unless($f->kind === 'image', 422);
        $node = GpNode::query()->findOrFail($f->node_id);
        Acl::guard($node->id, 'mod');
        $target = $request->string('target')->toString();
        DB::table('node_scene_layers')->insert([
            'node_id' => $node->id,
            'tab' => '',
            'kind' => 'image',
            'label' => $f->title,
            'src' => $f->thumb_path ?: (str_starts_with($f->path, '/') ? $f->path : '/'.$f->path),
            'body' => '',
            'x' => 20, 'y' => 20, 'w' => 24, 'h' => 32,
            'z' => 4, 'opacity' => 1,
            'action_key' => '',
            'action_target' => $target,
            'visible' => 1, 'locked' => 0,
            'motion' => 'none', 'delay_ms' => 0,
        ]);

        return redirect('/n/'.$node->slug.'/monde?mode=design')->with('ok', 'Calque posé. Clic = lieu lié.');
    }
}
