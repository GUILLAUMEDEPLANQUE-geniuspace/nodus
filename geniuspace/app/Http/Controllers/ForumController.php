<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\GuildMessage;
use App\Models\LiveMessage;
use App\Models\Reply;
use App\Models\Thread;
use App\Support\Acl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    private function author(string $nodeId): string
    {
        Acl::mustUser();
        $name = Auth::user()->name;
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

    private function threadOnNode(string $slug, string $tid): Thread
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $thread = Thread::query()->where('id', $tid)->where('node_id', $node->id)->firstOrFail();

        return $thread;
    }

    public function reply(Request $request, string $slug, string $tid): RedirectResponse
    {
        Acl::mustUser();
        $data = $request->validate(['body' => 'required|string|max:4000']);
        $thread = $this->threadOnNode($slug, $tid);
        $nodeId = $thread->node_id;
        $vid = (string) $request->input('video_path', '');
        $media = $vid ? DB::table('media')->where('node_id', $nodeId)->where('path', $vid)->first() : null;
        $fp = (string) $request->input('file_path', '');
        $file = $fp ? DB::table('drive_files')->where('node_id', $nodeId)->where('path', $fp)->first() : null;
        Reply::query()->create([
            'thread_id' => $thread->id,
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
        Thread::query()->where('id', $thread->id)->increment('replies_count');
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
        $thread = $this->threadOnNode($slug, $tid);
        LiveMessage::query()->create([
            'thread_id' => $thread->id,
            'author' => $this->author($thread->node_id),
            'body' => $data['body'],
        ]);
        return redirect('/n/'.$slug.'?tab=forum&tid='.$thread->id.'&mode=live')->with('ok', 'Live envoyé.');
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
        Acl::mustUser();
        $thread = $this->threadOnNode($slug, $tid);
        Thread::query()->where('id', $thread->id)->increment('fires');
        DB::table('forum_awards')->insert(['thread_id' => $thread->id, 'kind' => 'feu', 'author' => Auth::user()->name]);
        return redirect('/n/'.$slug.'?tab=forum&tid='.$thread->id);
    }

    public function echoLive(Request $request, string $slug, string $tid): RedirectResponse
    {
        Acl::mustUser();
        $thread = $this->threadOnNode($slug, $tid);
        $id = $request->integer('live_id');
        $row = DB::table('live_messages')->where('id', $id)->where('thread_id', $thread->id)->first();
        abort_unless($row, 404);
        Reply::query()->create([
            'thread_id' => $thread->id,
            'author' => $row->author,
            'body' => $row->body,
            'votes' => 1,
            'pending' => 0,
            'media_path' => '',
            'author_avatar' => \App\Support\Faces::of($row->author),
        ]);
        Thread::query()->where('id', $thread->id)->increment('replies_count');
        return redirect('/n/'.$slug.'?tab=forum&tid='.$thread->id)->with('ok', 'Écho : le Live devient Legacy (SEO).');
    }

    public function award(string $slug, string $tid): RedirectResponse
    {
        Acl::mustUser();
        $thread = $this->threadOnNode($slug, $tid);
        DB::table('forum_awards')->insert(['thread_id' => $thread->id, 'kind' => 'relique', 'author' => Auth::user()->name]);
        return redirect('/n/'.$slug.'/t/'.$thread->id)->with('ok', 'Relique posée.');
    }
}
