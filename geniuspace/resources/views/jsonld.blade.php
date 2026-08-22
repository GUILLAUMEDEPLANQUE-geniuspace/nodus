@extends('layouts.app')
@section('title', 'Générateur JSON-LD — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem;max-width:52rem">
  <p class="kicker">Générateur schema.org</p>
  <h1 class="font-display">{{ $node->title }}</h1>
  <p class="muted">Chaque salle compile son schéma. Google et les LLM le lisent. Copie, ping, sitemap.</p>
  <form method="get" class="rel" style="margin:1rem 0;flex-wrap:wrap">
    @foreach($rooms as $key => $r)
      <a class="chip {{ $salle===$key ? 'primary' : '' }}" href="/studio/{{ $node->slug }}/jsonld?salle={{ $key }}">{{ $r['label'] }}</a>
    @endforeach
  </form>
  <p class="kicker">Salle {{ $salle }} · {{ $rooms[$salle]['schema'] ?? '' }}</p>
  <textarea readonly rows="22" style="width:100%;font-family:ui-monospace,monospace;font-size:.8rem">{{ $json }}</textarea>
  <p class="rel" style="margin-top:1rem">
    <a class="btn" href="/n/{{ $node->slug }}/schema.json">Schéma complet .json</a>
    <a class="btn-line" href="/n/{{ $node->slug }}/{{ $salle }}/schema.json">Cette salle</a>
    <a class="btn-ghost" href="/atelier/{{ $node->slug }}">Atelier</a>
  </p>
</main>
@endsection
