@extends('layouts.app')
@section('title', $count.' templates d’univers — Geniuspace')
@section('description', 'Dix flagships : Coffre, Terrain, Atelier, Territoire, Maison, Scène, Arène, Labo, Plateau, Table. Puis 50 métiers.')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem" x-data="{
  g: 'Flagship',
  pick: null,
  choose(t) {
    this.pick = t;
    this.$nextTick(() => {
      const el = document.getElementById('composer');
      el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      this.$refs.title?.focus();
    });
  }
}">
  <p class="kicker">Création de monde</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.4rem);line-height:.95">Dix flagships.<br>Pas des skins. Des moteurs.</h1>
  <p class="lede">Coffre, Terrain, Atelier, Territoire, Maison, Scène, Arène, Labo, Plateau, Table. Chaque carte a un hôte, des passages, des preuves. <a href="/flagships">Bible →</a></p>

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
          <button type="button" class="tpl {{ !empty($t['flagship']) ? 'flag' : '' }}" style="--tpl:{{ $t['primary'] }}"
            @click="choose({id:@js($t['id']), label:@js($t['label']), pitch:@js($t['innovation']), schema:@js($t['schema'])})"
            :class="pick && pick.id===@js($t['id']) && 'on'">
            <img src="/tpl/{{ $t['id'] }}.svg" alt="{{ $t['label'] }}" width="320" height="180">
            <strong>{{ $t['label'] }}</strong>
            <em>{{ $t['innovation'] }}</em>
            <small>{{ $t['schema'] }} · {{ count($t['rooms']) }} salles</small>
          </button>
        @endforeach
      </div>
    </section>
  @endforeach

  <form id="composer" method="post" action="/create" class="mag-box composer" style="max-width:36rem;margin-top:2rem"
        :style="pick && 'border-color:var(--primary)'">
    @csrf
    <input type="hidden" name="template" :value="pick ? pick.id : ''">
    <p class="kicker" x-show="!pick">Clique un template au-dessus — puis nomme ton monde ici.</p>
    <p class="kicker" x-show="pick" x-cloak>Template · <span x-text="pick && pick.label"></span> · <span x-text="pick && pick.schema"></span></p>
    <label class="muted">Nom du monde</label>
    <input name="title" x-ref="title" required placeholder="Vera Paris, Lumen Atelier…" style="width:100%;margin:.4rem 0">
    <textarea name="summary" placeholder="Une phrase. Google la lira." :placeholder="pick ? pick.pitch : 'Une phrase. Google la lira.'" style="width:100%;min-height:4.5rem"></textarea>
    <button class="btn" type="submit" style="margin-top:.7rem">Créer <span x-show="pick" x-cloak>— <span x-text="pick && pick.label"></span></span></button>
  </form>
</main>

<div class="pick-bar" x-show="pick" x-cloak>
  <img :src="pick ? '/tpl/'+pick.id+'.svg' : ''" alt="" width="72" height="40">
  <div>
    <strong x-text="pick && pick.label"></strong>
    <p class="muted" x-text="pick && pick.pitch"></p>
  </div>
  <a class="btn" href="#composer">Nommer ↓</a>
</div>
@endsection
