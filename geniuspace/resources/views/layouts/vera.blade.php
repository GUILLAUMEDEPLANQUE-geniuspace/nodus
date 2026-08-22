<!DOCTYPE html>
<html lang="fr" class="vera">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Vera — l’emploi enfin lisible')</title>
  <meta name="description" content="@yield('description', 'Offres d’emploi à salaire publié. Un test de 6 minutes avant le CV. Un délai de réponse écrit. Pas de pubs.')">
  <meta name="robots" content="@yield('robots', 'index,follow,max-image-preview:large')">
  <link rel="canonical" href="@yield('canonical', url()->current())">
  <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title')))">
  <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('description')))">
  <meta property="og:url" content="@yield('canonical', url()->current())">
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:image" content="@yield('og_image', url('/offer/releve-atelier.jpg'))">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="/favicon.svg">
  <link rel="stylesheet" href="/css/vera.css">
  @stack('jsonld')
  <script src="/js/preview-bridge.js"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
</head>
<body>
@php
  $tab = $tab ?? '';
  $nav = \App\Support\VeraCatalog::nav();
@endphp
<div class="vera-root" x-data="{ open: false }">
  <header class="vera-head">
    <div class="vera-wrap vera-head-inner">
      <a href="/n/vera" class="vera-brand">Vera</a>
      <span class="vera-tag">L’emploi, lisible.</span>
      <nav class="vera-nav">
        @foreach($nav as $n)
          <a href="{{ $n['to'] }}" class="{{ ($tab === $n['key'] || str_contains(url()->current(), $n['to'])) ? 'on' : '' }}">{{ $n['label'] }}</a>
        @endforeach
      </nav>
      <div class="vera-actions">
        <a class="vera-btn ghost" href="/login">Connexion</a>
        <button type="button" class="vera-burger" @click="open=!open" aria-label="Menu">☰</button>
      </div>
    </div>
    <nav class="vera-drawer" :class="open && 'open'">
      @foreach($nav as $n)
        <a href="{{ $n['to'] }}" @click="open=false">{{ $n['label'] }}</a>
      @endforeach
      <a href="/n/vera/savoirs" @click="open=false">Fiches métier</a>
      <a href="/n/vera/viviers" @click="open=false">Profils oubliés</a>
      <a href="/n/vera/lexique" @click="open=false">Lexique</a>
      <a href="/n/vera/delais" @click="open=false">Délais de réponse</a>
      <a href="/n/vera/offres" @click="open=false">Toutes les offres</a>
    </nav>
  </header>
  <main style="flex:1">
    @yield('content')
  </main>
  <footer class="vera-footer">
    <div class="vera-wrap cols">
      <div>
        <div class="vera-brand">Vera</div>
        <p style="max-width:28rem;margin:.4rem 0 0">Un test de 6 minutes avant le CV. Si ça rate, un module, puis on rejoue. Un carnet de preuves exportable. L’Europe, pas une traduction.</p>
      </div>
      <div class="chips" style="align-items:flex-end">
        <a href="/n/vera/europe">Europe</a>
        <a href="/n/vera/preuve">Tests métier</a>
        <a href="/n/vera/carnet">Mon carnet</a>
        <a href="/n/vera/delais">Délais de réponse</a>
        <a href="/n/vera/offres">Offres</a>
        <a href="/n/vera/lexique">Lexique</a>
        <a href="/n/lumen">Lumen</a>
        <a href="/">Geniuspace</a>
      </div>
    </div>
  </footer>
</div>
</body>
</html>
