<?php

namespace App\Http\Middleware;

use App\Models\GpNode;
use Closure;
use Illuminate\Http\Request;

/** 205.geniuspace.com → /w/205 si host = slug ou colonne host. */
class ClubHost
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $first = explode('.', $host)[0];
        if (in_array($first, ['www', '127', 'localhost', '0', 'geniuspace'], true)) {
            return $next($request);
        }
        if ($request->is('w/*') || $request->is('login*') || $request->is('up')) {
            return $next($request);
        }
        $node = GpNode::query()->where('host', $first)->orWhere('slug', $first)->first();
        if ($node && $request->path() === '/') {
            return redirect('/w/'.$node->slug);
        }
        return $next($request);
    }
}
