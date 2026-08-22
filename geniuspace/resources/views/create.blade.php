@extends('layouts.app')
@section('title', $count.' templates d’univers — Geniuspace')
@section('description', 'Cinquante univers uniques : manga, Vera, jeux, pays, formation, annonces. SEO d’entité, tout customisable.')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem" x-data="{ g: 'Tous', id: '' }">
  <p class="kicker">Création de monde</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.4rem);line-height:.95">{{ $count }} templates.<br>Aucun n’existe ailleurs.</h1>
  <p class="lede">Pas un thème WordPress. Chaque carte = schéma Google + salles + curseur + magazine. Le contenu, c’est toi.</p>

  <div class="rel" style="margin:1rem 0;flex-wrap:wrap">
    <button type="button" class="chip" @click="g='Tous'" :class="g==='Tous' && 'primary'">Tous</button>
    @foreach(array_keys($groups) as $name)
      <button type="button" class="chip" @click="g=@js($name)" :class="g===@js($name) && 'primary'">{{ $name }}</button>
    @endforeach
  </div>

  @foreach($groups as $name => $list)
    <section x-show="g==='Tous' || g===@js($name)" style="margin:1.5rem 0">
      <p class="kicker">{{ $name }}</p>
      <div class="tpl-grid">
        @foreach($list as $t)
          <button type="button" class="tpl" style="--tpl:{{ $t['primary'] }}" @click="id=@js($t['id'])" :class="id===@js($t['id']) && 'on'">
            <span class="tpl-dot"></span>
            <strong>{{ $t['label'] }}</strong>
            <em>{{ $t['innovation'] }}</em>
            <small>{{ $t['schema'] }} · {{ count($t['rooms']) }} salles</small>
          </button>
        @endforeach
      </div>
    </section>
  @endforeach

  <form method="post" action="/create" class="mag-box" style="max-width:36rem;margin-top:2rem">
    @csrf
    <input type="hidden" name="template" :value="id">
    <p class="kicker">Tu as choisi : <span x-text="id || 'aucun — club simple'"></span></p>
    <label class="muted">Nom du monde</label>
    <input name="title" required placeholder="Club 205, Maison Orion, Hub One Piece…" style="width:100%;margin:.4rem 0">
    <textarea name="summary" placeholder="Une phrase. Google la lira." style="width:100%;min-height:4.5rem"></textarea>
    <button class="btn" type="submit" style="margin-top:.7rem">Créer — puis on habille</button>
  </form>
</main>
@endsection
