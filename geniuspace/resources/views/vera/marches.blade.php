@extends('layouts.vera')
@section('title', 'Marchés Europe — friction nommée | Vera')
@section('description', 'France, Allemagne, Pays-Bas, Irlande, Suède, Portugal, Royaume-Uni, Espagne. Ce qui voyage, ce qui freine.')
@section('canonical', url('/n/vera/marches'))
@php $markets = \App\Support\VeraCatalog::json('markets'); @endphp
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <p class="vera-kicker">Expansion</p>
  <h1>Marchés, friction nommée</h1>
  <p class="vera-lead">Pas une traduction du siège français. Des normes, des relecteurs, un test culture.</p>
  <div class="vera-grid g2" style="margin-top:1.6rem">
    @foreach($markets as $m)
      <article class="v-card">
        <p class="vera-kicker">{{ $m['code'] }} · {{ $m['testCulture'] }}</p>
        <h2>{{ $m['name'] }}</h2>
        <p>{{ $m['desk'] }}</p>
        <p><strong>Droit.</strong> {{ $m['law'] }}</p>
        <p><strong>Épreuve.</strong> {{ $m['testNote'] }}</p>
        <p><strong>GTM.</strong> {{ $m['gtm'] }}</p>
        <div class="chips">@foreach($m['partners']??[] as $p)<span class="badge">{{ $p }}</span>@endforeach</div>
        <div class="chips" style="margin-top:.4rem">@foreach($m['cities']??[] as $ci)<span class="badge">{{ $ci }}</span>@endforeach</div>
      </article>
    @endforeach
  </div>
</div>
@endsection
