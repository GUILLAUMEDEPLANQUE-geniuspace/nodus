@extends('layouts.app')
@section('title', $left->title.' vs '.$right->title.' — '.$node->title)
@section('description', 'Comparateur : '.$left->title.' et '.$right->title)
@section('canonical', url('/n/'.$node->slug.'/vs/'.$left->slug.'/'.$right->slug))
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@'.'context'=>'https://schema.org','@'.'type'=>'ItemList','name'=>$left->title.' vs '.$right->title,'itemListElement'=>[['@'.'type'=>'ListItem','position'=>1,'url'=>url('/n/'.$left->slug),'name'=>$left->title],['@'.'type'=>'ListItem','position'=>2,'url'=>url('/n/'.$right->slug),'name'=>$right->title]]], JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem">
  <p class="kicker"><a href="/n/{{ $node->slug }}">{{ $node->title }}</a> · comparateur</p>
  <h1 class="font-display" style="font-size:2.4rem">{{ $left->title }} vs {{ $right->title }}</h1>
  <div class="grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
    @foreach([[$left,$cckA],[$right,$cckB]] as [$n,$cck])
      <article class="card" style="padding:1rem">
        <p class="kicker">{{ $n->kind }}</p>
        <h2 class="font-display"><a href="/n/{{ $n->slug }}">{{ $n->title }}</a></h2>
        <p>{{ $n->summary }}</p>
        @foreach($cck as $f)
          @include('partials.cck-render', ['f'=>$f])
        @endforeach
      </article>
    @endforeach
  </div>
</main>
@endsection
