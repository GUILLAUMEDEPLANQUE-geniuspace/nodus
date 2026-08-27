<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Coffre privé : notifications, DM, inventaire. Jamais /profil/{id}. */
    public function me(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect('/login');
        }
        $user = Auth::user();
        $playlists = DB::table('playlists')->where('user_id', $user->id)->get();
        $notifs = DB::table('notifications')->where('user_id', $user->id)->orderByDesc('id')->limit(20)->get();
        $dms = DB::table('dm_messages')->where(function ($q) use ($user) {
            $q->where('to_id', $user->id)->orWhere('from_id', $user->id);
        })->orderByDesc('id')->limit(20)->get();
        $pack = DB::table('inventory')->where('user_id', $user->id)->orderByDesc('id')->get();
        $mine = true;

        return view('profile', compact('user', 'playlists', 'notifs', 'dms', 'pack', 'mine'));
    }

    /** Carte publique. Pas de DM, pas de notifications, pas d’inventaire. */
    public function show(int $id): View
    {
        $user = User::query()->findOrFail($id);
        $playlists = DB::table('playlists')->where('user_id', $user->id)->get();
        $notifs = collect();
        $dms = collect();
        $pack = collect();
        $mine = false;

        return view('profile', compact('user', 'playlists', 'notifs', 'dms', 'pack', 'mine'));
    }

    public function save(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);
        $user->update($request->validate(['name' => 'required|string', 'bio' => 'nullable|string']));

        return back()->with('ok', 'Profil enregistré.');
    }

    public function playlist(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user, 401);
        $title = $request->validate(['title' => 'required|string'])['title'];
        $slug = 'pl-'.substr(md5($title.microtime()), 0, 8);
        DB::table('playlists')->insert(['user_id' => $user->id, 'title' => $title, 'share_slug' => $slug]);

        return back()->with('ok', 'Playlist créée — /pl/'.$slug);
    }

    public function addToPlaylist(Request $request): RedirectResponse
    {
        abort_unless(Auth::check(), 403, 'Connectez-vous pour écrire.');
        $data = $request->validate([
            'playlist_id' => 'required|integer',
            'media_id' => 'required|integer',
        ]);
        $pl = DB::table('playlists')->where('id', $data['playlist_id'])->where('user_id', Auth::id())->first();
        abort_unless($pl, 403);
        DB::table('playlist_items')->insert([
            'playlist_id' => $pl->id,
            'media_id' => $data['media_id'],
        ]);

        return back()->with('ok', 'Ajouté à la playlist.');
    }

    public function playlistShow(string $share): View
    {
        $pl = DB::table('playlists')->where('share_slug', $share)->firstOrFail();
        $items = DB::table('playlist_items')->where('playlist_id', $pl->id)->get();

        return view('playlist', compact('pl', 'items'));
    }

    public function dm(Request $request): RedirectResponse
    {
        abort_unless(Auth::check(), 403, 'Connectez-vous pour écrire.');
        DB::table('dm_messages')->insert([
            'from_id' => Auth::id(),
            'to_id' => $request->integer('to_id'),
            'body' => $request->validate(['body' => 'required|string'])['body'],
        ]);

        return back()->with('ok', 'Message envoyé.');
    }
}
