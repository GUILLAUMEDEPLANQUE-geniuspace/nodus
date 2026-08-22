@extends('layouts.vera')
@section('title', 'Viviers — seniors, RSA, slashers, reprise | Vera')
@section('description', 'Bassin de gens que Indeed ignore : RSA + freins, seniors à la journée, multi-activité, binômes, reprise. Pas un vivier CRM.')
@section('canonical', url('/n/vera/viviers'))
@php $viviers = \App\Support\VeraCatalog::json('viviers'); @endphp
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <p class="vera-kicker">Profils oubliés</p>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem)">Seniors, RSA, reprise</h1>
  <p class="vera-lead">Indeed ignore ces bassins. Ici on nomme le frein, le créneau, le duo. Publier est gratuit. On paie seulement un candidat qui a réussi le test.</p>
  <div class="vera-grid g2" style="margin-top:1.8rem">
    @foreach($viviers as $v)
      <a class="v-card" href="/n/vera/viviers/{{ $v['slug'] }}">
        <p class="vera-kicker">{{ $v['kicker'] }}</p>
        <h2>{{ $v['name'] }}</h2>
        <p>{{ $v['description'] }}</p>
      </a>
    @endforeach
  </div>
</div>
@endsection
