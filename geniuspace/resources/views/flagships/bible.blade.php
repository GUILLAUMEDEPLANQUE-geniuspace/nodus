@extends('layouts.app')
@section('title', '10 templates flagship — bible produit')
@section('description', 'Coffre, Terrain, Atelier, Territoire, Maison, Scène, Arène, Labo, Plateau, Table. Chaque template a un secret, une surface, un hôte.')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem;max-width:58rem">
  <p class="kicker">Bible produit · 10 flagships</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.4rem);line-height:.95">Le moteur ne se montre pas.<br>Il se sent.</h1>
  <p class="lede">Surface : lieux, preuves, décisions. Moteur : fiches + voisins + grant. L’utilisateur ne doit jamais lire « node », « edge », « CCK ».</p>
  <p><a class="btn" href="/create">Créer un flagship</a> <a class="btn-line" href="/n/coffre-celeste/p/p-cel-1">Voir le Coffre</a></p>

  @foreach($flags as $id => $f)
    <article class="card" style="padding:1.4rem;margin:1.2rem 0;border-left:4px solid {{ $f['primary'] }}">
      <p class="kicker">{{ $f['vertical'] }} · {{ $f['schema'] }} · {{ $id }}</p>
      <h2 class="font-display" style="font-size:2rem;margin:.2rem 0">{{ $f['label'] }}</h2>
      <p style="font-size:1.15rem">{{ $f['pitch'] }}</p>
      <div class="grid-2" style="margin-top:1rem;gap:1rem">
        <div>
          <p class="kicker">Innovation</p>
          <p>{{ $f['innovation'] }}</p>
          <p class="kicker" style="margin-top:.8rem">Mécanique</p>
          <p>{{ $f['mechanic'] }}</p>
        </div>
        <div>
          <p class="kicker">Secret (moteur)</p>
          <p>{{ $f['secret'] }}</p>
          <p class="kicker" style="margin-top:.8rem">Moat</p>
          <p>{{ $f['moat'] }}</p>
        </div>
      </div>
      <ul style="margin:1rem 0 0;padding:0;list-style:none;display:grid;gap:.35rem">
        <li><strong>Hôte</strong> — {{ $f['ghost']['name'] }} · {{ $f['ghost']['role'] }}. « {{ $f['ghost']['wake'] }} »</li>
        <li><strong>Passage</strong> — {{ $f['wormhole']['label'] }} ({{ $f['wormhole']['phrase'] }})</li>
        <li><strong>Preuve</strong> — {{ $f['proof']['label'] }} · mise {{ $f['proof']['stake'] }} · {{ $f['proof']['what'] }}</li>
        <li><strong>Omni</strong> — {{ $f['omni'] }}</li>
        <li><strong>SEO</strong> — {{ $f['jsonld'] }}</li>
      </ul>
      <p class="muted" style="margin-top:.8rem">Salles : {{ implode(' · ', $f['rooms']) }}</p>
    </article>
  @endforeach
</main>
@endsection
