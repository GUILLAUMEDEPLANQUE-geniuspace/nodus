<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Geniuspace')</title>
    <meta name="description" content="@yield('description', 'Univers interconnectés — offres lisibles, galeries, fiches liées.')">
    <meta name="robots" content="@yield('robots', 'index,follow,max-image-preview:large')">
    @isset($node)
      @php $gsc = \Illuminate\Support\Facades\DB::table('node_seo')->where('node_id', $node->id)->value('gsc'); @endphp
      @if($gsc)<meta name="google-site-verification" content="{{ $gsc }}">@endif
    @endisset
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title')))">
    <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('description')))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:image" content="@yield('og_image', url('/realms/205-garage.jpg'))">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/favicon.svg">
    <style>
      :root{--bg:#07080c;--fg:#f3eadc;--primary:#c9a36a;--muted:#8d8794;--border:#2a2c38;--font:system-ui,sans-serif;--display:Georgia,serif}
      html,body{margin:0;background:var(--bg);color:var(--fg);font-family:var(--font);min-height:100%}
      a{color:inherit;text-decoration:none} button{cursor:pointer}
    </style>
    <link rel="stylesheet" href="/css/geniuspace.css">
    @isset($node)
      @php
        $chromeTheme = $chromeTheme ?? ($chrome['theme'] ?? null);
        if (! $chromeTheme && \Illuminate\Support\Facades\Schema::hasTable('node_theme')) {
            $chromeTheme = \App\Support\Chrome::theme($node->id);
        }
      @endphp
      @if(!empty($chromeTheme) && ($chromeTheme->primary ?? ''))
        <style>
          :root{
            --primary: {{ $chromeTheme->primary }};
            --bg: {{ $chromeTheme->bg }};
            --fg: {{ $chromeTheme->fg }};
            --muted: {{ $chromeTheme->muted }};
          }
        </style>
        @if($chromeTheme->favicon ?? '')
          <link rel="icon" href="{{ $chromeTheme->favicon }}">
        @endif
      @endif
    @endisset
    @stack('jsonld')
    <script src="/js/preview-bridge.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script>
      try{var t=localStorage.getItem('nodus-theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}
    </script>
</head>
<body>
@if(session('ok'))
  <p class="kicker wrap" style="padding:.6rem 1.25rem;background:var(--surface)">{{ session('ok') }}</p>
@endif
@php $white = $white ?? false; @endphp
<header class="site-head">
    @if($white && isset($node))
      <a href="/w/{{ $node->slug }}" class="brand">{{ $node->title }}</a>
      <form action="/n/{{ $node->slug }}/q" class="nav" style="flex:1;max-width:20rem">
        <input name="q" placeholder="Dans le club…">
      </form>
    @else
    <a href="/" class="brand">Geniuspace</a>
    <form action="/explore" class="nav" style="flex:1;max-width:20rem">
        <input name="q" placeholder="205 GTI, joint culasse, Luffy…" style="width:100%">
    </form>
    <nav class="nav">
        <a class="btn-ghost" href="/explore">Explorer</a>
        <a class="btn-ghost" href="/create">Créer</a>
        <a class="btn-ghost" href="/drive">Drive</a>
        <a class="btn-ghost" href="/panier">Panier</a>
        @auth
          <a class="btn-ghost" href="/profil">{{ auth()->user()->name }}</a>
          <form method="post" action="/logout">@csrf<button class="btn-ghost" type="submit">Out</button></form>
        @else
          <a class="btn-line" href="/login/demo">Créateur</a>
        @endauth
        <button class="btn-line" type="button" onclick="document.documentElement.setAttribute('data-theme',document.documentElement.getAttribute('data-theme')==='light'?'dark':'light');localStorage.setItem('nodus-theme',document.documentElement.getAttribute('data-theme'))">Thème</button>
    </nav>
    @endif
</header>
@if(session('ok'))
    <p class="wrap primary" style="padding-top:0.75rem">{{ session('ok') }}</p>
@endif
@yield('content')
<footer class="wrap muted" style="padding:2rem 1.25rem 6rem;font-size:.8rem">
  <a href="/n/vera">Vera</a> · <a href="/n/lumen">Lumen</a> · <a href="/create">Créer</a> · <a href="/llms.txt">llms.txt</a>
</footer>
</body>
</html>
