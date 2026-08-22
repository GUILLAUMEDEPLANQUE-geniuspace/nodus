@extends('layouts.app')
@section('title', 'Créer un univers — Geniuspace')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem">
  <p class="kicker">Onboarding</p>
  <h1 class="font-display" style="font-size:2.8rem">Créer un monde</h1>
  <p class="muted">Fans = living. Recruteur = Vera. Ensuite : Drive, éditeur d’images, CCK, onglets.</p>
  <form method="post" action="/create" class="card" style="padding:1.25rem;max-width:32rem">
    @csrf
    <input name="title" required placeholder="Nom (One Piece, Maison Orion…)" style="width:100%;margin:0.4rem 0">
    <select name="kind" style="width:100%;margin:0.4rem 0">
      <option value="series">Série / manga / jeu</option>
      <option value="company">Maison / business / recrutement</option>
      <option value="franchise">Franchise</option>
      <option value="person">Personne</option>
    </select>
    <select name="skin" style="width:100%;margin:0.4rem 0">
      <option value="living">Lieu de vie (fans)</option>
      <option value="vera">Maison recruteur (Vera)</option>
    </select>
    <textarea name="summary" placeholder="Pitch" style="width:100%"></textarea>
    <button class="btn" type="submit" style="margin-top:0.8rem">Créer l’univers</button>
  </form>
</main>
@endsection
