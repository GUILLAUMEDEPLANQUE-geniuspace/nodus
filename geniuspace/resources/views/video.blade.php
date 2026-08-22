@extends('layouts.app')
@section('title', $media->title.' | '.$node->title)
@section('description', $media->transcript)
@section('canonical', url('/n/'.$node->slug.'/v/'.\Illuminate\Support\Str::slug($media->title)))
@php
  $gated = \App\Support\Grantor::isGated($media);
  $ld = [
    '@'.'context' => 'https://schema.org',
    '@'.'type' => 'VideoObject',
    'name' => $media->title,
    'description' => $media->transcript,
    'thumbnailUrl' => url($node->hero),
    'author' => ['@'.'type' => 'Person', 'name' => $media->author_name ?: 'Créateur'],
    'duration' => 'PT'.($media->duration ?: '0M'),
    'embedUrl' => url('/embed/'.$node->slug),
    'hasPart' => \App\Support\Chapters::clips($chapters ?? \App\Support\Chapters::parse($media->chapters), url('/n/'.$node->slug.'/v/'.$media->id)),
  ];
  if (! $gated) {
    $ld['contentUrl'] = url('/'.ltrim($media->path, '/'));
  }
@endphp
@push('jsonld')
<script type="application/ld+json">
{!! json_encode($ld, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
@section('content')
@php
  $chrome = $chrome ?? \App\Support\Chrome::bag($node);
  $theme = $chrome['theme'];
  $pw = $chrome['playerPaywall'];
  $pwExtra = $pw ? \App\Support\Chrome::extra($pw) : [];
  $play = $chrome['playerOverlay']->first();
  $poster = $theme->poster ?: $node->hero;
  $unlockUrl = '/n/'.$node->slug.'/v/'.$media->id.'/unlock';
  $dropUrl = '/n/'.$node->slug.'/v/'.$media->id.'/drop';
@endphp
<div class="cockpit" x-data="playerMaison()" x-init="boot()">
  <div>
    <div class="kicker wrap" style="padding:0.6rem 1rem">
      {{ $media->mode === 'interview' ? 'Épreuve' : ($media->mode === 'shop' ? 'Making-of' : 'Film') }}
      · {{ $granted ? 'Ouvert' : 'Teaser' }}
      · {{ $media->views }} vues
    </div>
    <a href="/profil" class="wrap" style="display:flex;align-items:center;gap:.75rem;padding:.4rem 1rem 0.8rem">
      <img src="{{ $media->author_avatar ?: \App\Support\Faces::of($media->author_name ?: 'Créateur') }}" alt="" style="width:2.6rem;height:2.6rem;border-radius:999px;object-fit:cover;border:1px solid var(--primary)">
      <div>
        <strong>{{ $media->author_name ?: 'Créateur' }}</strong>
        <p class="muted" style="margin:0;font-size:.8rem">{{ $media->author_role ?: 'Auteur' }} · chaîne du lieu</p>
      </div>
    </a>
    <div class="video-box" style="margin:0 0.75rem;border-radius:1rem;overflow:hidden;border:1px solid var(--border);position:relative">
      <video id="v" src="{{ $src }}" poster="{{ $poster }}" playsinline
        :style="(!granted && teaser && t>=teaser) ? 'filter:blur(8px)' : ''"
        @timeupdate="onTime($event)"
        @play="playing=true" @pause="playing=false"></video>
      <button class="btn" type="button" x-show="!playing && !( !granted && teaser && t>=teaser )"
        style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%)"
        @click="document.getElementById('v').play()">{{ $play->label ?? 'Lecture' }}</button>
      <div class="paywall" x-show="!granted && teaser && t>=teaser" x-cloak>
        <div class="card" style="padding:1.5rem;text-align:center;max-width:20rem">
          <h2 class="font-display">{{ $pwExtra['paywall_title'] ?? 'Suite du lieu' }}</h2>
          <p class="muted">{{ $pwExtra['paywall_body'] ?? 'Le teaser pose le geste. La suite s’ouvre ici — et la preuve voyage avec vous.' }}</p>
          <button class="btn" type="button" @click="unlock()">{{ $pw->label ?? 'Continuer' }} {{ $media->price }}</button>
        </div>
      </div>
      <div x-show="activeDoor" x-cloak class="card" style="position:absolute;left:1rem;bottom:1rem;padding:.7rem 1rem;max-width:16rem;background:var(--surface)">
        <p class="kicker" x-text="activeDoor && activeDoor.kind==='drop' ? 'Relique' : 'Porte'"></p>
        <p style="margin:.2rem 0" x-text="activeDoor && activeDoor.label"></p>
        <a class="chip" x-show="activeDoor && activeDoor.kind==='door' && activeDoor.href" :href="activeDoor && activeDoor.href">Ouvrir le lieu</a>
        <button class="chip" type="button" x-show="activeDoor && activeDoor.kind==='drop'" @click="drop()">Prendre</button>
      </div>
    </div>
    <p class="wrap" style="padding:.5rem 1rem 0" x-show="preuve" x-cloak>
      <span class="chip" x-text="preuve && preuve.quoi"></span>
      <a class="chip" href="/n/{{ $node->slug }}/carnet">Voir le carnet</a>
    </p>
    <div class="wrap" style="padding:0.75rem 1rem">
      <a class="kicker" href="/n/{{ $node->slug }}/videos">{{ $node->title }}</a>
      <h1 class="font-display" style="font-size:2.2rem;margin:0.2rem 0">{{ $media->title }}</h1>
      <p class="stars">★★★★★ {{ $media->rating }}/5 · {{ $media->duration }}</p>
      <p>{{ $media->transcript }}</p>
      <div class="rel" style="margin-top:0.6rem">
        @foreach($chrome['playerBar'] as $a)
          @if($a->action_key === 'share')
            <a class="chip" href="{{ \App\Support\Chrome::href($node, $a) }}" target="_blank" rel="noopener">{{ $a->label }}</a>
          @else
            <button class="chip" type="button" @click="panel='{{ $a->action_key }}'">{{ $a->label }}</button>
          @endif
        @endforeach
        <a class="chip" href="/embed/{{ $node->slug }}">Intégrer le lieu</a>
      </div>
      <div x-show="panel==='desc'" style="margin-top:1rem">
        <pre class="muted" style="white-space:pre-wrap;font-family:inherit">{{ $media->chapters }}</pre>
      </div>
      <div x-show="panel==='chap'" style="margin-top:1rem">
        @foreach($chapters as $c)
          <button class="chip" type="button" @click="document.getElementById('v').currentTime={{ $c['startOffset'] }};document.getElementById('v').play()">{{ $c['name'] }}</button>
          @if(!empty($c['href']))
            <a class="chip" href="{{ $c['href'] }}">Ouvrir · {{ $c['name'] }}</a>
          @endif
        @endforeach
      </div>
      <div x-show="panel==='graph'" style="margin-top:1rem">
        <p class="muted" style="margin:0 0 .4rem">Lieux ouverts par ce film.</p>
        <div class="rel">
          @foreach($doors as $d)
            @if(($d['kind'] ?? '') === 'door' && !empty($d['href']))
              <a class="chip" href="{{ $d['href'] }}">{{ $d['label'] }}</a>
            @endif
          @endforeach
          @foreach($children as $c)
            <a class="chip" href="{{ \App\Support\Engine::href($c) }}">{{ $c->title }}</a>
          @endforeach
          <a class="chip" href="/n/{{ $node->slug }}">{{ $node->title }}</a>
        </div>
      </div>
      <div x-show="panel==='drive'" style="margin-top:1rem">
        @forelse($files as $f)
          @php $open = \App\Support\Grantor::canSeeFile($f); @endphp
          <p>
            {{ $open ? '📄' : '🔒' }} {{ $f->title }}
            @if($open)
              <a class="chip" href="{{ \App\Support\Grantor::fileHref($f) }}">ouvrir</a>
            @else
              <span class="muted">se mérite</span>
            @endif
          </p>
        @empty
          <p class="muted">Pas de relique liée.</p>
        @endforelse
      </div>
      <div x-show="panel==='shop'" style="margin-top:1rem">
        @foreach($node->products as $p)
          <article class="card" style="padding:1rem;margin:.4rem 0">
            <p><a href="/n/{{ $node->slug }}/p/{{ $p->id }}">{{ $p->title }}</a> · {{ $p->price }} · {{ $p->rating }}/5</p>
            @include('partials.shop-actions', ['p'=>$p,'node'=>$node,'chrome'=>['shopCardActions'=>$chrome['playerShop']]])
          </article>
        @endforeach
      </div>
    </div>
  </div>
  <aside class="rail">
    <p class="kicker">À suivre</p>
    @forelse($related as $r)
      <a class="card" href="/n/{{ $node->slug }}/v/{{ $r->id }}" style="display:block;padding:0.6rem;margin:0.4rem 0">
        <p class="kicker">{{ $r->mode === 'interview' ? 'Épreuve' : 'Film' }}</p>
        <strong>{{ $r->title }}</strong>
        <p class="muted" style="font-size:0.8rem">{{ $r->duration }}</p>
      </a>
    @empty
      <p class="muted">Une seule fiche sur ce lieu.</p>
    @endforelse
    <p class="kicker" style="margin-top:1rem">Carnet</p>
    <p class="muted">Chaque déblocage s’écrit ici. <a href="/n/{{ $node->slug }}/carnet">Ouvrir</a></p>
    <p class="kicker" style="margin-top:1rem">Intégrer</p>
    <code style="font-size:.7rem;display:block;white-space:pre-wrap;color:var(--muted)"><iframe src="{{ url('/embed/'.$node->slug) }}" width="360" height="520" style="border:0;border-radius:12px"></iframe></code>
  </aside>
</div>
<script>
function playerMaison(){
  return {
    t: 0,
    granted: {{ $granted ? 'true' : 'false' }},
    teaser: {{ (int) $media->teaser_sec }},
    playing: false,
    panel: '{{ optional($chrome['playerBar']->first())->action_key ?: 'desc' }}',
    doors: @json($doors),
    activeDoor: null,
    preuve: null,
    unlockUrl: @json($unlockUrl),
    dropUrl: @json($dropUrl),
    csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
    boot(){},
    onTime(e){
      this.t = e.target.currentTime;
      if(!this.granted && this.teaser && this.t >= this.teaser){ e.target.pause(); }
      let hit = null;
      for (const d of this.doors){
        if (this.t >= d.at && this.t <= d.at + 4) { hit = d; break; }
      }
      this.activeDoor = hit;
    },
    async unlock(){
      const r = await fetch(this.unlockUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      const d = await r.json();
      this.granted = true;
      this.preuve = d.preuve || { quoi: 'Suite ouverte' };
      const v = document.getElementById('v');
      const t = v.currentTime;
      if (d.src) { v.src = d.src; v.currentTime = t; }
      v.play();
    },
    async drop(){
      const r = await fetch(this.dropUrl, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        body: JSON.stringify({ at: Math.round(this.t) })
      });
      const d = await r.json();
      this.preuve = { quoi: d.quoi || 'Relique posée' };
      this.activeDoor = null;
    }
  }
}
</script>
@endsection
