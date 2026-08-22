{{-- HUD jouable d’un flagship. Pas un pitch : une boucle (hôte, passage, preuve, objet). --}}
@php
  $flag = $flag ?? \App\Support\Flagships::of($node);
  if ($flag && ! \App\Support\Flagships::has((string) ($node->template ?? ''))) {
      $flag = null;
  }
  $host = $flag['ghost'] ?? ['name' => 'Le guide', 'role' => 'Guide', 'wake' => ''];
  $canvas = $flag['canvas'] ?? 'lieu';
  $shop = $node->products->first();
  $clip = $node->media->first();
  $fiche = $children->first();
@endphp
@if($flag)
<section class="flag-play canvas-{{ $canvas }}" id="jouer">
  <article class="flag-host">
    <p class="kicker">{{ $host['role'] }}</p>
    <h2 class="font-display">{{ $host['name'] }}</h2>
    <p>{{ $host['wake'] }}</p>
    <button type="button" class="btn" onclick="document.querySelector('.ghost-orb')?.click()">Parler à {{ $host['name'] }}</button>
  </article>

  <article class="flag-loop">
    @if($canvas === 'vault')
      <p class="kicker">La relique</p>
      @if($shop)
        <h3>{{ $shop->title }}</h3>
        <p class="price">{{ $shop->price }}</p>
        <a class="btn" href="/n/{{ $node->slug }}/p/{{ $shop->id }}">Entrer dans le coffre</a>
      @endif

    @elseif($canvas === 'terrain')
      <p class="kicker">HUD · patch vivant</p>
      @foreach($cck as $f)
        @if(trim((string)$f->value) !== '' && in_array(mb_strtolower($f->name), ['patch','plateforme','classe','difficulté'], true))
          <span class="chip">{{ $f->name }} · {{ $f->value }}</span>
        @endif
      @endforeach
      @if($shop)
        <h3>{{ $shop->title }}</h3>
        <form method="post" action="/cart">@csrf<input type="hidden" name="product_id" value="{{ $shop->id }}"><button class="btn" type="submit">Prendre le loot</button></form>
      @endif
      @if($clip)<a class="btn-line" href="/n/{{ $node->slug }}/v/{{ $clip->id }}">VOD → labo</a>@endif

    @elseif($canvas === 'atelier')
      <p class="kicker">Rideau · anti-spoiler</p>
      <p>L’arc que tu n’as pas vu n’existe pas encore. Avance le curseur en haut, puis parle au concierge — il filtre.</p>
      @if($fiche)<a class="btn" href="/n/{{ $node->slug }}/f/{{ $fiche->slug }}">Fiche · {{ $fiche->title }}</a>@endif
      @if($shop)<a class="btn-line" href="/n/{{ $node->slug }}/p/{{ $shop->id }}">Cel · {{ $shop->title }}</a>@endif

    @elseif($canvas === 'territoire')
      <p class="kicker">Atlas · corridors</p>
      <div class="rel" style="flex-wrap:wrap">
        @forelse($children->take(6) as $c)
          <a class="chip" href="/n/{{ $c->slug }}">{{ $c->title }}</a>
        @empty
          <p class="muted">Les lieux s’ouvrent ici.</p>
        @endforelse
      </div>
      @if($shop)<a class="btn" href="/n/{{ $node->slug }}/p/{{ $shop->id }}">Goût du lieu</a>@endif

    @elseif($canvas === 'scene')
      <p class="kicker">Drop · pressage</p>
      <div class="flag-wave" aria-hidden="true"></div>
      @if($shop)
        <h3>{{ $shop->title }}</h3>
        <form method="post" action="/cart">@csrf<input type="hidden" name="product_id" value="{{ $shop->id }}"><button class="btn" type="submit">Prendre le pressage</button></form>
      @endif
      @if($clip)<a class="btn-line" href="/n/{{ $node->slug }}/v/{{ $clip->id }}">Écouter</a>@endif

    @elseif($canvas === 'arene')
      <p class="kicker">Compos du jour</p>
      <div class="flag-compos">
        @forelse($children->take(11) as $c)
          <a href="/n/{{ $node->slug }}/f/{{ $c->slug }}">{{ $c->title }}</a>
        @empty
          <p class="muted">La compos se pose ici.</p>
        @endforelse
      </div>
      @if($clip)<a class="btn" href="/n/{{ $node->slug }}/v/{{ $clip->id }}">VOD match</a>@endif
      @if($shop)<a class="btn-line" href="/n/{{ $node->slug }}/p/{{ $shop->id }}">Maillot</a>@endif

    @elseif($canvas === 'labo')
      <p class="kicker">Exo · tu ne regardes plus</p>
      @if($clip)
        <h3>{{ $clip->title }}</h3>
        <a class="btn" href="/n/{{ $node->slug }}/v/{{ $clip->id }}">Ouvrir l’exo · cadre à 03:15</a>
      @endif
      <a class="btn-line" href="/n/vera">Vers une mission qui recrute</a>

    @elseif($canvas === 'plateau')
      <p class="kicker">Feuille de service</p>
      @forelse($children->take(6) as $c)
        <a class="chip" href="/n/{{ $node->slug }}/f/{{ $c->slug }}">{{ $c->title }} · {{ $c->subtitle }}</a>
      @empty
        <p class="muted">L’équipe se pose ici.</p>
      @endforelse
      @if($clip)
        <p class="muted" style="margin-top:.6rem">Rushes lockés. L’AD ouvre au bon rôle.</p>
        <a class="btn" href="/n/{{ $node->slug }}/v/{{ $clip->id }}">Daily</a>
      @endif

    @elseif($canvas === 'table')
      <p class="kicker">Cave · service</p>
      @if($shop)
        <h3>{{ $shop->title }}</h3>
        <p class="price">{{ $shop->price }}</p>
        <a class="btn" href="/n/{{ $node->slug }}/p/{{ $shop->id }}">Ouvrir la nappe</a>
      @endif

    @elseif($canvas === 'maison')
      <p class="kicker">Présélection · 3 questions</p>
      <p>Pas un CV. Parle à l’accueil, ou ouvre une mission. Vera reste le lieu historique — ici le catalogue voyage.</p>
      <a class="btn" href="/n/{{ $node->slug }}/offres">Voir les missions</a>
      <a class="btn-line" href="/n/{{ $node->slug }}/epreuve">Tenter l’épreuve</a>

    @else
      <p class="kicker">Boucle</p>
      <p>{{ $flag['pitch'] }}</p>
    @endif

    <form class="proof-box" method="post" action="/n/{{ $node->slug }}/preuve-lore" style="margin-top:1rem" x-data="{ok:''}" @submit.prevent="
      fetch($el.action, {method:'POST', headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json','Content-Type':'application/json'},
        body: JSON.stringify({field:$refs.f.value, value:$refs.v.value, stake: {{ (int)($flag['proof']['stake'] ?? 0) }} })
      }).then(r=>r.json()).then(j=> ok = j.quoi || 'Mise posée.')
    ">
      <p>{{ $flag['proof']['label'] }} · {{ $flag['proof']['what'] }}</p>
      <input x-ref="f" placeholder="Champ" required>
      <input x-ref="v" placeholder="Correction" required>
      <button class="btn-line" type="submit">Proposer</button>
      <p class="muted" x-show="ok" x-text="ok"></p>
    </form>
  </article>
</section>
@endif
