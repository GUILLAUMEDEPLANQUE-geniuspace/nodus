@extends('layouts.app')
@section('title', 'Postuler — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:36rem">
  <p class="kicker">Sac à dos → CV</p>
  <h1 class="font-display">Coche tes reliques</h1>
  <p class="muted">LinkedIn ne peut pas importer ça. Ce que tu as gagné sur d’autres clubs voyage ici.</p>
  <form method="post">
    @csrf
    @forelse($pack as $it)
      <label class="card" style="display:block;padding:.8rem;margin:.4rem 0">
        <input type="checkbox" name="relics[]" value="{{ $it->id }}"> <strong>{{ $it->label }}</strong>
        <span class="kicker">{{ $it->kind }}</span>
      </label>
    @empty
      <p class="muted">Sac vide. Gagne une bounty ou achète une pièce d’abord.</p>
    @endforelse
    <button class="btn" type="submit">Envoyer la candidature</button>
  </form>
</main>
@endsection
