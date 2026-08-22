@extends('layouts.app')
@section('title', 'Importer — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Onboarding</p>
  <h1 class="font-display" style="font-size:2.2rem">Coller Facebook / Discord</h1>
  <p class="muted">Texte brut, blocs séparés par une ligne vide, ou JSON Discord (tableau de messages). Chaque post devient un sujet Legacy indexable. Les fiches du club sont maillées, pas inventées.</p>
  <form method="post" action="/n/{{ $node->slug }}/import">
    @csrf
    <textarea name="paste" required style="width:100%;min-height:16rem" placeholder="Jean
12 août
Qui a changé le joint de culasse 205 GTI à Reims ?

---
Marie
Hier
Oui, 4 heures. La fiche est encore vide."></textarea>
    <button class="btn" type="submit">Créer les sujets</button>
  </form>
</main>
@endsection
