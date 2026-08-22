@extends('layouts.vera')
@section('title', 'Entreprises — honneur public | Vera')
@section('description', 'Maisons Vera : honneur, pacte, offres à salaire publié. Pas une CVthèque.')
@section('canonical', url('/n/vera/entreprises'))
@php $cos = array_values(\App\Support\VeraCatalog::companies()); $jobs = \App\Support\VeraCatalog::jobs(); @endphp
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <p class="vera-kicker">Maisons</p>
  <h1>Entreprises</h1>
  <p class="vera-lead">L’honneur est un chiffre. On ne vend pas une meilleure place.</p>
  <div class="vera-grid g2" style="margin-top:1.6rem">
    @foreach($cos as $c)
      @php $n = count(array_filter($jobs, fn($j)=>$j['companySlug']===$c['slug'])); @endphp
      <a class="v-card" href="/n/vera/maisons/{{ $c['slug'] }}">
        <div class="job-head">
          <span class="mark">{{ mb_substr($c['name'],0,1) }}</span>
          <div>
            <h3>{{ $c['name'] }}</h3>
            <p style="margin:0;color:var(--muted)">{{ $c['tagline'] }}</p>
          </div>
        </div>
        <p>{{ $c['industry'] }} · {{ $c['hqCity'] }} · {{ $c['sizeBand'] }}</p>
        <div class="chips">
          <span class="badge">Honneur {{ $c['honorScore'] }}</span>
          <span class="badge">Réponse {{ $c['slaDays'] }} j</span>
          <span class="badge">{{ $n }} offres</span>
        </div>
      </a>
    @endforeach
  </div>
</div>
@endsection
