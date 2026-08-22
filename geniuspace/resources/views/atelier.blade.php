@extends('layouts.app')
@section('title', 'Ton atelier — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem 5rem;max-width:44rem" x-data="{ step: 1, open: {} }">
  <p class="kicker">3 questions · chaque salle se habille</p>
  <h1 class="font-display" style="font-size:2.4rem">On monte {{ $node->title }}</h1>
  <p class="muted">Coche une salle, puis ouvre-la pour la couleur, le fond, l’anim, le SEO.</p>

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
      <p class="kicker">2 / 3 · les salles</p>
      <h2 class="font-display">Chez toi, on pourra…</h2>
      @foreach($groups as $g => $rooms)
        <p class="primary" style="margin:1.1rem 0 .4rem">{{ $g }}</p>
        @foreach($rooms as $key => [$label, $hint])
          @php $row = $chosen[$key] ?? null; @endphp
          <div class="card" style="padding:.75rem;margin:.4rem 0">
            <label style="cursor:pointer;display:block">
              <input type="checkbox" name="rooms[]" value="{{ $key }}" {{ $row || in_array($key, ['forum','personnages','videos']) ? 'checked' : '' }}>
              <strong>{{ $label }}</strong>
              <span class="muted"> — {{ $hint }}</span>
            </label>
            <button class="btn-ghost" type="button" style="font-size:.75rem" @click="open['{{ $key }}']=!open['{{ $key }}']">Habiller cette salle</button>
            <div x-show="open['{{ $key }}']" x-cloak style="margin-top:.6rem;display:grid;gap:.4rem">
              <label class="muted">Couleur <input type="color" name="style[{{ $key }}][color]" value="{{ $row->color ?? '#c9a36a' }}"></label>
              <label class="muted"><input type="checkbox" name="style[{{ $key }}][animate]" value="1" {{ !empty($row->animate) ? 'checked' : '' }}> Animation douce</label>
              <input name="style[{{ $key }}][seo_title]" placeholder="Titre SEO de la salle" value="{{ $row->seo_title ?? '' }}" style="width:100%">
              <input name="style[{{ $key }}][seo_desc]" placeholder="Description Google" value="{{ $row->seo_desc ?? '' }}" style="width:100%">
              <label class="muted">Image de fond <input type="file" name="bg_{{ $key }}" accept="image/*"></label>
            </div>
          </div>
        @endforeach
      @endforeach
      <div class="rel" style="margin-top:1rem">
        <button class="btn-line" type="button" @click="step=1">Retour</button>
        <button class="btn" type="button" @click="step=3">Suivant</button>
      </div>
    </section>

    <section class="card" style="padding:1.25rem;margin:1rem 0" x-show="step===3" x-cloak>
      <p class="kicker">3 / 3</p>
      <h2 class="font-display">Photo de couverture du club</h2>
      <input type="file" name="cover" accept="image/*">
      <div class="rel" style="margin-top:1.2rem">
        <button class="btn-line" type="button" @click="step=2">Retour</button>
        <button class="btn" type="submit">C’est bon, ouvrir le club</button>
      </div>
    </section>
  </form>
  <p class="muted" style="margin-top:1.5rem;font-size:.8rem">Plus tard : <a class="primary" href="/builder/{{ $node->slug }}">espace 3D pro</a></p>
</main>
@endsection
