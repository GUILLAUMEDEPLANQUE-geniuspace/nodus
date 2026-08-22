@extends('layouts.app')
@section('title', $pl->title.' — playlist | Geniuspace')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem">
  <p class="kicker">Playlist partageable</p>
  <h1 class="font-display">{{ $pl->title }}</h1>
  <p class="muted">/pl/{{ $pl->share_slug }}</p>
  @forelse($items as $it)
    <p><a href="/n/one-piece/v/{{ $it->media_id }}">Vidéo #{{ $it->media_id }}</a></p>
  @empty
    <p class="muted">Vide.</p>
  @endforelse
</main>
@endsection
