@extends('layouts.app')
@section('title', 'Club 205 — Geniuspace')
@section('description', 'Un garage indexable. Fiches 205 GTI, forum, pièces à Reims. Pas un groupe Facebook.')
@section('canonical', url('/'))
@section('content')
<section class="hero">
    <img class="bg" src="/realms/sea-hero.jpg" alt="">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Pilote — un seul club</p>
        <h1>Club 205.<br>Le garage que Google lit.</h1>
        <p class="muted" style="max-width:36rem">3 salles : parler, fiches voitures, pièces. 20 fiches vides à habiller. 5 quêtes SEO. Le reste de Geniuspace est en bientôt.</p>
        <div style="margin-top:1.5rem;display:flex;gap:0.6rem;flex-wrap:wrap">
            <a class="btn" href="/n/club-205">Entrer dans le garage</a>
            <a class="btn-line" href="/n/club-205/import">Importer Facebook / Discord</a>
            <a class="btn-line" href="/bounties">Guides manquants (FR)</a>
            <a class="btn-ghost" href="/create">Créer un autre club — bientôt</a>
        </div>
    </div>
</section>
<section class="wrap grid-3">
    @foreach($featured as $n)
        <a class="card" href="/n/{{ $n->slug }}">
            <img src="{{ $n->hero }}" alt="">
            <div class="pad">
                <p class="kicker">{{ $n->kind }} · pilote</p>
                <h2 class="font-display" style="font-size:1.6rem;margin:0.2rem 0">{{ $n->title }}</h2>
                <p class="muted" style="font-size:0.9rem">{{ $n->subtitle }}</p>
            </div>
        </a>
    @endforeach
</section>
<section class="wrap" style="padding-bottom:4rem">
    <p class="kicker">Bientôt</p>
    <p class="muted">One Piece, Vera, Atelier — le moteur est là. On ne les pousse pas tant que le 205 n’a pas 3 requêtes n°1.</p>
    <p><a class="btn-ghost" href="/explore">Voir les démos</a> · <a class="btn-ghost" href="/api/v1/g/club-205">API graphe v1</a></p>
</section>
@endsection
