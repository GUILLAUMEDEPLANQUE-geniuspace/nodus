@extends('layouts.app')
@section('title', 'Studio — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem">
  <p class="kicker">Studio · rôle {{ $role ?: 'visiteur' }}</p>
  <h1 class="font-display" style="font-size:2.6rem">Configurer {{ $node->title }}</h1>
  <p class="muted">Owner / admin : SEO, CCK, onglets, ATS. Modo : file d’attente, bans. <a class="primary" href="/studio/image?src={{ urlencode($node->hero) }}&target=hero&slug={{ $node->slug }}">Éditer le héros</a></p>

  <h2 class="font-display">SEO (owner/admin)</h2>
  <form method="post" action="/n/{{ $node->slug }}/studio/seo" class="card" style="padding:1rem;max-width:36rem">
    @csrf
    <input name="title" placeholder="Title" value="{{ $seo->title ?? '' }}" style="width:100%;margin:0.3rem 0">
    <textarea name="description" placeholder="Description" style="width:100%">{{ $seo->description ?? '' }}</textarea>
    <input name="keywords" placeholder="keywords" value="{{ $seo->keywords ?? '' }}" style="width:100%;margin:0.3rem 0">
    <label class="muted"><input type="checkbox" name="noindex" value="1" @checked($seo->noindex ?? false)> noindex</label>
    <button class="btn" type="submit">Sauver SEO</button>
  </form>

  <h2 class="font-display">Onglets du dock</h2>
  <div class="rel">@foreach($tabs as $t)<span class="chip">{{ $t->label }} ({{ $t->key }})</span>@endforeach</div>
  <form method="post" action="/n/{{ $node->slug }}/studio/tab" class="rel" style="margin-top:0.5rem">
    @csrf
    <input name="key" placeholder="key" required>
    <input name="label" placeholder="Libellé" required>
    <button class="btn" type="submit">Ajouter</button>
  </form>

  <h2 class="font-display">CCK</h2>
  @foreach($cck as $f)
    <p class="card" style="padding:0.7rem;margin:0.3rem 0">{{ $f->name }} · {{ $f->type }} = {{ $f->value }}</p>
  @endforeach
  <form method="post" action="/n/{{ $node->slug }}/studio/cck">
    @csrf
    <input name="name" placeholder="Nom" required>
    <select name="type"><option>text</option><option>number</option><option>image</option><option>bool</option><option>rich</option></select>
    <input name="value" placeholder="Valeur">
    <button class="btn" type="submit">Champ</button>
  </form>

  <h2 class="font-display">ATS (7 étapes)</h2>
  @foreach($steps as $s)
    <p class="step">{{ $s->step }}. {{ $s->title }} — {{ $s->prompt }}</p>
  @endforeach
  <form method="post" action="/n/{{ $node->slug }}/studio/ats">@csrf<input name="title" placeholder="Nouvelle étape"><input name="prompt" placeholder="Prompt"><button class="btn" type="submit">Ajouter</button></form>

  <h2 class="font-display">Modération</h2>
  @forelse($pending as $p)
    <form method="post" action="/n/{{ $node->slug }}/studio/approve">@csrf<input type="hidden" name="id" value="{{ $p->id }}"><button class="btn" type="submit">Publier #{{ $p->id }} {{ $p->author }}</button></form>
  @empty
    <p class="muted">File d’attente vide.</p>
  @endforelse
  <form method="post" action="/n/{{ $node->slug }}/studio/ban">@csrf<input name="author" placeholder="Auteur"><input name="reason" placeholder="Raison"><button class="btn-line" type="submit">Ban</button></form>
  @foreach($bans as $b)<p class="muted">Ban {{ $b->author }} — {{ $b->reason }}</p>@endforeach

  <form method="post" action="/n/{{ $node->slug }}/studio/weave" style="margin-top:1rem">@csrf<button class="btn-line" type="submit">Éclater les sujets → wiki</button></form>
  <p><a class="btn-ghost" href="/drive?slug={{ $node->slug }}">Ouvrir le Drive</a></p>
</main>
@endsection
