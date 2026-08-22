@extends('layouts.app')
@section('title', $page->title.' — '.$node->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($page->body), 160))
@section('canonical', url('/n/'.$node->slug.'/guide/'.\Illuminate\Support\Str::slug($page->title)))
@push('jsonld')
<script type="application/ld+json">{!! json_encode([
  '@'.'context'=>'https://schema.org','@'.'type'=>'TechArticle',
  'headline'=>$page->title,'articleBody'=>$page->body,
  'url'=>url('/n/'.$node->slug.'/guide/'.\Illuminate\Support\Str::slug($page->title)),
  'isPartOf'=>['@'.'type'=>'CreativeWork','name'=>$node->title,'url'=>url('/n/'.$node->slug)],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<article class="wrap {{ $node->kind==='auto' ? 'skin-auto' : '' }}" style="padding:2.5rem 1.25rem 6rem;max-width:42rem">
  <p class="kicker"><a href="/n/{{ $node->slug }}/guides">Guides · {{ $node->title }}</a></p>
  <h1 class="font-display" style="font-size:2.6rem">{{ $page->title }}</h1>
  <p class="lede">{!! \App\Support\Linker::html($node, $page->body) !!}</p>
  <p style="margin-top:2rem"><a class="btn-line" href="/n/{{ $node->slug }}/bounties">Enrichir (bounty)</a>
     <a class="btn-ghost" href="/n/{{ $node->slug }}/radar">Radar</a></p>
</article>
@endsection
