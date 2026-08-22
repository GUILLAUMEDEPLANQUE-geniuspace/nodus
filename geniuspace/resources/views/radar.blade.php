@extends('layouts.app')
@section('title', 'Radar SEO — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Propriétaire</p>
  <h1 class="font-display" style="font-size:2.4rem">Radar SEO</h1>
  <p class="muted">Ce que Google et les LLM ne voient pas encore.</p>
  @foreach($issues as $i)
    <a class="card" href="{{ $i['href'] }}" style="display:block;padding:1rem;margin:.5rem 0">
      <p class="kicker">{{ $i['niveau'] }}</p>
      <p>{{ $i['msg'] }}</p>
    </a>
  @endforeach
  <p style="margin-top:1.5rem"><a class="btn-line" href="/n/{{ $node->slug }}">Retour au club</a></p>
</main>
@endsection
