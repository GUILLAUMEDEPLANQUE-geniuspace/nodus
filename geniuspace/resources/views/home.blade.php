@extends('layouts.app')
@section('title', 'Nodus — crée un univers. L’hôte n’en sort pas.')
@section('description', 'Tu crées un univers. Tu poses les lieux et les preuves. Un hôte n’en sort pas. Vera et Lumen sont des mondes déjà habités, pas le produit.')
@section('canonical', url('/'))
@section('content')
<section class="hero" style="min-height:72dvh">
    <img class="bg" src="/realms/sea-hero.jpg" alt="">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Nodus · OS de micro-univers</p>
        <h1>Tu crées un univers.<br>L’hôte n’en sort pas.</h1>
        <p class="muted" style="max-width:36rem">Tu poses les lieux et les preuves. Un hôte lit seulement ce coffre — pas le web, pas le spoiler, pas une embauche inventée. Vera et Lumen sont des mondes déjà habillés, pas la porte.</p>
        <div style="margin-top:1.5rem;display:flex;gap:0.6rem;flex-wrap:wrap">
            <a class="btn" href="/create">Créer un univers</a>
            <a class="btn-line" href="/n/atelier-clamp">Voir un atelier</a>
            <a class="btn-ghost" href="/explore">Explorer</a>
        </div>
    </div>
</section>
<main class="wrap" style="padding:2rem 1.25rem 6rem">
  <p class="kicker">Cinq germes · le catalogue reste ouvert</p>
  <div class="grid-3">
    <a class="card" href="/create">
      <div class="pad">
        <p class="kicker">Atelier</p>
        <h2 class="font-display">Anime / manga</h2>
        <p class="muted">Rideau d’arc. Le perso est un lieu. Ce que tu n’as pas vu n’existe pas encore.</p>
      </div>
    </a>
    <a class="card" href="/create">
      <div class="pad">
        <p class="kicker">Terrain</p>
        <h2 class="font-display">Jeu vidéo</h2>
        <p class="muted">Le patch est un curseur. Le build est une épreuve. Le loot est une relique.</p>
      </div>
    </a>
    <a class="card" href="/create">
      <div class="pad">
        <p class="kicker">Coffre</p>
        <h2 class="font-display">Relique</h2>
        <p class="muted">L’hôte négocie dans le plancher. Le certificat s’ouvre au paiement.</p>
      </div>
    </a>
    <a class="card" href="/create">
      <div class="pad">
        <p class="kicker">Maison</p>
        <h2 class="font-display">RH / missions</h2>
        <p class="muted">Salaire écrit. Délai public. L’épreuve tranche, pas le CV.</p>
      </div>
    </a>
    <a class="card" href="/create">
      <div class="pad">
        <p class="kicker">Hub manga</p>
        <h2 class="font-display">Fandom tenu</h2>
        <p class="muted">Salles, fiches, passages. L’hôte reste dans le pack du lieu.</p>
      </div>
    </a>
  </div>
  <p class="muted" style="margin:2rem 0 1rem">Mondes témoins — déjà habités</p>
  <div class="grid-3">
    <a class="card card-film" href="/n/vera">
      <img src="/offer/releve-atelier.jpg" alt="Vera">
      <div class="pad">
        <p class="kicker">Maison</p>
        <h2 class="font-display">Vera</h2>
        <p class="muted">Salaire écrit, délai public, test de 6 min. Un monde RH, pas la home.</p>
      </div>
    </a>
    <a class="card card-film" href="/n/lumen">
      <img src="/realms/actor-hero.jpg" alt="Lumen">
      <div class="pad">
        <p class="kicker">Coffre</p>
        <h2 class="font-display">Lumen</h2>
        <p class="muted">Œuvre, certificat, making-of. Même moteur, autre peau.</p>
      </div>
    </a>
  </div>
</main>
@endsection
