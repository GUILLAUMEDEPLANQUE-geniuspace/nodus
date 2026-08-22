@extends('layouts.vera')
@php $v = $vivier; @endphp
@section('title', $v['title'])
@section('description', $v['description'])
@section('canonical', url('/n/vera/viviers/'.$v['slug']))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(fn($f)=>['@type'=>'Question','name'=>$f['q'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['a']]], $v['faqs'])], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem;max-width:44rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · <a href="/n/vera/viviers">Viviers</a> · {{ $v['name'] }}</nav>
  <p class="vera-kicker" style="margin-top:1rem">{{ $v['kicker'] }}</p>
  <h1>{{ $v['name'] }}</h1>
  <p class="vera-lead">{{ $v['description'] }}</p>
  @foreach($v['intro'] as $p)<p>{{ $p }}</p>@endforeach
  <h2 style="margin-top:2rem">FAQ</h2>
  @foreach($v['faqs'] as $f)
    <article class="v-card" style="margin-top:.7rem">
      <h3>{{ $f['q'] }}</h3>
      <p>{{ $f['a'] }}</p>
    </article>
  @endforeach
  <p style="margin-top:1.6rem"><a class="vera-btn" href="/n/vera/offres">Voir les offres</a></p>
</div>
@endsection
