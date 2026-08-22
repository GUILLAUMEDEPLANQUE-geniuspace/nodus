@extends('layouts.app')
@section('title', 'Vera & Lumen — Geniuspace')
@section('description', 'Deux univers : Vera, des offres d’emploi à salaire publié avec un test métier. Lumen, une galerie où chaque œuvre a une fiche et un making-of.')
@section('canonical', url('/'))
@section('content')
<section class="hero" style="min-height:70dvh">
    <img class="bg" src="/offer/releve-atelier.jpg" alt="Vera">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Geniuspace · deux mondes</p>
        <h1>L’emploi, lisible.<br>L’œuvre, hologramme.</h1>
        <p class="muted" style="max-width:36rem">Vera : le salaire est écrit, le délai de réponse est public, on passe un test de 6 min avant le CV. Lumen : chaque œuvre a une fiche, un certificat, une vidéo. Pas une grille d’annonces. Pas une boutique générique.</p>
        <div style="margin-top:1.5rem;display:flex;gap:0.6rem;flex-wrap:wrap">
            <a class="btn" href="/n/vera">Voir les offres Vera</a>
            <a class="btn-line" href="/n/lumen">Entrer dans Lumen</a>
            <a class="btn-ghost" href="/create">Créer un univers</a>
        </div>
    </div>
</section>
<main class="wrap" style="padding:2rem 1.25rem 6rem">
  <div class="grid-3">
    <a class="card card-film" href="/n/vera">
      <img src="/offer/releve-atelier.jpg" alt="Vera">
      <div class="pad">
        <p class="kicker">Offres d’emploi</p>
        <h2 class="font-display">Vera</h2>
        <p class="muted">35 postes, salaire publié, test métier, fiches, Europe. Indeed vend du volume. Ici on lit le poste.</p>
      </div>
    </a>
    <a class="card card-film" href="/n/lumen">
      <img src="/realms/actor-hero.jpg" alt="Lumen">
      <div class="pad">
        <p class="kicker">Galerie</p>
        <h2 class="font-display">Lumen</h2>
        <p class="muted">Œuvres, certificat, making-of, partage du prix entre auteurs.</p>
      </div>
    </a>
  </div>
</main>
@endsection
