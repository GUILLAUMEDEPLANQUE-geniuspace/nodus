<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Geniuspace')</title>
    <meta name="description" content="@yield('description', 'Univers interconnectés — graphe, boutique RWA, recrutement expérientiel.')">
    <meta name="robots" content="@yield('robots', 'index,follow,max-image-preview:large')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="icon" href="/favicon.svg">
    <link rel="stylesheet" href="{{ asset('css/geniuspace.css') }}">
    @stack('jsonld')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script>
      try{var t=localStorage.getItem('nodus-theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-theme',t)}catch(e){}
    </script>
</head>
<body>
<header class="site-head">
    <a href="{{ route('home') }}" class="brand">Geniuspace</a>
    <form action="{{ route('explore') }}" class="nav" style="flex:1;max-width:20rem">
        <input name="q" placeholder="Jack O'Neill, Luffy…" style="width:100%">
    </form>
    <nav class="nav">
        <a class="btn-ghost" href="{{ route('explore') }}">Explorer</a>
        <button class="btn-line" type="button" onclick="document.documentElement.setAttribute('data-theme',document.documentElement.getAttribute('data-theme')==='light'?'dark':'light');localStorage.setItem('nodus-theme',document.documentElement.getAttribute('data-theme'))">Thème</button>
    </nav>
</header>
@if(session('ok'))
    <p class="wrap primary" style="padding-top:0.75rem">{{ session('ok') }}</p>
@endif
@yield('content')
</body>
</html>
