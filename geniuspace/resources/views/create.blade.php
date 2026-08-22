@extends('layouts.app')
@section('title', 'Créer un univers — Geniuspace')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem">
  <p class="kicker">Onboarding · God Canvas</p>
  <h1 class="font-display" style="font-size:2.8rem">Créer un monde</h1>
  <p class="muted">Après validation vous entrez dans l’espace 3D (niveau catalogue spatial, pas une grille). Les outils LLM y posent CCK, SEO, flotte, rayons. Modifier un monde = même canvas.</p>
  <form method="post" action="/create" class="card" style="padding:1.25rem;max-width:32rem">
    @csrf
    <input name="title" required placeholder="Nom" style="width:100%;margin:0.4rem 0">
    <select name="kind" style="width:100%;margin:0.4rem 0">
      <option value="series">Série / manga / jeu</option>
      <option value="company">Maison / business / recrutement</option>
      <option value="franchise">Franchise / flotte RWA</option>
      <option value="person">Personne</option>
    </select>
    <select name="skin" style="width:100%;margin:0.4rem 0">
      <option value="living">Sanctuaire / flotte</option>
      <option value="vera">Campus recruteur</option>
    </select>
    <textarea name="summary" placeholder="Prompt LLM : flotte luxe, campus RPG, sanctuaire manga…" style="width:100%;min-height:6rem"></textarea>
    <button class="btn" type="submit" style="margin-top:0.8rem">Entrer dans le God Canvas</button>
  </form>
</main>
@endsection
