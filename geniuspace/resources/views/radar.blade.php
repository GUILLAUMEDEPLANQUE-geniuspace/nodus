@extends('layouts.app')
@section('title', 'Radar SEO — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Propriétaire</p>
  <h1 class="font-display" style="font-size:2.4rem">Radar SEO</h1>
  <form method="post" action="/n/{{ $node->slug }}/bounties" class="card" style="padding:1rem;margin:1rem 0">
    @csrf
    <p class="kicker">Transformer une alerte en quête de guilde</p>
    <input name="keyword" required placeholder="Changement joint de culasse 205 GTI" style="width:100%">
    <input name="reward" type="number" value="500" style="width:6rem"> NodeCoins
    <button class="btn" type="submit">Ouvrir la quête</button>
  </form>
  @foreach($issues as $i)
    <a class="card" href="{{ $i['href'] }}" style="display:block;padding:1rem;margin:.5rem 0">
      <p class="kicker">{{ $i['niveau'] }}</p>
      <p>{{ $i['msg'] }}</p>
    </a>
  @endforeach
  <p style="margin-top:1.5rem"><a class="btn-line" href="/n/{{ $node->slug }}">Retour au club</a></p>
</main>
@endsection
