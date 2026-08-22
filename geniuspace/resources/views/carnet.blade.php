@extends('layouts.app')
@section('title', 'Carnet · '.$node->title)
@section('description', 'Les films ouverts, les fichiers mérités, les reliques. Un export, pas un ZIP anonyme.')
@section('canonical', url('/n/'.$node->slug.'/carnet'))
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem;max-width:40rem">
  <p class="kicker">Carnet · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:2.6rem">Ce que vous avez mérité</h1>
  <p class="muted">Pas un historique YouTube. Les preuves voyagent avec vous d’un lieu à l’autre.</p>

  @if(!empty($mine))
  <div class="card" style="padding:1.2rem;margin-top:1.4rem">
    <p class="kicker">Sur cet appareil</p>
    <ul style="list-style:none;padding:0;margin:.6rem 0">
      @foreach($mine as $p)
        <li style="display:flex;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--border);padding:.65rem 0">
          <div>
            <p style="margin:0;font-weight:500"><a href="{{ $p['href'] }}">{{ $p['titre'] }}</a></p>
            <p class="muted" style="margin:0;font-size:.8rem">{{ $p['maison'] }} · {{ $p['quoi'] }}</p>
          </div>
          <span class="chip">tenu</span>
        </li>
      @endforeach
    </ul>
  </div>
  @else
    <p class="muted" style="margin-top:1.2rem">Rien encore. Ouvrez un making-of, tenez une épreuve, prenez une relique.</p>
  @endif

  @if(!empty($carnet['preuves']))
  <div class="card" style="padding:1.2rem;margin-top:1rem">
    <p class="kicker">{{ $carnet['titre'] ?? 'Maison' }}</p>
    <ul style="list-style:none;padding:0;margin:.6rem 0">
      @foreach($carnet['preuves'] as $p)
        <li style="padding:.5rem 0;border-bottom:1px solid var(--border)">
          <a href="{{ $p['href'] }}">{{ $p['titre'] }}</a>
          <span class="muted"> · {{ $p['quoi'] }}</span>
        </li>
      @endforeach
    </ul>
  </div>
  @endif

  <div class="rel" style="margin-top:1.2rem">
    <a class="btn" href="/n/{{ $node->slug }}/carnet.json">Exporter JSON</a>
    <a class="btn-line" href="/n/{{ $node->slug }}/videos">Reprendre un film</a>
  </div>
</main>
@endsection
