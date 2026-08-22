@extends('layouts.app')
@section('title', 'Ton atelier — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem 5rem;max-width:40rem" x-data="{ step: 1 }">
  <p class="kicker">3 questions · pas de jargon</p>
  <h1 class="font-display" style="font-size:2.4rem">On monte {{ $node->title }}</h1>
  <p class="muted">Comme un club. Tu n’as rien à savoir en 3D ni en SEO.</p>

  <form method="post" action="/atelier/{{ $node->slug }}" enctype="multipart/form-data">
    @csrf

    <section class="card" style="padding:1.25rem;margin:1rem 0" x-show="step===1">
      <p class="kicker">1 / 3</p>
      <h2 class="font-display">C’est quoi, ici ?</h2>
      <input name="title" value="{{ $node->title }}" required style="width:100%;margin:.5rem 0">
      <textarea name="summary" placeholder="Ex. Club 205 GTI, sorties le dimanche, pièces et conseils." style="width:100%;min-height:6rem">{{ $node->summary }}</textarea>
      <button class="btn" type="button" style="margin-top:1rem" @click="step=2">Suivant</button>
    </section>

    <section class="card" style="padding:1.25rem;margin:1rem 0" x-show="step===2" x-cloak>
      <p class="kicker">2 / 3</p>
      <h2 class="font-display">Chez toi, on pourra…</h2>
      <p class="muted">Coche. Tu pourras changer plus tard.</p>
      @foreach($rooms as $key => [$label, $hint])
        <label class="card" style="display:block;padding:.75rem;margin:.4rem 0;cursor:pointer">
          <input type="checkbox" name="rooms[]" value="{{ $key }}" {{ in_array($key, ['forum','personnages','videos']) ? 'checked' : '' }}>
          <strong>{{ $label }}</strong>
          <span class="muted"> — {{ $hint }}</span>
        </label>
      @endforeach
      <div class="rel" style="margin-top:1rem">
        <button class="btn-line" type="button" @click="step=1">Retour</button>
        <button class="btn" type="button" @click="step=3">Suivant</button>
      </div>
    </section>

    <section class="card" style="padding:1.25rem;margin:1rem 0" x-show="step===3" x-cloak>
      <p class="kicker">3 / 3</p>
      <h2 class="font-display">Une photo de couverture</h2>
      <p class="muted">Ta 205, le hangar, le logo du club… ou passe.</p>
      <input type="file" name="cover" accept="image/*">
      <div class="rel" style="margin-top:1.2rem">
        <button class="btn-line" type="button" @click="step=2">Retour</button>
        <button class="btn" type="submit">C’est bon, ouvrir le club</button>
      </div>
    </section>
  </form>

  <p class="muted" style="margin-top:1.5rem;font-size:.8rem">
    Plus tard, si tu veux : <a class="primary" href="/builder/{{ $node->slug }}">espace 3D (mode pro)</a>
    · <a class="primary" href="/n/{{ $node->slug }}/studio">réglages</a>
  </p>
</main>
@endsection
