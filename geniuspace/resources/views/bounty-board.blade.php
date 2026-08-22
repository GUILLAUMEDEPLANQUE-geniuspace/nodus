@extends('layouts.app')
@section('title', 'Guides manquants du web francophone — Geniuspace')
@section('description', 'Quêtes SEO de niche : écris le guide, gagne des NodeCoins et une byline Google.')
@section('canonical', url('/bounties'))
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:44rem">
  <p class="kicker">File publique</p>
  <h1 class="font-display" style="font-size:2.4rem">Guides que le web FR n’a pas</h1>
  <p class="muted">Upwork du SEO de niche. Tu écris. Le LLM note. L’admin publie. Ton nom sur la page qui rank.</p>
  @foreach($rows as $b)
    @php $c = $clubs[$b->node_id] ?? null; @endphp
    <article class="card" style="padding:1rem;margin:.6rem 0">
      <p class="kicker">{{ $b->reward }} coins · {{ $b->status }} · {{ $c->title ?? $b->node_id }}</p>
      <h2 class="font-display">{{ $b->keyword }}</h2>
      @if($c)<p><a class="btn-line" href="/n/{{ $c->slug }}/bounties">Prendre la quête</a></p>@endif
    </article>
  @endforeach
</main>
@endsection
