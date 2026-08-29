@extends('layouts.app')
@section('title', 'Créer un univers — Nodus')
@section('description', 'Cinq germes pour partir. Dix flagships et une cinquantaine de démarrages restent disponibles.')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem" x-data="{
  g: 'Germes',
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
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.4rem);line-height:.95">Cinq germes.<br>Puis tout le catalogue.</h1>
  <p class="lede">Tu nommes un univers, tu poses les fiches, l’hôte lit seulement ce coffre. Les <strong>cinq germes</strong> suffisent pour la première heure. En dessous : les 10 flagships (hôte, boucle, passage, preuve) et ~50 démarrages métier. Rien n’est retiré. <a href="/flagships">Bible flagships →</a></p>

  <div class="rel" style="margin:1rem 0;flex-wrap:wrap">
    <button type="button" class="chip primary" @click="g='Germes'" :class="g==='Germes' && 'primary'">Germes</button>
    <button type="button" class="chip" @click="g='Tous'" :class="g==='Tous' && 'primary'">Tous</button>
    @foreach(array_keys($groups) as $name)
      <button type="button" class="chip" @click="g=@js($name)" :class="g===@js($name) && 'primary'">{{ $name }}</button>
    @endforeach
  </div>

  <section x-show="g==='Germes'" style="margin:1.5rem 0">
    <p class="kicker">Première heure</p>
    <div class="tpl-grid">
      @foreach($germs as $t)
        <button type="button" class="tpl flag" style="--tpl:{{ $t['primary'] }}"
          @click="choose({id:@js($t['id']), label:@js($t['label']), pitch:@js($t['innovation'] ?? $t['pitch']), schema:@js($t['schema'])})"
          :class="pick && pick.id===@js($t['id']) && 'on'">
          <img src="/tpl/{{ $t['id'] }}.svg" alt="{{ $t['label'] }}" width="320" height="180">
          <strong>{{ $t['label'] }}</strong>
          <em>{{ $t['innovation'] ?? $t['pitch'] }}</em>
          <small>{{ $t['schema'] }} · {{ count($t['rooms']) }} salles</small>
        </button>
      @endforeach
    </div>
  </section>

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
    <p class="kicker" x-show="!pick">Choisis un germe ou un template — puis nomme ton monde.</p>
    <p class="kicker" x-show="pick" x-cloak>Template · <span x-text="pick && pick.label"></span> · <span x-text="pick && pick.schema"></span></p>
    <label class="muted">Nom du monde</label>
    <input name="title" x-ref="title" required placeholder="Atelier Clamp, Terrain Midgar…" style="width:100%;margin:.4rem 0">
    <textarea name="summary" placeholder="Une phrase. L’hôte et Google la liront." :placeholder="pick ? pick.pitch : 'Une phrase. L’hôte et Google la liront.'" style="width:100%;min-height:4.5rem"></textarea>
    <p class="muted" style="margin:.6rem 0 0">Après création tu atterris sur l’habillage. L’hôte lira seulement ce que tu poses.</p>
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
