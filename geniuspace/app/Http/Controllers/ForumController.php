<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\GuildMessage;
use App\Models\LiveMessage;
use App\Models\Reply;
use App\Models\Thread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    private function author(string $nodeId): string
    {
        $name = Auth::user()->name ?? 'Toi';
        abort_if(DB::table('forum_bans')->where('node_id', $nodeId)->where('author', $name)->exists(), 403, 'Banni de ce forum');
        return $name;
    }

    public function thread(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => 'required|string',
            'title' => 'required|string|max:180',
            'body' => 'required|string|max:4000',
        ]);
        $node = GpNode::query()->where('slug', $data['slug'])->firstOrFail();
        $id = 'th-'.substr(md5($data['title'].microtime()), 0, 10);
        Thread::query()->create([
            'id' => $id,
            'node_id' => $node->id,
            'kind' => 'forum',
            'title' => $data['title'],
            'author' => $this->author($node->id),
            'body' => $data['body'],
            'cover' => $node->hero,
            'views' => 1,
            'fires' => 0,
            'replies_count' => 0,
        ]);
        return redirect('/n/'.$node->slug.'/t/'.$id)->with('ok', 'Sujet publié (Legacy SEO).');
    }

    public function reply(Request $request, string $slug, string $tid): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $nodeId = GpNode::query()->where('slug', $slug)->value('id');
        $vid = (string) $request->input('video_path', '');
        $media = $vid ? DB::table('media')->where('node_id', $nodeId)->where('path', $vid)->first() : null;
        $fp = (string) $request->input('file_path', '');
        $file = $fp ? DB::table('drive_files')->where('node_id', $nodeId)->where('path', $fp)->first() : null;
        Reply::query()->create([
            'thread_id' => $tid,
            'author' => $this->author($nodeId),
            'body' => $data['body'],
            'votes' => 0,
            'pending' => 0,
            'media_path' => $vid,
            'product_id' => (string) $request->input('product_id', ''),
            'video_path' => $vid,
            'video_title' => $media->title ?? '',
            'video_meta' => $media ? ($media->duration.' · '.$media->mode) : '',
            'file_path' => $fp,
            'file_title' => $file->title ?? '',
            'file_locked' => (bool) ($file->locked ?? false),
            'badge' => '',
        ]);
        Thread::query()->where('id', $tid)->increment('replies_count');
        if (preg_match_all('/@([a-z0-9][a-z0-9\-]+)/i', $data['body'], $mm)) {
            foreach (array_unique($mm[1]) as $key) {
                $prod = \App\Models\Product::query()->where('node_id', $nodeId)->where('id', $key)->first();
                DB::table('citations')->insert([
                    'thread_id' => $tid,
                    'target_slug' => $key,
                    'product_id' => $prod->id ?? '',
                    'user_id' => Auth::id(),
                ]);
            }
        }
        return redirect('/n/'.$slug.'/t/'.$tid)->with('ok', 'Réponse Legacy indexée.');
    }

    public function live(Request $request, string $slug, string $tid): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:500']);
        $nodeId = GpNode::query()->where('slug', $slug)->value('id');
        LiveMessage::query()->create([
            'thread_id' => $tid,
            'author' => $this->author($nodeId),
            'body' => $data['body'],
        ]);
        return redirect('/n/'.$slug.'?tab=forum&tid='.$tid.'&mode=live')->with('ok', 'Live envoyé.');
    }

    public function guild(Request $request, string $slug): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:500']);
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        GuildMessage::query()->create([
            'node_id' => $node->id,
            'author' => $this->author($node->id),
            'body' => $data['body'],
        ]);
        return redirect('/n/'.$slug.'?tab=guilde')->with('ok', 'Message de guilde.');
    }

    public function fire(string $slug, string $tid): RedirectResponse
    {
        Thread::query()->where('id', $tid)->increment('fires');
        DB::table('forum_awards')->insert(['thread_id' => $tid, 'kind' => 'feu', 'author' => Auth::user()->name ?? 'Toi']);
        return redirect('/n/'.$slug.'?tab=forum&tid='.$tid);
    }

    public function echoLive(Request $request, string $slug, string $tid): RedirectResponse
    {
        $id = $request->integer('live_id');
        $row = DB::table('live_messages')->where('id', $id)->first();
        abort_unless($row, 404);
        Reply::query()->create([
            'thread_id' => $tid,
            'author' => $row->author,
            'body' => $row->body,
            'votes' => 1,
            'pending' => 0,
            'media_path' => '',
            'author_avatar' => \App\Support\Faces::of($row->author),
        ]);
        Thread::query()->where('id', $tid)->increment('replies_count');
        return redirect('/n/'.$slug.'?tab=forum&tid='.$tid)->with('ok', 'Écho : le Live devient Legacy (SEO).');
    }

    public function award(string $slug, string $tid): RedirectResponse
    {
        DB::table('forum_awards')->insert(['thread_id' => $tid, 'kind' => 'relique', 'author' => Auth::user()->name ?? 'Toi']);
        return redirect('/n/'.$slug.'/t/'.$tid)->with('ok', 'Relique posée.');
    }
}
