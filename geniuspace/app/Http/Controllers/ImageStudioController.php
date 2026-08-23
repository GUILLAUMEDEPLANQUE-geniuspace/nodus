<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\GpNode;
use App\Models\Product;
use App\Models\Thread;
use App\Models\User;
use App\Support\Acl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Éditeur d'images créateur : produits, covers forum, héros, avatars, Drive.
 * Enregistre un JPEG dans public/media/edits/ puis met à jour la cible.
 */
class ImageStudioController extends Controller
{
    public function show(Request $request): View
    {
        $src = $request->query('src', '/realms/sea-hero.jpg');
        $target = $request->query('target', 'drive'); // drive|hero|product|thread|avatar|banner
        $id = $request->query('id', '');
        $slug = $request->query('slug', '');
        return view('image-studio', compact('src', 'target', 'id', 'slug'));
    }

    public function save(Request $request): RedirectResponse
    {
        Acl::mustUser();
        $data = $request->validate([
            'data' => 'required|string',
            'target' => 'required|string',
            'id' => 'nullable|string',
            'slug' => 'nullable|string',
            'src' => 'nullable|string',
        ]);
        $slug = (string) ($data['slug'] ?? '');
        if (in_array($data['target'], ['hero', 'product', 'thread', 'drive'], true)) {
            Acl::guard(Acl::nodeOfSlug($slug !== '' ? $slug : 'lumen')->id, 'admin');
        }
        $raw = $data['data'];
        if (! str_starts_with($raw, 'data:image')) {
            return back()->with('ok', 'Image invalide.');
        }
        $bin = base64_decode(preg_replace('#^data:image/\w+;base64,#', '', $raw), true);
        if ($bin === false) {
            return back()->with('ok', 'Décodage impossible.');
        }
        $dir = public_path('media/edits');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'e-'.now()->format('Ymd-His').'-'.substr(md5($bin), 0, 6).'.jpg';
        file_put_contents($dir.'/'.$name, $bin);
        $path = '/media/edits/'.$name;
        DB::table('edits')->insert(['path' => $path, 'source' => $data['src'] ?? '', 'context' => $data['target']]);

        $id = $data['id'] ?? '';
        match ($data['target']) {
            'hero' => GpNode::query()->where('slug', $data['slug'])->update(['hero' => $path]),
            'product' => Product::query()->where('id', $id)->update(['image' => $path]),
            'thread' => Thread::query()->where('id', $id)->update(['cover' => $path]),
            'avatar' => Auth::user()?->update(['avatar' => $path]),
            'banner' => Auth::user()?->update(['banner' => $path]),
            default => $this->toDrive($path, $data['slug'] ?? 'one-piece'),
        };

        $back = match ($data['target']) {
            'hero' => '/n/'.($data['slug'] ?: 'one-piece'),
            'product' => '/n/'.($data['slug'] ?: 'one-piece').'/p/'.$id,
            'thread' => '/n/'.($data['slug'] ?: 'one-piece').'/t/'.$id,
            'avatar', 'banner' => '/profil',
            default => '/drive',
        };
        return redirect($back)->with('ok', 'Image enregistrée dans le Drive (edits).');
    }

    private function toDrive(string $path, string $slug): void
    {
        $node = GpNode::query()->where('slug', $slug)->first();
        DriveFile::query()->create([
            'node_id' => $node->id ?? 'onepiece',
            'title' => basename($path),
            'path' => $path,
            'kind' => 'image',
            'locked' => false,
            'mime' => 'image/jpeg',
            'size' => 0,
            'user_id' => Auth::id(),
        ]);
    }
}
