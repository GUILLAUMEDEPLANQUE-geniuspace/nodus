@extends('layouts.vera')
@section('title', 'Europe — proof before the degree | Vera')
@section('description', 'Remote Europe ±2h. Salaire publié. Épreuve métier, module si ça rate, retry. Talent Passport, transparence salariale UE.')
@section('canonical', url('/n/vera/europe'))
@php
  $markets = \App\Support\VeraCatalog::json('markets');
  $jobs = \App\Support\VeraCatalog::filter(['collection'=>'remote']);
@endphp
@section('content')
<section class="vera-hero">
  <div class="vera-wrap">
    <p class="vera-kicker">Europe</p>
    <h1>La preuve<br>avant le titre.</h1>
    <p class="vera-lead">La même boucle Vera, dans le fuseau qui tient : épreuve, échec → module de 8 min, retry, passeport. Indeed vend du volume. Nous vendons un dossier tenu.</p>
    <div class="chips" style="margin-top:1.4rem">
      <a class="vera-btn" href="/n/vera/preuve">Passer une épreuve</a>
      <a class="vera-btn ghost" href="/n/vera/offres?collection=remote">Offres remote ±2h</a>
    </div>
  </div>
</section>
<section class="section">
  <div class="vera-wrap vera-grid g3">
    <article class="principle"><p class="n">01</p><h3>L’épreuve</h3><p>6–8 min, geste pas QCM RH. Coordonnées après.</p></article>
    <article class="principle"><p class="n">02</p><h3>Échec → module</h3><p>L’échec est une leçon taguée, puis un retry. Pas un silence.</p></article>
    <article class="principle"><p class="n">03</p><h3>Passeport</h3><p>Preuve exportable. L’entreprise paie un qualifié, pas un clic.</p></article>
  </div>
</section>
<section class="section paper">
  <div class="vera-wrap">
    <h2>Ce qui voyage</h2>
    <ul class="vera-grid g2" style="list-style:none;padding:0;margin-top:1.4rem">
      <li class="v-card"><h3>Le diplôme est un mauvais proxy</h3><p>US, UE, Canada, Asie lâchent le filtre diplôme. Assessments, micro-credentials, passports : Vera est déjà ce produit, avec un geste métier.</p></li>
      <li class="v-card"><h3>Échec → module → retry</h3><p>HackerRank et Codility filtrent. Vera transforme l’échec en module. Les recruteurs à court de geste détestent les faux négatifs.</p></li>
      <li class="v-card"><h3>Passeport</h3><p>Registre façon Open Badge : épreuve + candidature + fit dans un artefact. AbilityEx vérifie ; peu attachent le score à l’embauche.</p></li>
      <li class="v-card"><h3>Transparence salariale</h3><p>Directive UE, Entgelttransparenz, GPG irlandais. Vera refuse déjà les offres sans bande. La conformité est le produit.</p></li>
    </ul>
  </div>
</section>
<section class="section">
  <div class="vera-wrap">
    <h2>Marchés, friction nommée</h2>
    <ul class="vera-grid g4" style="list-style:none;padding:0;margin-top:1.4rem">
      @foreach($markets as $m)
        <li class="v-card">
          <p class="vera-kicker">{{ $m['code'] }}</p>
          <h3>{{ $m['name'] }}</h3>
          <p style="font-size:.85rem">{{ $m['testNote'] }}</p>
          <p style="font-size:.75rem;color:var(--muted)">{{ $m['law'] }}</p>
        </li>
      @endforeach
    </ul>
  </div>
</section>
<section class="section paper">
  <div class="vera-wrap">
    <h2>Offres remote ±2h</h2>
    <div class="vera-grid" style="margin-top:1rem;gap:.9rem">
      @foreach(array_slice($jobs,0,8) as $job)
        @include('vera.partials.job-card', ['job'=>$job])
      @endforeach
    </div>
  </div>
</section>
@endsection
