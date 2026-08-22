<?php

namespace App\Http\Controllers;

use App\Models\GpNode;
use App\Support\Acl;
use App\Support\ClubImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PilotController extends Controller
{
    public function importForm(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        return view('import', compact('node'));
    }

    public function importStore(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $raw = $request->validate(['paste' => 'required|string|max:200000'])['paste'];
        $r = ClubImport::run($node, $raw);
        return redirect('/n/'.$slug.'/forum')->with('ok', $r['threads'].' sujets Legacy importés.');
    }

    public function board(): View
    {
        $rows = DB::table('bounties')->whereIn('status', ['open', 'claimed', 'submitted'])
            ->orderByDesc('id')->limit(80)->get();
        $clubs = GpNode::query()->whereIn('id', $rows->pluck('node_id'))->get()->keyBy('id');
        return view('bounty-board', compact('rows', 'clubs'));
    }

    public function applyForm(string $slug): View
    {
        abort_unless(Auth::id(), 401);
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $pack = DB::table('inventory')->where('user_id', Auth::id())->get();
        return view('apply', compact('node', 'pack'));
    }

    public function applyStore(Request $request, string $slug): RedirectResponse
    {
        abort_unless(Auth::id(), 401);
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        $ids = $request->input('relics', []);
        $ids = is_array($ids) ? array_map('intval', $ids) : [];
        $labels = DB::table('inventory')->where('user_id', Auth::id())->whereIn('id', $ids)->pluck('label');
        DB::table('applications')->insert([
            'user_id' => Auth::id(),
            'node_id' => $node->id,
            'relics' => json_encode($labels, JSON_UNESCAPED_UNICODE),
            'status' => 'sent',
        ]);
        return redirect('/n/'.$slug)->with('ok', 'Candidature envoyée avec '.count($labels).' relique(s).');
    }

    public function cv(?int $id = null): View
    {
        $user = $id ? \App\Models\User::query()->findOrFail($id) : Auth::user();
        abort_unless($user, 404);
        $pack = DB::table('inventory')->where('user_id', $user->id)->get();
        return view('cv', compact('user', 'pack'));
    }

    public function dns(string $slug): View
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id, 'owner');
        $host = $node->host ?: $node->slug;
        $ok = false;
        $target = gethostbyname($host.'.geniuspace.com');
        if ($target && $target !== $host.'.geniuspace.com') {
            $ok = true;
        }
        return view('dns', compact('node', 'host', 'ok', 'target'));
    }

    public function ping(string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $map = url('/n/'.$slug.'/sitemap.xml');
        $url = 'https://www.google.com/ping?sitemap='.urlencode($map);
        $http = 0;
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
            @file_get_contents($url, false, $ctx);
            $http = 200;
        } catch (\Throwable) {
            $http = 0;
        }
        DB::table('index_pings')->insert(['node_id' => $node->id, 'url' => $map, 'http' => $http]);
        return back()->with('ok', 'Sitemap ping : '.$map);
    }

    public function gsc(Request $request, string $slug): RedirectResponse
    {
        $node = GpNode::query()->where('slug', $slug)->firstOrFail();
        Acl::guard($node->id);
        $code = $request->validate(['gsc' => 'nullable|string|max:80'])['gsc'] ?? '';
        DB::table('node_seo')->updateOrInsert(['node_id' => $node->id], ['gsc' => $code]);
        return back()->with('ok', 'Balise Search Console enregistrée.');
    }
}
