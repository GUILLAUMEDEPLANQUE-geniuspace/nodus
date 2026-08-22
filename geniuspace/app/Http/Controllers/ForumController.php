<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Models\GuildMessage;
use App\Models\LiveMessage;
use App\Models\Reply;
use App\Models\Thread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForumController extends Controller
{
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
            'author' => 'Toi',
            'body' => $data['body'],
            'cover' => $node->hero,
            'views' => 1,
            'fires' => 0,
            'replies_count' => 0,
        ]);
        return redirect('/n/'.$node->slug.'?tab=forum&tid='.$id)->with('ok', 'Sujet publié (Legacy SEO).');
    }

    public function reply(Request $request, string $slug, string $tid): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:4000']);
        Reply::query()->create([
            'thread_id' => $tid,
            'author' => 'Toi',
            'body' => $data['body'],
            'votes' => 0,
        ]);
        Thread::query()->where('id', $tid)->increment('replies_count');
        return redirect('/n/'.$slug.'?tab=forum&tid='.$tid)->with('ok', 'Réponse Legacy indexée.');
    }

    public function live(Request $request, string $slug, string $tid): RedirectResponse
    {
        $data = $request->validate(['body' => 'required|string|max:500']);
        LiveMessage::query()->create([
            'thread_id' => $tid,
            'author' => 'Toi',
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
            'author' => 'Toi',
            'body' => $data['body'],
        ]);
        return redirect('/n/'.$slug.'?tab=guilde')->with('ok', 'Message de guilde.');
    }
}
