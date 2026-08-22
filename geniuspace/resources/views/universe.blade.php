@extends('layouts.app')
@section('title', $node->seoTitle())
@section('description', $node->summary)
@section('canonical', url('/n/'.$node->slug))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@graph' => [
    ['@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
      ['@type' => 'ListItem', 'position' => 1, 'name' => 'Geniuspace', 'item' => url('/')],
      isset($parents[0]) ? ['@type' => 'ListItem', 'position' => 2, 'name' => $parents[0]->title, 'item' => url('/n/'.$parents[0]->slug)] : null,
      ['@type' => 'ListItem', 'position' => isset($parents[0]) ? 3 : 2, 'name' => $node->title, 'item' => url('/n/'.$node->slug)],
    ]))],
    ['@type' => $node->kind === 'company' ? 'Organization' : 'CreativeWork', 'name' => $node->title, 'description' => $node->summary, 'url' => url('/n/'.$node->slug)],
  ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div x-data="{ tab: '{{ $node->skin === 'vera' ? 'maison' : 'vivre' }}' }">
<section class="hero" style="min-height:70dvh">
    <img class="bg" src="{{ $node->hero }}" alt="">
    <div class="veil"></div>
    <div class="copy wrap">
        @if($parents->first())
            <a class="kicker" href="/n/{{ $parents->first()->slug }}">Univers parent · {{ $parents->first()->title }}</a>
        @else
            <p class="kicker">{{ $node->skin === 'vera' ? 'Maison' : 'Lieu de vie' }} · {{ $node->kind }}</p>
        @endif
        <h1>{{ $node->title }}</h1>
        <p>{{ $node->subtitle ?: $node->summary }}</p>
    </div>
</section>

<div class="wrap">
    <div class="panel" :class="tab==='vivre' || tab==='maison' ? 'active' : ''">
        <p style="max-width:40rem;font-size:1.1rem">{{ $node->summary }}</p>
        @if($children->count())
            <h2 class="font-display" style="font-size:2rem">{{ $node->skin === 'vera' ? 'Offres' : 'Personnages / enfants' }}</h2>
            <div class="rel">
                @foreach($children as $c)
                    <a class="chip" href="/n/{{ $c->slug }}">{{ $c->title }}</a>
                @endforeach
            </div>
        @endif
        @if($node->wiki->count())
            <h2 class="font-display" style="font-size:2rem">Wiki</h2>
            @foreach($node->wiki as $w)
                <article class="card" style="padding:1.25rem;margin:0.5rem 0">
                    <h3 class="font-display">{{ $w->title }}</h3>
                    <p class="muted">{{ $w->body }}</p>
                </article>
            @endforeach
        @endif
    </div>

    <div class="panel" :class="tab==='forum' ? 'active' : ''">
        @forelse($node->threads as $t)
            <a class="forum-card" href="/n/{{ $node->slug }}/t/{{ $t->id }}">
                <img src="{{ $t->cover ?: $node->hero }}" alt="">
                <div class="veil"></div>
                <div class="copy">
                    <p class="kicker">{{ $t->author }}</p>
                    <h2 class="font-display" style="font-size:2.4rem;margin:0">{{ $t->title }}</h2>
                    <p>{{ $t->body }}</p>
                </div>
            </a>
        @empty
            <p class="muted">Pas encore de sujet.</p>
        @endforelse
    </div>

    <div class="panel" :class="tab==='boutique' ? 'active' : ''">
        <div class="grid-3">
            @foreach($node->products as $p)
                <a class="card" href="/n/{{ $node->slug }}/p/{{ $p->id }}">
                    <img src="{{ $p->image }}" alt="">
                    <div class="pad">
                        @if($p->rwa)<p class="kicker">RWA</p>@endif
                        <h3 class="font-display">{{ $p->title }}</h3>
                        <p class="primary">{{ $p->price }}</p>
                        <p class="muted" style="font-size:0.85rem">{{ $p->rating }}/5 · {{ $p->votes }} avis</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <div class="panel" :class="tab==='videos' ? 'active' : ''">
        @foreach($node->media as $m)
            <a class="card" href="/n/{{ $node->slug }}/v/{{ $m->id }}" style="display:flex;gap:1rem;padding:1rem;margin:0.5rem 0">
                <div>
                    <p class="kicker">{{ $m->mode }} · {{ $m->access }}</p>
                    <h3 class="font-display">{{ $m->title }}</h3>
                    <p class="muted">{{ $m->duration }} {{ $m->price }}</p>
                </div>
            </a>
        @endforeach
    </div>

    <div class="panel" :class="tab==='epreuve' ? 'active' : ''">
        @forelse($node->quests as $q)
            <article class="step">
                <p class="kicker">Étape {{ $q->step }} · {{ $q->skill }}</p>
                <h3 class="font-display">{{ $q->title }}</h3>
                <p>{{ $q->prompt }}</p>
                <div class="rel" style="margin-top:0.6rem">
                    <span class="chip">{{ $q->option_a }}</span>
                    <span class="chip">{{ $q->option_b }}</span>
                </div>
            </article>
        @empty
            <p class="muted">Pas d'épreuve sur ce Node.</p>
        @endforelse
    </div>
</div>

<nav class="dock">
    @if($node->skin === 'vera')
        <button type="button" :class="tab==='maison' && 'active'" @click="tab='maison'">Maison</button>
        <button type="button" :class="tab==='epreuve' && 'active'" @click="tab='epreuve'">Quêtes</button>
        <button type="button" :class="tab==='videos' && 'active'" @click="tab='videos'">Vidéos</button>
        <button type="button" :class="tab==='forum' && 'active'" @click="tab='forum'">Forum</button>
    @else
        <button type="button" :class="tab==='vivre' && 'active'" @click="tab='vivre'">Univers</button>
        <button type="button" :class="tab==='forum' && 'active'" @click="tab='forum'">Forum</button>
        <button type="button" :class="tab==='videos' && 'active'" @click="tab='videos'">Vidéos</button>
        <button type="button" :class="tab==='boutique' && 'active'" @click="tab='boutique'">Boutique</button>
    @endif
</nav>
</div>
@endsection
