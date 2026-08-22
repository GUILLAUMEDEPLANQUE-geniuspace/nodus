@extends('layouts.app')
@section('title', 'Quêtes SEO — '.$node->title)
@section('canonical', url('/n/'.$node->slug.'/bounties'))
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:42rem">
  <p class="kicker">Guilde · {{ $open }} ouverte(s)</p>
  <h1 class="font-display" style="font-size:2.4rem">Bounties SEO</h1>
  <p class="muted">Le radar détecte le trou. La guilde écrit. Le LLM note. L’admin publie. NodeCoins.</p>
  @foreach($rows as $b)
    <article class="card" style="padding:1rem;margin:.7rem 0">
      <p class="kicker">{{ $b->status }} · {{ $b->reward }} coins · {{ $b->title_reward }}</p>
      <h2 class="font-display">{{ $b->keyword }}</h2>
      @if($b->llm_score)<p class="muted">LLM {{ $b->llm_score }}/100 — {{ $b->llm_note }}</p>@endif
      @if($b->status==='open')
        <form method="post" action="/n/{{ $node->slug }}/bounties/{{ $b->id }}/claim">@csrf<button class="btn" type="submit">Prendre la quête</button></form>
      @endif
      @if($b->status==='claimed' && (int)$b->claimer_id === (int)auth()->id())
        <form method="post" action="/n/{{ $node->slug }}/bounties/{{ $b->id }}/submit">
          @csrf
          <textarea name="draft" required minlength="400" placeholder="Guide (≥400 car., mot-clé, titres ## )" style="width:100%;min-height:8rem">{{ $b->draft }}</textarea>
          <button class="btn" type="submit">Soumettre au LLM</button>
        </form>
      @endif
      @if($b->status==='submitted')
        <pre class="muted" style="white-space:pre-wrap">{{ \Illuminate\Support\Str::limit($b->draft, 400) }}</pre>
        <form method="post" action="/n/{{ $node->slug }}/bounties/{{ $b->id }}/publish">@csrf<button class="btn" type="submit">Publier (admin)</button></form>
      @endif
    </article>
  @endforeach
</main>
@endsection
