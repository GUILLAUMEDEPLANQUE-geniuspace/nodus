@extends('layouts.app')
@section('title', 'Vera & Lumen — Geniuspace')
@section('description', 'Deux univers complets : Vera le campus de recrutement, Lumen la galerie hologramme. Pas un jobboard. Pas Shopify.')
@section('canonical', url('/'))
@section('content')
<section class="hero" style="min-height:70dvh">
    <img class="bg" src="/realms/studio-hero.jpg" alt="Vera">
    <div class="veil"></div>
    <div class="copy wrap">
        <p class="kicker">Geniuspace · deux mondes</p>
        <h1>Vera recrute.<br>Lumen vend.</h1>
        <p class="muted" style="max-width:36rem">Un campus d’épreuves (JobPosting, skill tree, Passport) et une galerie hologramme (RWA, holo-vidéo, split). On bosse dessus.</p>
        <div style="margin-top:1.5rem;display:flex;gap:0.6rem;flex-wrap:wrap">
            <a class="btn" href="/n/vera">Entrer dans Vera</a>
            <a class="btn-line" href="/n/lumen">Entrer dans Lumen</a>
            <a class="btn-ghost" href="/create">Créer un monde</a>
        </div>
    </div>
</section>
<main class="wrap" style="padding:2rem 1.25rem 6rem">
  <div class="grid-3">
    <a class="card card-film" href="/n/vera">
      <img src="/realms/studio-hero.jpg" alt="Vera">
      <div class="pad">
        <p class="kicker">Recrutement</p>
        <h2 class="font-display">Vera</h2>
        <p class="muted">Salon, arbre, 7 épreuves, magazine, holo-forum. LinkedIn ne peut pas importer le Passport.</p>
      </div>
    </a>
    <a class="card card-film" href="/n/lumen">
      <img src="/realms/actor-hero.jpg" alt="Lumen">
      <div class="pad">
        <p class="kicker">Galerie</p>
        <h2 class="font-display">Lumen</h2>
        <p class="muted">Œuvres, certificat RWA, making-of, split auteurs. Shopify n’a pas le graphe.</p>
      </div>
    </a>
  </div>
</main>
@endsection
