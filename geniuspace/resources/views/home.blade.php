@extends('layouts.app')
@section('title', 'Geniuspace')
@section('content')
<section class="hero">
    <img class="bg" src="/realms/sea-hero.jpg" alt="">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Geniuspace — un lieu de vie</p>
        <h1>Les fans bâtissent le monde.<br>La guilde l'habite.</h1>
        <p class="muted" style="max-width:36rem">Wiki, forum, journal, reliques, carte — le même univers. Un recruteur ouvre une Maison, des offres, une épreuve.</p>
        <div style="margin-top:1.5rem;display:flex;gap:0.6rem;flex-wrap:wrap">
            <a class="btn" href="{{ route('node.show', 'one-piece') }}">Vivre One Piece</a>
            <a class="btn-line" href="{{ route('node.show', 'maison-orion') }}">Maison recruteur</a>
            <a class="btn-line" href="{{ route('product.show', ['slug' => 'atelier-nocturne', 'pid' => 'sp-at-1']) }}">Galerie RWA</a>
        </div>
    </div>
</section>
<section class="wrap grid-3">
    @foreach($featured as $n)
        <a class="card" href="{{ route('node.show', $n->slug) }}">
            <img src="{{ $n->hero }}" alt="">
            <div class="pad">
                <p class="kicker">{{ $n->kind }}</p>
                <h2 class="font-display" style="font-size:1.6rem;margin:0.2rem 0">{{ $n->title }}</h2>
                <p class="muted" style="font-size:0.9rem">{{ $n->subtitle }}</p>
            </div>
        </a>
    @endforeach
</section>
@endsection
