@extends('layouts.app')
@section('title', $node->title.' — '.$club->title)
@section('description', $node->summary)
@section('canonical', url('/n/'.$club->slug.'/f/'.$node->slug))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@'.'context' => 'https://schema.org',
  '@'.'type' => in_array($node->kind, ['car','part']) ? 'Product' : ($node->kind === 'character' ? 'Person' : 'CreativeWork'),
  'name' => $node->title,
  'description' => $node->summary,
  'image' => url($node->hero),
  'url' => url('/n/'.$club->slug.'/f/'.$node->slug),
  'isPartOf' => ['@'.'type'=>'CreativeWork','name'=>$club->title,'url'=>url('/n/'.$club->slug)],
  'brand' => $node->kind === 'car' ? ['@'.'type'=>'Brand','name'=>'Peugeot'] : null,
  'hasPart' => $children->map(fn($c)=>['@'.'type'=>'CreativeWork','name'=>$c->title,'url'=>url('/n/'.$club->slug.'/f/'.$c->slug)])->values(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
@section('content')
<section class="hero" style="min-height:52dvh">
  <img class="bg" src="{{ $node->hero }}" alt="{{ $node->title }}">
  <div class="veil"></div>
  <div class="copy wrap">
    <p class="kicker"><a href="/n/{{ $club->slug }}">{{ $club->title }}</a> · {{ \App\Support\Vocab::kind($node->kind) }}</p>
    <h1>{{ $node->title }}</h1>
    <p>{{ $node->subtitle }}</p>
  </div>
</section>
<main class="wrap" style="padding:2rem 1.25rem 6rem">
  @if(!empty($trail))
    <nav class="kicker" style="margin-bottom:1rem">
      @foreach($trail as $i => $c)
        @if($c['href'])<a href="{{ $c['href'] }}">{{ $c['title'] }}</a>@else{{ $c['title'] }}@endif
        @if(!$loop->last) · @endif
      @endforeach
    </nav>
  @endif
  <p class="lede">{!! \App\Support\Linker::html($club, $node->summary ?: $node->body) !!}</p>
  @if($parents->count())
    <p class="kicker">Fait partie de</p>
    @foreach($parents as $p)
      <a class="chip" href="{{ \App\Support\Engine::href($p) }}">{{ \App\Support\Vocab::parentPhrase($p->kind) }} {{ $p->title }}</a>
    @endforeach
  @endif
  @if($children->count())
    <h2 class="font-display" style="margin-top:1.5rem">Fiches liées</h2>
    <div class="grid-3">
      @foreach($children as $c)
        <a class="card card-film" href="/n/{{ $club->slug }}/f/{{ $c->slug }}">
          <img src="{{ $c->hero }}" alt="{{ $c->title }}">
          <div class="pad"><p class="kicker">{{ \App\Support\Vocab::kind($c->kind) }}</p><h3 class="font-display">{{ $c->title }}</h3></div>
        </a>
      @endforeach
    </div>
  @endif
  @foreach($cck as $f)
    @include('partials.cck-render', ['f'=>$f, 'surface'=>'fiche'])
  @endforeach
  @if(!empty($also))
    <h2 class="font-display" style="margin-top:1.8rem">Aussi dans cet univers</h2>
    <div class="rel">
      @foreach($also as $a)
        <a class="chip" href="{{ $a['href'] }}">{{ $a['title'] }}</a>
      @endforeach
    </div>
  @endif
  @if($sibs->count()>=2)
    <p style="margin-top:1.5rem"><a class="btn-line" href="/n/{{ $club->slug }}/vs/{{ $sibs[0]->slug }}/{{ $sibs[1]->slug }}">Comparer</a></p>
  @endif
</main>
@endsection
