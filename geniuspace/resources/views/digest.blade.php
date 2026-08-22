@extends('layouts.app')
@section('title', 'Digest Legacy — '.$node->title)
@section('description', 'Le meilleur du forum, élevé hors du chat.')
@section('canonical', url('/n/'.$node->slug.'/digest'))
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@'.'context'=>'https://schema.org','@'.'type'=>'Article','headline'=>'Digest — '.$node->title,'url'=>url()->current()], JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Legacy</p>
  <h1 class="font-display" style="font-size:2.4rem">Digest</h1>
  <p class="muted">Le live meurt. Ceci reste et s’indexe.</p>
  @forelse($best as $r)
    <article class="legacy-card">
      <p class="kicker">{{ $r->author }} · {{ $r->votes }} votes</p>
      <p>{!! \App\Support\Linker::html($node, $r->body) !!}</p>
    </article>
  @empty
    <p class="muted">Pas encore de réponses à élever.</p>
  @endforelse
  <form method="post" action="/n/{{ $node->slug }}/digest" style="margin-top:1.2rem">@csrf<button class="btn" type="submit">Publier comme article</button></form>
</main>
@endsection
