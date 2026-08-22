@extends('layouts.app')
@section('title', 'Magazine — '.$node->title)
@section('description', 'Guides et analyses du '.$node->title.' : thématiques, intents, articles indexables.')
@section('canonical', url('/n/'.$node->slug.'/blog'))
@push('jsonld')
<script type="application/ld+json">{!! json_encode([
  '@'.'context'=>'https://schema.org','@'.'type'=>'Blog','name'=>$node->title.' Magazine',
  'url'=>url('/n/'.$node->slug.'/blog'),
  'blogPost'=>$articles->map(fn($a)=>['@'.'type'=>'BlogPosting','headline'=>$a->title,'url'=>url('/n/'.$node->slug.'/blog/'.$a->urlSlug())])->values(),
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<main class="wrap {{ $node->kind==='auto'?'skin-auto':'' }}" style="padding:2rem 1.25rem 6rem">
  <p class="kicker">Magazine · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:clamp(2rem,5vw,3.2rem)">Guides, essais, vidéos — pas un fil Facebook</h1>
  <p class="lede">Chaque article a une URL, un résumé opérationnel, une FAQ, un cluster. Les membres publient. Google lit.</p>

  <h2 class="font-display">Vous voulez…</h2>
  <div class="grid-3" style="margin-bottom:2rem">
    @foreach($articles->take(6) as $a)
      <a class="card card-film" href="/n/{{ $node->slug }}/blog/{{ $a->urlSlug() }}">
        <img src="{{ $a->cover ?: $node->hero }}" alt="">
        <div class="pad">
          <p class="kicker">{{ $a->theme }}{{ $a->video_path ? ' · vidéo' : '' }}</p>
          <h3 class="font-display">{{ $a->title }}</h3>
          <p class="muted">{{ \Illuminate\Support\Str::limit($a->resume, 110) }}</p>
        </div>
      </a>
    @endforeach
  </div>

  @foreach($themes as $theme => $list)
    <section style="margin:2rem 0">
      <p class="kicker">Thématique</p>
      <h2 class="font-display">{{ $theme }}</h2>
      @foreach($list as $a)
        <p class="card" style="padding:1rem;margin:.4rem 0">
          <a href="/n/{{ $node->slug }}/blog/{{ $a->urlSlug() }}"><strong>{{ $a->title }}</strong></a>
          <span class="muted"> · {{ $a->author }} · {{ $a->reading_min }} min</span>
        </p>
      @endforeach
    </section>
  @endforeach

  <form method="post" action="/n/{{ $node->slug }}/blog" class="mag-box" style="margin-top:2.5rem">
    @csrf
    <p class="kicker">Publier un article ou une vidéo</p>
    <input name="title" required placeholder="Titre = requête (ex. Joint de culasse 205 GTI Reims)" style="width:100%;margin:.4rem 0">
    <select name="theme">
      <option>Technique</option><option>Meets</option><option>Essais</option><option>Pièces</option><option>Métier</option>
    </select>
    <textarea name="resume" required placeholder="Résumé opérationnel (ce que Google / le LLM doit citer)" style="width:100%;min-height:4rem;margin:.4rem 0"></textarea>
    <textarea name="body" required placeholder="Corps. @fiches pour le maillage." style="width:100%;min-height:8rem"></textarea>
    <input name="definition_term" placeholder="Terme défini" style="width:100%;margin:.4rem 0">
    <textarea name="definition" placeholder="Encadré définition" style="width:100%;min-height:3rem"></textarea>
    <textarea name="toc" placeholder="Sommaire, une ligne par H2" style="width:100%;min-height:3rem;margin:.4rem 0"></textarea>
    <textarea name="longtail" placeholder="requête | note (une par ligne)" style="width:100%;min-height:3rem"></textarea>
    <textarea name="faq" placeholder="Question || Réponse (une par ligne)" style="width:100%;min-height:3rem;margin:.4rem 0"></textarea>
    <select name="video_path">
      <option value="">Sans vidéo</option>
      @foreach($node->media as $m)<option value="{{ $m->path }}">Vidéo · {{ $m->title }}</option>@endforeach
    </select>
    <p><button class="btn" type="submit">Publier (URL indexable)</button></p>
  </form>
</main>
@isset($tabs)
<nav class="dock">
  @foreach($tabs as $t)
    <a href="/n/{{ $node->slug }}/{{ $t->key }}" class="{{ ($tab ?? '') === $t->key ? 'active' : '' }}">{{ $t->label }}</a>
  @endforeach
</nav>
@endisset
@endsection
