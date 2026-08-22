@extends('layouts.vera')
@php $a = $article; $cat = $catRow; @endphp
@section('title', ($a['title'] ?? 'Fiche').' | Vera')
@section('description', $a['excerpt'] ?? '')
@section('canonical', url('/n/vera/savoirs/'.($a['cat']??'').'/'.($a['slug']??'')))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'Article','headline'=>$a['title'],'description'=>$a['excerpt'],'author'=>['@type'=>'Person','name'=>$a['author']],'wordCount'=>str_word_count(implode(' ',$a['body']??[]))], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem;max-width:44rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · <a href="/n/vera/savoirs">Fiches</a> · <a href="/n/vera/savoirs/{{ $a['cat'] }}">{{ $cat['title'] ?? $a['cat'] }}</a></nav>
  <p class="vera-kicker" style="margin-top:1rem">{{ $cat['kicker'] ?? $a['cat'] }} · {{ $a['minutes'] }} min · Proof {{ $a['proof'] }} · {{ $a['author'] }}</p>
  <h1>{{ $a['title'] }}</h1>
  <p class="vera-lead">{{ $a['excerpt'] }}</p>
  @foreach($a['body']??[] as $p)<p>{{ $p }}</p>@endforeach
  @if(!empty($a['skills']))
    <div class="chips" style="margin-top:1rem">@foreach($a['skills'] as $s)<span class="badge">{{ $s }}</span>@endforeach</div>
  @endif
  @if(!empty($a['jobs']))
    <h2 style="margin-top:1.8rem">Offres liées</h2>
    <ul>@foreach($a['jobs'] as $js)<li><a href="/n/vera/offres/{{ $js }}" style="color:var(--primary)">{{ $js }}</a></li>@endforeach</ul>
  @endif
  <p style="margin-top:1.6rem"><a class="vera-btn" href="/n/vera/preuve">Tenir l’épreuve</a></p>
</div>
@endsection
