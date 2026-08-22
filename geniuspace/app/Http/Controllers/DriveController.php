<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Support\SignedMedia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriveController extends Controller
{
    public function index(): View
    {
        $dir = public_path(config('media.dir', 'media'));
        $files = is_dir($dir)
            ? array_values(array_filter(scandir($dir) ?: [], fn ($f) => $f !== '.' && $f !== '..'))
            : [];
        $rows = Media::query()->latest('id')->get();
        return view('drive', compact('files', 'rows'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimetypes:video/mp4,video/webm,audio/mpeg,audio/mp4,image/jpeg,image/png,image/webp,application/zip|max:512000',
            'title' => 'nullable|string|max:180',
            'node_slug' => 'nullable|string',
        ]);
        $path = SignedMedia::storeUpload($request->file('file'));
        $nodeId = 'onepiece';
        if ($slug = $request->string('node_slug')->toString()) {
            $n = \App\Models\GpNode::query()->where('slug', $slug)->first();
            if ($n) {
                $nodeId = $n->id;
            }
        }
        Media::query()->create([
            'node_id' => $nodeId,
            'title' => $request->string('title')->toString() ?: $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'mode' => 'lore',
            'access' => 'free',
            'teaser_sec' => 0,
            'price' => '',
            'duration' => '',
            'chapters' => '',
            'transcript' => 'Fichier local (mutu / VPS).',
        ]);
        return back()->with('ok', 'Fichier enregistré dans public/media (disque du serveur).');
    }
}
