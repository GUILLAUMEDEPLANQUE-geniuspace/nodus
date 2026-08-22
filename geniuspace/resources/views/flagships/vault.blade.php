@extends('layouts.app')
@section('title', 'Acquérir : '.$product->title.' | '.$node->title)
@section('description', $product->summary ?: $node->summary)
@section('canonical', url('/n/'.$node->slug.'/p/'.$product->id))
@section('og_type', 'product')
@section('og_image', url($product->image ?: $node->hero))
@push('jsonld')
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@push('head')
<link rel="stylesheet" href="/css/flagships.css">
@endpush
@section('content')
@php
  $passages = array_merge($neighbors['fait_partie_de'] ?? [], $neighbors['contient'] ?? []);
  $passage = $passages[0] ?? null;
@endphp
<div class="vault" x-data="vaultCanvas()" data-lore="/n/{{ $node->slug }}/preuve-lore" data-pid="{{ $product->id }}">

  <video class="vault-bg" autoplay loop muted playsinline poster="{{ $product->image ?: $node->hero }}">
    <source src="{{ $chrome['theme']->hero_video ?: '/media/atelier.mp4' }}" type="video/mp4">
  </video>

  @if($passage)
    <a class="wormhole" href="{{ $passage['url'] }}" data-visit="{{ $passage['titre'] }}" data-target="">
      <span class="wormhole-tip">{{ $flag['wormhole']['label'] }} · {{ $passage['titre'] }}</span>
    </a>
  @endif

  <div class="vault-stage" :style="stageStyle">
    <div class="vault-frame">
      <img src="{{ $product->image ?: $node->hero }}" alt="{{ $product->title }}" class="vault-art">
      @foreach($hotspots as $h)
        <button type="button" class="hotspot" style="top:{{ $h['top'] }}%; {{ $h['right'] > 40 ? 'right:18%' : 'left:18%' }}" @click="spot = @js($h)">
          <span class="hotspot-card">
            <em>{{ $h['name'] }}</em>
            <span>{{ $h['value'] }}</span>
          </span>
        </button>
      @endforeach
    </div>
  </div>

  <div class="vault-scroll" aria-hidden="true">
    <div class="vault-beat"><p>Le trait originel.<br>Capturé avant l’ère du numérique.</p></div>
    <div class="vault-beat end"><p>Une pièce d’histoire.<br>Sauvée de l’oubli.</p></div>
    <div class="vault-beat center"><p class="gold">Votre héritage matériel.</p></div>
  </div>

  <div class="vault-ui">
    <header class="vault-top">
      <a class="brand" href="/n/{{ $node->slug }}">{{ $node->title }}</a>
      <button type="button" class="btn-lore" @click="lore = !lore">Preuves & origines</button>
    </header>
    <div class="vault-info" :class="scrolled && 'dim'">
      <h1>{{ $product->title }}</h1>
      <p class="price">{{ $product->price }} @if($product->stock)<span>{{ $product->stock }}</span>@endif</p>
      <form method="post" action="/cart" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        @include('partials.order-fields', ['p' => $product])
        <button class="btn vault-buy" type="submit">Acquérir l’œuvre</button>
      </form>
    </div>
  </div>

  @include('partials.ghost-orb', ['node' => $node, 'ghostProduct' => $product])

  <aside class="lore-drawer" :class="lore && 'open'">
    <div class="ld-head">
      <p class="kicker">Origines de l’œuvre</p>
      <button type="button" class="btn-ghost" @click="lore=false">Fermer</button>
    </div>
    <dl class="lore-spec">
      @forelse($fields as $f)
        @if(trim((string)$f->value) !== '')
          <div><dt>{{ $f->name }}</dt><dd>{{ $f->value }}{{ $f->unit ? ' '.$f->unit : '' }}</dd></div>
        @endif
      @empty
        <p class="muted">Les détails se posent ici.</p>
      @endforelse
    </dl>
    @if($also)
      <p class="kicker" style="margin-top:1.2rem">Aussi dans cet univers</p>
      @foreach($also as $a)
        <a class="chip" href="{{ $a['href'] }}">{{ $a['title'] }}</a>
      @endforeach
    @endif
    <div class="proof-box">
      <p>{{ $flag['proof']['label'] }}. Mise : {{ $flag['proof']['stake'] }} pièces. Si c’est tenu, la fiche bouge.</p>
      <form @submit.prevent="stake()">
        <input x-model="stField" placeholder="Champ (épisode, douga…)" required>
        <input x-model="stVal" placeholder="Correction" required>
        <button class="btn-line" type="submit">Proposer une correction</button>
      </form>
      <p class="muted" x-show="stOk" x-text="stOk"></p>
    </div>
  </aside>

  <nav class="smart-dock">
    <a href="/n/{{ $node->slug }}">Explorer</a>
    <a href="/n/{{ $node->slug }}/forum">Salon</a>
    <a href="/n/{{ $node->slug }}/boutique_expert" class="on">Coffre</a>
    <a href="/n/{{ $node->slug }}/carnet">Preuves</a>
  </nav>
</div>

<script>
function vaultCanvas(){
  return {
    chat:false, lore:false, scrolled:false, spot:null,
    stField:'', stVal:'', stOk:'', rot:0, lift:0,
    async stake(){
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const r = await fetch(this.$el.dataset.lore, {
        method:'POST', credentials:'same-origin',
        headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({field:this.stField, value:this.stVal, stake: {{ (int)$flag['proof']['stake'] }}})
      });
      const j = await r.json();
      this.stOk = j.quoi || 'Mise posée.';
    },
    get stageStyle(){ return 'transform: translateY('+this.lift+'px) rotateX('+this.rot+'deg)'; }
  }
}
window.addEventListener('scroll', () => {
  const el = document.querySelector('[x-data="vaultCanvas()"]');
  if (!el || !el._x_dataStack) return;
  const d = el._x_dataStack[0];
  const p = Math.min(1, window.scrollY / (document.body.scrollHeight - window.innerHeight || 1));
  d.scrolled = p > 0.08;
  d.lift = p * -80;
  d.rot = p * 12;
}, {passive:true});
</script>
@endsection
