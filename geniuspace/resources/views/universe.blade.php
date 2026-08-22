@extends('layouts.app')
@section('title', ($seoRow->title ?? null) ?: $node->seoTitle())
@section('description', ($seoRow->description ?? null) ?: $node->summary)
@section('canonical', url('/n/'.$node->slug))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@'.'context' => 'https://schema.org',
  '@'.'graph' => [
    ['@'.'type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
      ['@'.'type' => 'ListItem', 'position' => 1, 'name' => 'Geniuspace', 'item' => url('/')],
      isset($parents[0]) ? ['@'.'type' => 'ListItem', 'position' => 2, 'name' => $parents[0]->title, 'item' => url('/n/'.$parents[0]->slug)] : null,
      ['@'.'type' => 'ListItem', 'position' => isset($parents[0]) ? 3 : 2, 'name' => $node->title, 'item' => url('/n/'.$node->slug)],
    ]))],
    ['@'.'type' => $node->kind === 'company' ? 'Organization' : 'CreativeWork', 'name' => $node->title, 'description' => $node->summary, 'url' => url('/n/'.$node->slug)],
  ],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
@php
  $forumThreads = $node->threads->where('kind', 'forum')->values();
  $journal = $node->threads->where('kind', 'blog')->values();
  $living = $node->skin !== 'vera';
  $defaultTab = $tab ?: ($living ? 'vivre' : 'maison');
@endphp
<div x-data="{
  tab: @js($defaultTab),
  openId: @js($tid),
  mode: @js($mode ?: 'legacy'),
  bubbles: [],
  ping(name) {
    const id = Date.now();
    this.bubbles.push({ id, name });
    setTimeout(() => { this.bubbles = this.bubbles.filter(b => b.id !== id) }, 3200);
  }
}" class="pb-28">

@if($living)
{{-- ========== LIVING WORLD ========== --}}
<template x-if="tab === 'forum'">
<div>
  <div class="forum-split" :class="openId && 'is-open'">
    <div class="snap">
      @foreach($forumThreads as $t)
        <article class="snap-card">
          <img src="{{ $t->cover ?: $node->hero }}" alt="">
          <div class="veil"></div>
          <div class="relative z-10 wrap" style="display:flex;justify-content:space-between;align-items:flex-end;gap:1rem;width:100%">
            <div style="max-width:36rem">
              <p class="kicker" style="display:flex;align-items:center;gap:.5rem">
                <img src="{{ $t->author_avatar ?: \App\Support\Faces::of($t->author) }}" alt="" style="width:1.6rem;height:1.6rem;border-radius:999px;object-fit:cover">
                Sujet · {{ $t->author }}
              </p>
              <h2 class="font-display" style="font-size:clamp(2rem,6vw,3.4rem);margin:0.3rem 0;line-height:0.95">{{ $t->title }}</h2>
              <p>{{ $t->body }}</p>
              <p class="muted" style="font-size:0.8rem">{{ $t->views }} vues · {{ $t->fires }} feux · {{ $t->replies_count }} réponses</p>
            </div>
            <div class="side-btns">
              <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/fire"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="orb">🔥 {{ $t->fires }}</button></form>
              <button type="button" class="orb pri" @click="openId='{{ $t->id }}'">💬 {{ $t->replies_count }}</button>
              <a class="orb" href="/n/{{ $node->slug }}/t/{{ $t->id }}" aria-label="Fiche SEO">↗</a>
              <a class="orb" href="/studio/image?src={{ urlencode($t->cover ?: $node->hero) }}&target=thread&id={{ $t->id }}&slug={{ $node->slug }}">✎</a>
            </div>
          </div>
        </article>
      @endforeach
      <div class="snap-card" style="background:var(--surface);align-items:center">
        <form method="post" action="/forum" class="wrap" style="max-width:32rem;position:relative;z-index:2">
          @csrf
          <input type="hidden" name="slug" value="{{ $node->slug }}">
          <p class="kicker">Nouveau sujet — indexé Google</p>
          <input name="title" required placeholder="Titre du débat" style="width:100%;margin:0.5rem 0">
          <select name="category" style="width:100%;margin-bottom:.4rem">
            <option value="">Catégorie</option>
            <option>Lore</option>
            <option>Théories</option>
            <option>Quêtes</option>
          </select>
          <textarea name="body" required placeholder="Accroche Legacy" style="width:100%;min-height:6rem"></textarea>
          <button class="btn" type="submit" style="margin-top:0.75rem">Publier le sujet</button>
        </form>
      </div>
    </div>
    <aside class="dive" x-show="openId" x-cloak>
      <header style="display:flex;justify-content:space-between;padding:0.75rem 1rem;border-bottom:1px solid var(--border)">
        <div>
          <button type="button" :class="mode==='legacy' && 'primary'" @click="mode='legacy'">Top (SEO)</button>
          <button type="button" :class="mode==='live' && 'primary'" @click="mode='live'" class="btn-ghost">Live</button>
        </div>
        <button type="button" class="btn-ghost" @click="openId=null">Fermer</button>
      </header>
      <div style="flex:1;overflow:auto;padding:1rem">
        @foreach($forumThreads as $t)
          <div x-show="openId==='{{ $t->id }}' && mode==='legacy'">
            @forelse($replies->get($t->id, collect()) as $r)
              <article class="legacy-card">
                <p class="kicker" style="display:flex;align-items:center;gap:.4rem">
                  <img src="{{ $r->author_avatar ?: \App\Support\Faces::of($r->author) }}" alt="" style="width:1.4rem;height:1.4rem;border-radius:999px;object-fit:cover">
                  {{ $r->author }} · {{ $r->votes }} votes
                </p>
                <p>{{ $r->body }}</p>
              </article>
            @empty
              <p class="muted">Pas encore de réponse indexable.</p>
            @endforelse
            <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/reply" @submit="ping('Toi')">
              @csrf
              <input name="body" required placeholder="Réponse Legacy (Google indexe)" style="width:100%;margin-top:0.5rem">
            </form>
          </div>
          <div x-show="openId==='{{ $t->id }}' && mode==='live'">
            @foreach($live->get($t->id, collect()) as $l)
              <div class="live-row">
                <img src="{{ \App\Support\Faces::of($l->author) }}" alt="" style="width:2rem;height:2rem;border-radius:999px;object-fit:cover">
                <p class="bubble"><span class="primary" style="font-size:0.75rem">{{ $l->author }}</span> {{ $l->body }}</p>
                <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/echo">
                  @csrf
                  <input type="hidden" name="live_id" value="{{ $l->id }}">
                  <button class="chip" type="submit">Écho → Legacy</button>
                </form>
              </div>
            @endforeach
            <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/live" @submit="ping('Toi')">
              @csrf
              <input name="body" required placeholder="Live…" style="width:100%;margin-top:0.5rem">
            </form>
          </div>
        @endforeach
      </div>
    </aside>
  </div>
</div>
</template>

<template x-if="tab !== 'forum'">
<div>
<section class="hero" style="min-height:70dvh">
    <img class="bg" src="{{ $node->hero }}" alt="">
    <div class="veil"></div>
    <div class="copy wrap">
        @if($parents->first())
            <a class="kicker" href="/n/{{ $parents->first()->slug }}">Univers parent · {{ $parents->first()->title }}</a>
        @else
            <p class="kicker">Lieu de vie · {{ $node->kind }}</p>
        @endif
        <h1>{{ $node->title }}</h1>
        <p>{{ $node->subtitle ?: $node->summary }}</p>
        <div style="margin-top:1.1rem;display:flex;gap:0.5rem;flex-wrap:wrap">
            <button class="btn" type="button" @click="tab='personnages'">Rejoindre l'équipage</button>
            <button class="btn-line" type="button" @click="tab='guilde'">Entrer dans la guilde</button>
            <a class="btn-line" href="/studio/image?src={{ urlencode($node->hero) }}&target=hero&slug={{ $node->slug }}">Éditer le héros</a>
            <a class="btn-ghost" href="/n/{{ $node->slug }}/studio">Studio</a>
            <a class="btn-ghost" href="/atelier/{{ $node->slug }}">Modifier le club</a>
        </div>
    </div>
</section>

<div class="wrap" style="padding-top:2rem">
    <div x-show="tab==='vivre'">
        <p style="max-width:40rem;font-size:1.1rem">{{ $node->summary }}</p>
        @if($node->body)<p class="muted" style="max-width:40rem">{{ $node->body }}</p>@endif
        @isset($cck)
          @if($cck->count())
            <p class="kicker">CCK</p>
            @foreach($cck as $f)
              @include('partials.cck-render', ['f' => $f])
            @endforeach
          @endif
        @endisset
        @if($children->count())
            <h2 class="font-display" style="font-size:2rem">Âmes liées</h2>
            <div class="grid-3">
                @foreach($children as $c)
                    <a class="card" href="/n/{{ $c->slug }}">
                        <img src="{{ $c->hero }}" alt="{{ $c->title }}">
                        <div class="pad">
                            <p class="kicker">{{ $c->kind }}</p>
                            <h3 class="font-display">{{ $c->title }}</h3>
                            <p class="muted">{{ $c->subtitle }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <div x-show="tab==='personnages'">
        <h2 class="font-display" style="font-size:2.4rem">Personnages</h2>
        <div class="grid-3">
            @forelse($children as $c)
                <a class="card" href="/n/{{ $c->slug }}">
                    <img src="{{ $c->hero }}" alt="">
                    <div class="pad">
                        <p class="kicker">{{ $c->kind }} · enfant du graphe</p>
                        <h3 class="font-display">{{ $c->title }}</h3>
                    </div>
                </a>
            @empty
                <p class="muted">Pas encore d'enfant. Reliez un nœud.</p>
            @endforelse
        </div>
        @if($parents->count())
            <p class="kicker" style="margin-top:1.5rem">Parents</p>
            <div class="rel">
                @foreach($parents as $p)
                    <a class="chip" href="/n/{{ $p->slug }}">{{ $p->title }}</a>
                @endforeach
            </div>
        @endif
    </div>

    <div x-show="tab==='journal'">
        <h2 class="font-display" style="font-size:2.4rem">Journal</h2>
        @forelse($journal as $j)
            <article class="card" style="padding:1.25rem;margin:0.75rem 0">
                <p class="kicker">{{ $j->author }} · {{ $j->views }} lectures</p>
                <h3 class="font-display" style="font-size:1.8rem">{{ $j->title }}</h3>
                <p>{{ $j->body }}</p>
            </article>
        @empty
            <p class="muted">Pas encore de chronique.</p>
        @endforelse
    </div>

    <div x-show="tab==='guilde'">
        <h2 class="font-display" style="font-size:2.4rem">Guilde</h2>
        <p class="muted">Canal Telegram-like. Une bulle monte dans le dock à chaque post.</p>
        <div class="card" style="padding:1rem;max-width:36rem">
            @foreach($guild as $g)
                <div class="live-row">
                    <span class="av">{{ mb_substr($g->author,0,2) }}</span>
                    <p class="bubble"><span class="primary" style="font-size:0.75rem">{{ $g->author }}</span> {{ $g->body }}</p>
                </div>
            @endforeach
            <form method="post" action="/n/{{ $node->slug }}/guilde" @submit="ping('Toi')">
                @csrf
                <input name="body" required placeholder="Message de guilde…" style="width:100%;margin-top:0.5rem">
            </form>
        </div>
    </div>

    <div x-show="tab==='guides'">
        <h2 class="font-display" style="font-size:2.4rem">Guides / wiki</h2>
        @forelse($node->wiki as $w)
            <article class="card" style="padding:1.25rem;margin:0.5rem 0">
                <h3 class="font-display">{{ $w->title }}</h3>
                <p class="muted">{{ $w->body }}</p>
            </article>
        @empty
            <p class="muted">Wiki vide.</p>
        @endforelse
    </div>

    <div x-show="tab==='boutique'">
        <h2 class="font-display" style="font-size:2.4rem">Boutique</h2>
        <div class="grid-3">
            @forelse($node->products as $p)
                <article class="card">
                    <img src="{{ $p->image }}" alt="">
                    <div class="pad">
                        @if($p->rwa)<p class="kicker">RWA</p>@else<p class="kicker">{{ $p->kind }}</p>@endif
                        <h3 class="font-display">{{ $p->title }}</h3>
                        <p class="primary" style="font-size:1.6rem;font-family:var(--display)">{{ $p->price }}</p>
                        <p class="stars">★★★★★ {{ $p->rating }}/5 · {{ $p->votes }} avis</p>
                        <p class="muted" style="font-size:0.8rem">{{ $p->stock }}</p>
                        <div style="display:flex;gap:0.4rem;margin-top:0.7rem;flex-wrap:wrap">
                            <form method="post" action="/cart">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $p->id }}">
                                <button class="btn" type="submit">Panier</button>
                            </form>
                            <a class="btn-line" href="/n/{{ $node->slug }}/p/{{ $p->id }}">Fiche</a>
                            <a class="btn-ghost" href="/studio/image?src={{ urlencode($p->image) }}&target=product&id={{ $p->id }}&slug={{ $node->slug }}">Éditer image</a>
                            <a class="btn-ghost" href="https://twitter.com/intent/tweet?text={{ urlencode($p->title.' '.$p->price) }}&url={{ urlencode(url('/n/'.$node->slug.'/p/'.$p->id)) }}">Partager</a>
                        </div>
                    </div>
                </article>
            @empty
                <p class="muted">Pas encore de produit.</p>
            @endforelse
        </div>
    </div>

    <div x-show="tab==='videos'">
        <h2 class="font-display" style="font-size:2.4rem">Studio vidéo</h2>
        @forelse($node->media as $m)
            <a class="card" href="/n/{{ $node->slug }}/v/{{ $m->id }}" style="display:grid;grid-template-columns:160px 1fr;gap:1rem;padding:0.75rem;margin:0.6rem 0">
                <video src="/{{ ltrim($m->path,'/') }}" muted style="width:160px;height:90px;object-fit:cover;border-radius:0.6rem"></video>
                <div>
                    <p class="kicker">{{ $m->mode }} · {{ $m->access }} · {{ $m->views }} vues</p>
                    <h3 class="font-display" style="font-size:1.6rem;margin:0">{{ $m->title }}</h3>
                    <p class="muted">{{ $m->duration }} {{ $m->price }} · {{ $m->rating }}/5</p>
                    <p class="muted" style="font-size:0.85rem">{{ $m->transcript }}</p>
                </div>
            </a>
        @empty
            <p class="muted">Déposez un MP4 dans le Drive.</p>
        @endforelse
    </div>

    <div x-show="tab==='reliques'">
        <h2 class="font-display" style="font-size:2.4rem">Drive / reliques</h2>
        <p class="muted">Fichiers sur le disque du serveur (mutu / VPS). <a class="primary" href="/drive">Uploader</a></p>
        @forelse($files as $f)
            <p class="card" style="padding:0.9rem;margin:0.4rem 0;display:flex;justify-content:space-between">
                <span>{{ $f->locked ? '🔒' : '📄' }} {{ $f->title }}</span>
                <a class="primary" href="{{ $f->path }}">ouvrir</a>
            </p>
        @empty
            <p class="muted">Drive vide.</p>
        @endforelse
    </div>
</div>
</div>
</template>

<nav class="dock">
    <template x-for="b in bubbles" :key="b.id"><span class="rise" x-text="b.name + ' vient de poster'"></span></template>
    <button type="button" :class="tab==='vivre' && 'active'" @click="tab='vivre'">Univers</button>
    <button type="button" :class="tab==='personnages' && 'active'" @click="tab='personnages'">Personnages</button>
    <button type="button" :class="tab==='forum' && 'active'" @click="tab='forum'">Forum</button>
    <button type="button" :class="tab==='journal' && 'active'" @click="tab='journal'">Journal</button>
    <button type="button" :class="tab==='guilde' && 'active'" @click="tab='guilde'">Guilde</button>
    <button type="button" :class="tab==='guides' && 'active'" @click="tab='guides'">Guides</button>
    <button type="button" :class="tab==='boutique' && 'active'" @click="tab='boutique'">Boutique</button>
    <button type="button" :class="tab==='videos' && 'active'" @click="tab='videos'">Vidéos</button>
    <button type="button" :class="tab==='reliques' && 'active'" @click="tab='reliques'">Drive</button>
</nav>

@else
{{-- ========== VERA / ORION ========== --}}
<section class="hero" style="min-height:48dvh" x-show="tab!=='forum' && tab!=='videos'">
    <img class="bg" src="{{ $node->hero }}" alt="">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Maison · recrutement expérientiel</p>
        <h1>{{ $node->title }}</h1>
        <p>{{ $node->summary }}</p>
    </div>
</section>
<div class="wrap" style="padding-top:1.5rem" x-show="tab!=='forum' && tab!=='videos'">
    <div x-show="tab==='maison'">
        <p style="max-width:40rem">{{ $node->summary }} Quêtes, pas des CV. Salon, arbre, 7 étapes.</p>
        <div class="rel" style="margin-top:1rem">
            @foreach($children as $c)
                <a class="chip" href="/n/{{ $c->slug }}">{{ $c->title }}</a>
            @endforeach
        </div>
    </div>
    <div x-show="tab==='salon'">
        <h2 class="font-display">Salon spatial</h2>
        <p class="muted">Approchez un stand. (2.5D — pas du 3D lourd.)</p>
        <div class="salon">
            <div class="cell">Accueil</div>
            <div class="cell primary">Stand Orion</div>
            <div class="cell">Café</div>
            <div class="cell">Drive</div>
            <div class="cell">Épreuve</div>
            <div class="cell">Sortie</div>
        </div>
    </div>
    <div x-show="tab==='arbre'">
        <h2 class="font-display">Arbre de compétences</h2>
        <svg class="tree" viewBox="0 0 320 220">
            <line x1="160" y1="30" x2="80" y2="110" stroke="currentColor" opacity="0.4"/>
            <line x1="160" y1="30" x2="240" y2="110" stroke="currentColor" opacity="0.4"/>
            <line x1="80" y1="110" x2="80" y2="190" stroke="currentColor" opacity="0.4"/>
            <line x1="240" y1="110" x2="240" y2="190" stroke="currentColor" opacity="0.4"/>
            <circle cx="160" cy="30" r="18" fill="var(--primary)"/>
            <circle cx="80" cy="110" r="16" fill="var(--surface)" stroke="var(--primary)"/>
            <circle cx="240" cy="110" r="16" fill="var(--surface)" stroke="var(--primary)"/>
            <circle cx="80" cy="190" r="14" fill="var(--surface-2)"/>
            <circle cx="240" cy="190" r="14" fill="var(--surface-2)"/>
            <text x="160" y="34" text-anchor="middle" font-size="8" fill="var(--primary-fg)">Fit</text>
        </svg>
    </div>
    <div x-show="tab==='offres'">
        <h2 class="font-display">Offres</h2>
        @foreach($children as $c)
            <a class="card" href="/n/{{ $c->slug }}" style="display:block;padding:1rem;margin:0.5rem 0">
                <p class="kicker">{{ $c->kind }}</p>
                <h3 class="font-display">{{ $c->title }}</h3>
                <p class="muted">{{ $c->summary }}</p>
            </a>
        @endforeach
    </div>
    <div x-show="tab==='epreuve'">
        <h2 class="font-display">Quêtes (pas un CV)</h2>
        @forelse($node->quests as $q)
            <article class="step">
                <p class="kicker">Étape {{ $q->step }} · {{ $q->skill }}</p>
                <h3 class="font-display">{{ $q->title }}</h3>
                <p>{{ $q->prompt }}</p>
                <div class="rel" style="margin-top:0.5rem">
                    <span class="chip">{{ $q->option_a }}</span>
                    <span class="chip">{{ $q->option_b }}</span>
                </div>
            </article>
        @empty
            <p class="muted">Pas d'épreuve.</p>
        @endforelse
    </div>
    <div x-show="tab==='drive' || tab==='academie'">
        <h2 class="font-display">{{ $tab === 'academie' ? 'Académie' : 'Drive' }}</h2>
        @foreach($files as $f)
            <p class="card" style="padding:0.9rem;margin:0.4rem 0">{{ $f->locked ? '🔒' : '📄' }} {{ $f->title }}</p>
        @endforeach
        @foreach($node->wiki as $w)
            <article class="card" style="padding:1rem;margin:0.5rem 0"><h3 class="font-display">{{ $w->title }}</h3><p class="muted">{{ $w->body }}</p></article>
        @endforeach
    </div>
</div>

<div x-show="tab==='forum'">
    <div class="snap">
        @foreach($forumThreads as $t)
            <article class="snap-card">
                <img src="{{ $t->cover ?: $node->hero }}" alt="">
                <div class="veil"></div>
                <div class="relative wrap" style="z-index:2">
                    <p class="kicker">{{ $t->author }}</p>
                    <h2 class="font-display" style="font-size:2.4rem">{{ $t->title }}</h2>
                    <p>{{ $t->body }}</p>
                    <button class="btn" type="button" @click="openId='{{ $t->id }}'">Discuter</button>
                    <a class="btn-line" href="/n/{{ $node->slug }}/t/{{ $t->id }}">Fiche SEO</a>
                </div>
            </article>
        @endforeach
    </div>
    <aside class="dive" x-show="openId" x-cloak>
        @foreach($forumThreads as $t)
            <div x-show="openId==='{{ $t->id }}'" style="padding:1rem;overflow:auto">
                @foreach($replies->get($t->id, collect()) as $r)
                    <article class="legacy-card"><p class="kicker">{{ $r->author }}</p><p>{{ $r->body }}</p></article>
                @endforeach
                <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/reply">@csrf<input name="body" required placeholder="Legacy…" style="width:100%"></form>
            </div>
        @endforeach
    </aside>
</div>
<div class="wrap" x-show="tab==='videos'" style="padding-top:1.5rem">
    @foreach($node->media as $m)
        <a class="card" href="/n/{{ $node->slug }}/v/{{ $m->id }}" style="display:block;padding:1rem;margin:0.5rem 0">
            <p class="kicker">{{ $m->mode }} · entretien</p>
            <h3 class="font-display">{{ $m->title }}</h3>
        </a>
    @endforeach
</div>
<nav class="dock">
    <button type="button" :class="tab==='maison' && 'active'" @click="tab='maison'">Maison</button>
    <button type="button" :class="tab==='salon' && 'active'" @click="tab='salon'">Salon</button>
    <button type="button" :class="tab==='arbre' && 'active'" @click="tab='arbre'">Arbre</button>
    <button type="button" :class="tab==='offres' && 'active'" @click="tab='offres'">Offres</button>
    <button type="button" :class="tab==='epreuve' && 'active'" @click="tab='epreuve'">Quêtes</button>
    <button type="button" :class="tab==='drive' && 'active'" @click="tab='drive'">Drive</button>
    <button type="button" :class="tab==='forum' && 'active'" @click="tab='forum'">Forum</button>
    <button type="button" :class="tab==='videos' && 'active'" @click="tab='videos'">Vidéos</button>
</nav>
@endif
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection
