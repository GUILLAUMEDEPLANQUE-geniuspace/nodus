@extends('layouts.app')
@section('title', $node->title.' — '.$club->title)
@section('description', $node->summary)
@section('canonical', url('/n/'.$club->slug.'/f/'.$node->slug))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@'.'context' => 'https://schema.org',
  '@'.'type' => $node->kind === 'character' ? 'Person' : 'CreativeWork',
  'name' => $node->title,
  'description' => $node->summary,
  'image' => url($node->hero),
  'url' => url('/n/'.$club->slug.'/f/'.$node->slug),
  'isPartOf' => ['@'.'type'=>'CreativeWork','name'=>$club->title,'url'=>url('/n/'.$club->slug)],
  'hasPart' => $children->map(fn($c)=>['@'.'type'=>'CreativeWork','name'=>$c->title,'url'=>url('/n/'.$club->slug.'/f/'.$c->slug)])->values(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem">
  <p class="kicker"><a href="/n/{{ $club->slug }}">{{ $club->title }}</a> · fiche</p>
  <h1 class="font-display" style="font-size:2.8rem">{{ $node->title }}</h1>
  <p>{!! \App\Support\Linker::html($club, $node->summary) !!}</p>
  @foreach($parents as $p)
    <a class="chip" href="/g/{{ $p->slug }}">Parent · {{ $p->title }}</a>
  @endforeach
  @foreach($children as $c)
    <a class="chip" href="/n/{{ $club->slug }}/f/{{ $c->slug }}">{{ $c->title }}</a>
  @endforeach
  @foreach($cck as $f)
    @include('partials.cck-render', ['f'=>$f])
  @endforeach
  @if($sibs->count()>=2)
    <p style="margin-top:1.5rem"><a class="btn-line" href="/n/{{ $club->slug }}/vs/{{ $sibs[0]->slug }}/{{ $sibs[1]->slug }}">Comparer</a>
    <a class="btn-ghost" href="/g/{{ $node->slug }}">Graphe JSON</a></p>
  @endif
</main>
@endsection
