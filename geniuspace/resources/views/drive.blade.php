@extends('layouts.app')
@section('title', 'Drive — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem" x-data="{ over:false }">
  <p class="kicker">Coffre · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:2.6rem">Fichiers du lieu</h1>
  <p class="muted">Liés à une épreuve, un achat, un chapitre. Pas un dossier partagé. <a href="/n/{{ $slug }}/carnet">Carnet</a> · <a href="/studio/image?src={{ $node->hero }}&target=drive&slug={{ $slug }}">Retoucher</a></p>
  <form method="get" action="/drive" class="rel" style="margin:0.8rem 0">
    <select name="slug" onchange="this.form.submit()">
      @foreach($nodes as $n)
        <option value="{{ $n->slug }}" @selected($n->slug===$slug)>{{ $n->title }}</option>
      @endforeach
    </select>
  </form>
  @if($parent)
    <p><a href="/drive?slug={{ $slug }}">← Racine</a> / {{ $parent->title }}</p>
  @endif
  <div class="grid-3">
    @foreach($folders as $fo)
      <a class="card" href="/drive?slug={{ $slug }}&folder={{ $fo->id }}" style="padding:1.2rem">
        <p class="kicker">Dossier</p>
        <h3 class="font-display">{{ $fo->title }}</h3>
      </a>
    @endforeach
    @foreach($files as $f)
      @php
        $open = \App\Support\Grantor::canSeeFile($f) || ($staff ?? false);
        $href = $open ? \App\Support\Grantor::fileHref($f) : '#';
        $thumb = $f->thumb_path ?: (($f->kind==='image' && $open && !str_contains($f->path, 'private/')) ? $f->path : '');
      @endphp
      <article class="card" style="padding:1rem">
        @if($f->kind==='image' && $thumb)
          <img src="{{ $thumb }}" alt="" style="height:8rem;width:100%;object-fit:cover;border-radius:0.6rem">
        @elseif($f->kind==='image')
          <div style="height:8rem;border-radius:.6rem;background:var(--surface-2);display:flex;align-items:center;justify-content:center" class="muted">{{ $open ? 'image' : 'au coffre' }}</div>
        @endif
        <p class="kicker">{{ $f->kind }} {{ $f->locked && !$open ? 'se mérite' : ($f->locked ? 'ouvert pour vous' : '') }}</p>
        <h3>{{ $f->title }}</h3>
        <div class="rel" style="margin-top:0.4rem">
          @if($open)
            <a class="chip" href="{{ $href }}">ouvrir</a>
          @else
            <span class="chip">locké</span>
          @endif
          @if($f->kind==='image' && $open)
            <a class="chip" href="/studio/image?src={{ urlencode($thumb ?: $f->path) }}&target=drive&slug={{ $slug }}">éditer</a>
            <form method="post" action="/drive/hero" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $f->id }}"><button class="chip" type="submit">poser en hero</button></form>
            <form method="post" action="/drive/layer" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $f->id }}"><input type="hidden" name="target" value=""><button class="chip" type="submit">calque scène</button></form>
          @endif
          @if($staff ?? false)
            <form method="post" action="/drive/lock">@csrf<input type="hidden" name="id" value="{{ $f->id }}"><button class="chip" type="submit">{{ $f->locked ? 'ouvrir' : 'coffre' }}</button></form>
            <form method="post" action="/drive/rename">@csrf<input type="hidden" name="id" value="{{ $f->id }}"><input name="title" value="{{ $f->title }}" style="height:2rem;width:8rem"><button class="chip" type="submit">ok</button></form>
          @endif
        </div>
      </article>
    @endforeach
  </div>
  <form method="post" action="/drive/folder" style="margin:1.2rem 0">
    @csrf
    <input type="hidden" name="slug" value="{{ $slug }}">
    <input type="hidden" name="parent_id" value="{{ $parent->id ?? '' }}">
    <input name="title" required placeholder="Nouveau dossier">
    <button class="btn" type="submit">Créer dossier</button>
  </form>
  <form method="post" action="/drive" enctype="multipart/form-data" class="card" style="padding:1rem;max-width:32rem"
    @dragover.prevent="over=true" @dragleave="over=false" @drop.prevent="over=false; $refs.file.files = $event.dataTransfer.files">
    @csrf
    <input type="hidden" name="node_slug" value="{{ $slug }}">
    <input type="hidden" name="folder_id" value="{{ $parent->id ?? '' }}">
    <p class="muted" x-text="over ? 'Déposez ici' : 'Glisser-déposer ou parcourir'"></p>
    <input x-ref="file" type="file" name="file" required>
    <label class="muted"><input type="checkbox" name="locked" value="1"> Au coffre (épreuve / achat)</label>
    <button class="btn" type="submit">Déposer</button>
  </form>
</main>
@endsection
