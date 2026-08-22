@extends('layouts.app')
@section('title', 'Connexion — Geniuspace')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem;display:grid;gap:1.5rem;grid-template-columns:1fr;max-width:52rem">
  <section>
    <h1 class="font-display">Connexion</h1>
    <p class="muted">Démo : creator@geniuspace.test / geniuspace</p>
    <form method="post" action="/login">
      @csrf
      <input name="email" type="email" required placeholder="Email" style="width:100%;margin:0.4rem 0">
      <input name="password" type="password" required placeholder="Mot de passe" style="width:100%">
      <button class="btn" type="submit" style="margin-top:0.6rem">Entrer</button>
    </form>
  </section>
  <section>
    <h2 class="font-display">Créer un compte</h2>
    <form method="post" action="/register">
      @csrf
      <input name="name" required placeholder="Nom">
      <input name="email" type="email" required placeholder="Email">
      <input name="password" type="password" required placeholder="Mot de passe">
      <button class="btn-line" type="submit" style="margin-top:0.6rem">Inscription</button>
    </form>
  </section>
</main>
@endsection
