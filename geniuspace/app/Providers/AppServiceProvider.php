<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }
        $req = request();
        $host = $req->header('X-Forwarded-Host') ?: $req->getHttpHost();
        $host = trim(explode(',', (string) $host)[0]);
        if ($host === '') {
            return;
        }
        $proto = $req->header('X-Forwarded-Proto') ?: $req->getScheme();
        $root = $proto.'://'.$host;
        $port = $req->header('X-Forwarded-Port');
        if ($port && ! in_array((string) $port, ['80', '443'], true) && ! str_contains($host, ':')) {
            $root .= ':'.$port;
        }
        URL::forceRootUrl($root);
        if ($proto === 'https') {
            URL::forceScheme('https');
        }
    }
}
