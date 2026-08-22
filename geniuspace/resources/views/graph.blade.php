@extends('layouts.app')
@section('title', 'Graphe — '.$node->title)
@section('canonical', url('/g/'.$node->slug))
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@'.'context'=>'https://schema.org','@'.'type'=>'CreativeWork','name'=>$node->title,'url'=>url('/n/'.$node->slug),'isPartOf'=>collect($payload['parents'])->pluck('node.title'),'hasPart'=>collect($payload['children'])->pluck('node.title')], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Graphe public · <a href="/g/{{ $node->slug }}.json">JSON</a></p>
  <h1 class="font-display" style="font-size:2.6rem">{{ $node->title }}</h1>
  <p class="muted">{{ $node->kind }} · source pour les autres sites</p>
  <h2 class="font-display">Parents</h2>
  @forelse($payload['parents'] as $p)
    <p><a class="primary" href="/g/{{ $p['node']['slug'] ?? '' }}">{{ $p['node']['title'] ?? '?' }}</a> <span class="muted">{{ $p['label'] }}</span></p>
  @empty<p class="muted">Aucun.</p>@endforelse
  <h2 class="font-display">Enfants / rôles</h2>
  @forelse($payload['children'] as $c)
    <p><a class="primary" href="/g/{{ $c['node']['slug'] ?? '' }}">{{ $c['node']['title'] ?? '?' }}</a> <span class="muted">{{ $c['label'] }} · {{ $c['node']['kind'] ?? '' }}</span></p>
  @empty<p class="muted">Aucun.</p>@endforelse
  <p><a class="btn-line" href="/n/{{ $node->slug }}">Ouvrir le club / la fiche</a></p>
</main>
@endsection
