@extends('layouts.app')
@section('title', ($q ?: 'Recherche').' — '.$node->title)
@section('canonical', url('/n/'.$node->slug.'/q').($q ? '?q='.urlencode($q) : ''))
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Dans {{ $node->title }}</p>
  <h1 class="font-display">Recherche</h1>
  <form action="/n/{{ $node->slug }}/q" class="rel" style="margin:1rem 0">
    <input name="q" value="{{ $q }}" placeholder="Fiche, sujet, pièce, vidéo…" style="flex:1">
    <button class="btn" type="submit">Chercher</button>
  </form>
  @forelse($hits as $h)
    <a class="card" href="{{ $h['href'] }}" style="display:block;padding:1rem;margin:.4rem 0">
      <p class="kicker">{{ $h['kind'] }} · {{ $h['score'] }}</p>
      <h3 class="font-display">{{ $h['title'] }}</h3>
      <p class="muted">{{ \Illuminate\Support\Str::limit(strip_tags($h['blurb']), 140) }}</p>
    </a>
  @empty
    @if($q)<p class="muted">Rien pour « {{ $q }} ».</p>@endif
  @endforelse
</main>
@endsection
