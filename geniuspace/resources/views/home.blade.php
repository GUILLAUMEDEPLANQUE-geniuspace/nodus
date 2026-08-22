@extends('layouts.app')
@section('title', 'Club 205 — Geniuspace')
@section('description', 'Le garage indexable. Fiches 205 GTI, forum, pièces à Reims, graphe parent/enfant. Pas un groupe Facebook.')
@section('canonical', url('/'))
@section('content')
<section class="hero" style="min-height:92dvh">
    <img class="bg" src="/realms/205-garage.jpg" alt="Garage Club 205">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Geniuspace · univers pilote</p>
        <h1>Ce n’est plus un groupe.<br>C’est un garage que Google lit.</h1>
        <p class="muted" style="max-width:36rem">20 fiches, graphe 1.9 → joint → Reims, holo-forum, pièces Offer, quêtes SEO, curseur Phase 1 / 1.9 / kit rallye. Toutes les salles. Tous les outils.</p>
        <div style="margin-top:1.5rem;display:flex;gap:0.6rem;flex-wrap:wrap">
            <a class="btn" href="/n/club-205">Entrer dans le garage</a>
            <a class="btn-line" href="/n/club-205/forum">Holo-forum</a>
            <a class="btn-line" href="/n/club-205/f/peugeot-205-gti-19">Fiche 1.9</a>
            <a class="btn-ghost" href="/bounties">Quêtes SEO FR</a>
        </div>
    </div>
</section>
@endsection
