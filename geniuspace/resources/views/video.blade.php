@extends('layouts.app')
@section('title', $media->title.' | '.$node->title)
@section('description', $media->transcript)
@section('canonical', url('/n/'.$node->slug.'/v/'.$media->id))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@'.'context' => 'https://schema.org',
  '@'.'type' => 'VideoObject',
  'name' => $media->title,
  'description' => $media->transcript,
  'thumbnailUrl' => url($node->hero),
  'author' => ['@'.'type' => 'Person', 'name' => $media->author_name ?: 'Créateur'],
  'duration' => 'PT'.($media->duration ?: '0M'),
  'embedUrl' => url('/n/'.$node->slug.'/v/'.$media->id),
  'contentUrl' => url('/'.$media->path),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
@section('content')
@php
  $chapters = collect(preg_split('/\n+/', $media->chapters))->filter();
@endphp
<div class="cockpit" x-data="{
  t: 0, granted: {{ $media->access==='free' ? 'true' : 'false' }},
  teaser: {{ (int) $media->teaser_sec }}, playing: false, panel: 'desc', drop: false
}">
  <div>
    <div class="kicker wrap" style="padding:0.6rem 1rem">
      {{ strtoupper($media->mode) }} · {{ $media->access }}
      · {{ $media->views }} vues · {{ $media->rating }}/5
      @if($node->products->first())
        · {{ $node->products->first()->price }}
      @endif
    </div>
    <a href="/profil" class="wrap" style="display:flex;align-items:center;gap:.75rem;padding:.4rem 1rem 0.8rem">
      <img src="{{ $media->author_avatar ?: \App\Support\Faces::of($media->author_name ?: 'Créateur') }}" alt="" style="width:2.6rem;height:2.6rem;border-radius:999px;object-fit:cover;border:1px solid var(--primary)">
      <div>
        <strong>{{ $media->author_name ?: 'Créateur' }}</strong>
        <p class="muted" style="margin:0;font-size:.8rem">{{ $media->author_role ?: 'Auteur' }} · chaîne du Node</p>
      </div>
    </a>
    <div class="video-box" style="margin:0 0.75rem;border-radius:1rem;overflow:hidden;border:1px solid var(--border)">
      <video id="v" src="{{ $src }}" poster="{{ $node->hero }}" playsinline
        :style="(!granted && teaser && t>=teaser) ? 'filter:blur(8px)' : ''"
        @timeupdate="t=$event.target.currentTime; if(!granted && teaser && t>=teaser){$event.target.pause()}; drop = granted && t>=2 && t<=6"
        @play="playing=true" @pause="playing=false"></video>
      <button class="btn" type="button" x-show="!playing && !( !granted && teaser && t>=teaser )"
        style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%)"
        @click="document.getElementById('v').play()">Lecture</button>
      <div class="paywall" x-show="!granted && teaser && t>=teaser">
        <div class="card" style="padding:1.5rem;text-align:center;max-width:20rem">
          <h2 class="font-display">Suite premium</h2>
          <p class="muted">Teaser {{ $media->teaser_sec }}s. Fichiers Drive lockés jusqu'au déblocage.</p>
          <button class="btn" type="button" @click="granted=true; document.getElementById('v').play()">Débloquer {{ $media->price }}</button>
        </div>
      </div>
      <button type="button" class="orb pri" x-show="drop" style="position:absolute;top:20%;right:18%" @click="drop=false">+</button>
    </div>
    <div class="wrap" style="padding:0.75rem 1rem">
      <a class="kicker" href="/n/{{ $node->slug }}?tab=videos">{{ $node->title }}</a>
      <h1 class="font-display" style="font-size:2.2rem;margin:0.2rem 0">{{ $media->title }}</h1>
      <p class="stars">★★★★★ {{ $media->rating }}/5 · {{ $media->duration }}</p>
      <p>{{ $media->transcript }}</p>
      <div class="rel" style="margin-top:0.6rem">
        <button class="chip" type="button" @click="panel='desc'">Contexte</button>
        <button class="chip" type="button" @click="panel='chap'">Chapitres</button>
        <button class="chip" type="button" @click="panel='graph'">Connexions</button>
        <button class="chip" type="button" @click="panel='drive'">Drive</button>
        <button class="chip" type="button" @click="panel='shop'">Boutique</button>
        <a class="chip" href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}">Partager</a>
      </div>
      <div x-show="panel==='desc'" style="margin-top:1rem">
        <pre class="muted" style="white-space:pre-wrap;font-family:inherit">{{ $media->chapters }}</pre>
      </div>
      <div x-show="panel==='chap'" style="margin-top:1rem">
        @foreach($chapters as $c)
          <p class="chip">{{ $c }}</p>
        @endforeach
      </div>
      <div x-show="panel==='graph'" style="margin-top:1rem">
        <div class="rel">
          @foreach($children as $c)
            <a class="chip" href="/n/{{ $c->slug }}">{{ $c->title }}</a>
          @endforeach
          <a class="chip" href="/n/{{ $node->slug }}">{{ $node->title }}</a>
        </div>
      </div>
      <div x-show="panel==='drive'" style="margin-top:1rem">
        @forelse($files as $f)
          <p>{{ $f->locked ? '🔒 locké jusqu\'à l\'achat' : '📄' }} {{ $f->title }}</p>
        @empty
          <p class="muted">Pas de relique liée.</p>
        @endforelse
      </div>
      <div x-show="panel==='shop'" style="margin-top:1rem">
        @foreach($node->products as $p)
          <p><a href="/n/{{ $node->slug }}/p/{{ $p->id }}">{{ $p->title }}</a> · {{ $p->price }} · {{ $p->rating }}/5</p>
        @endforeach
      </div>
    </div>
  </div>
  <aside class="rail">
    <p class="kicker">À suivre</p>
    @forelse($related as $r)
      <a class="card" href="/n/{{ $node->slug }}/v/{{ $r->id }}" style="display:block;padding:0.6rem;margin:0.4rem 0">
        <p class="kicker">{{ $r->mode }}</p>
        <strong>{{ $r->title }}</strong>
        <p class="muted" style="font-size:0.8rem">{{ $r->duration }}</p>
      </a>
    @empty
      <p class="muted">Une seule fiche sur ce Node.</p>
    @endforelse
    <p class="kicker" style="margin-top:1rem">Playlists</p>
    <p class="muted">Ajoutez cette fiche à une playlist (Drive).</p>
  </aside>
</div>
@endsection
