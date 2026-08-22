@extends('layouts.app')
@section('title', 'Explorer — Geniuspace')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem">
    <p class="kicker">Graphe</p>
    <h1 class="font-display" style="font-size:3rem;margin:0.3rem 0">Tous les univers</h1>
    <div class="grid-3">
        @foreach($nodes as $n)
            <a class="card" href="/n/{{ $n->slug }}">
                <img src="{{ $n->hero ?: '/realms/sea-hero.jpg' }}" alt="">
                <div class="pad">
                    <p class="kicker">{{ $n->kind }}</p>
                    <h2 class="font-display" style="margin:0">{{ $n->title }}</h2>
                </div>
            </a>
        @endforeach
    </div>
</main>
@endsection
