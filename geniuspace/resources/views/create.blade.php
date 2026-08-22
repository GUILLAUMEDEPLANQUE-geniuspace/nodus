@extends('layouts.app')
@section('title', 'Créer ton club — Geniuspace')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem">
  <p class="kicker">Pour les fans · 30 secondes</p>
  <h1 class="font-display" style="font-size:2.8rem">Crée ton monde</h1>
  <p class="muted">Club auto, manga, job… On te pose 3 questions. Pas de 3D, pas de jargon.</p>
  <form method="post" action="/create" class="card" style="padding:1.25rem;max-width:32rem">
    @csrf
    <label class="muted">Le nom</label>
    <input name="title" required placeholder="Club 205 GTI, One Piece FR…" style="width:100%;margin:0.4rem 0">
    <label class="muted">C’est plutôt</label>
    <select name="kind" style="width:100%;margin:0.4rem 0">
      <option value="series">Une passion (auto, manga, jeu…)</option>
      <option value="company">Une boîte / du recrutement</option>
      <option value="person">Une personne</option>
    </select>
    <input type="hidden" name="skin" value="living" id="skin">
    <textarea name="summary" placeholder="En une phrase : sorties, pièces, conseils." style="width:100%;min-height:5rem"></textarea>
    <button class="btn" type="submit" style="margin-top:0.8rem">Continuer — 3 questions</button>
  </form>
  <p class="muted" style="margin-top:1rem;font-size:.8rem">Recruteur ? <a class="primary" href="#" onclick="document.getElementById('skin').value='vera';document.querySelector('[name=kind]').value='company';return false">Basculer en campus jobs</a> puis envoyer.</p>
</main>
@endsection
