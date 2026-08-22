@extends('layouts.vera')
@php $c = $company; $C = \App\Support\VeraCatalog::class; @endphp
@section('title', $c['name'].' — offres, pacte, honneur | Vera')
@section('description', $c['about'])
@section('canonical', url('/n/vera/maisons/'.$c['slug']))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>$c['name'],'description'=>$c['about'],'address'=>['@type'=>'PostalAddress','addressLocality'=>$c['hqCity'],'addressCountry'=>$c['hqCountry']]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · <a href="/n/vera/entreprises">Entreprises</a> · {{ $c['name'] }}</nav>
  <div class="job-head" style="margin-top:1rem">
    <span class="mark" style="width:3.4rem;height:3.4rem;font-size:1.6rem">{{ mb_substr($c['name'],0,1) }}</span>
    <div>
      <h1>{{ $c['name'] }}</h1>
      <p style="color:var(--muted)">{{ $c['tagline'] }}</p>
    </div>
  </div>
  <div class="chips" style="margin:1rem 0">
    <span class="badge {{ $C::honorTone($c['honorScore']) }}">Honneur {{ $c['honorScore'] }} · {{ $C::honorCaption($c['honorScore'], $c['honorDue']??1) }}</span>
    <span class="badge">SLA {{ $c['slaDays'] }} j</span>
    <span class="badge">{{ $c['industry'] }}</span>
    <span class="badge">{{ $c['hqCity'] }}, {{ $c['hqCountry'] }}</span>
    <span class="badge">fondée {{ $c['foundedYear'] }}</span>
  </div>
  <p style="max-width:40rem">{{ $c['about'] }}</p>
  <h2>Valeurs écrites</h2>
  <div class="chips">@foreach($c['values']??[] as $v)<span class="badge">{{ $v }}</span>@endforeach</div>
  <h2 style="margin-top:2rem">Offres</h2>
  <div class="vera-grid" style="gap:.9rem">
    @foreach($jobs as $job)
      @include('vera.partials.job-card', ['job'=>$job])
    @endforeach
  </div>
</div>
@endsection
