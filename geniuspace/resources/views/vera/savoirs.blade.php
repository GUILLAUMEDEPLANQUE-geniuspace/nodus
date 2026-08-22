@extends('layouts.vera')
@section('title', 'Fiches Vera — hub de connaissance pour l’emploi et la formation')
@section('description', 'Fiches métier : marché, robotique, droit, compta, création d’entreprise. Liées aux offres. Un module avant de postuler si le geste manque.')
@section('canonical', url('/n/vera/savoirs'))
@php
  $cats = \App\Support\VeraCatalog::json('savoirs-cats');
  $arts = \App\Support\VeraCatalog::json('savoirs-arts');
  $featured = $arts[0] ?? null;
  $tones = ['marche'=>'#1b4332','metiers'=>'#3d4f66','robotique'=>'#6b5344','droit'=>'#8b3a2a','compta'=>'#2c3338','creation'=>'#3f6b4e','interculturel'=>'#8a6a2f','terrain'=>'#1b4332'];
@endphp
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · Fiches</nav>
  <p class="vera-kicker" style="margin-top:.8rem">Le geste avant le titre</p>
  <h1 style="font-size:clamp(2rem,6vw,3.6rem);max-width:22ch">Le hub où le métier s’écrit — et mène à l’offre.</h1>
  <p class="vera-lead">Pas un forum. Pas un LMS. Des fiches tenues par les entreprises et les candidats : marché, droit, compta, robotique, terrain. Chaque fiche ouvre des offres. Si le geste manque, vous suivez un module ici, puis vous tenez l’épreuve.</p>
  <ul class="vera-grid g4" style="list-style:none;padding:0;margin-top:2.2rem">
    @foreach($cats as $c)
      @php $n = count(array_filter($arts, fn($a)=>$a['cat']===$c['slug'])); @endphp
      <li>
        <a class="v-card" href="/n/vera/savoirs/{{ $c['slug'] }}" style="overflow:hidden;padding:0">
          <span style="display:block;height:.4rem;background:{{ $tones[$c['slug']] ?? '#1b4332' }}"></span>
          <span style="display:block;padding:1.1rem 1.2rem">
            <p class="vera-kicker">{{ $c['kicker'] }}</p>
            <h2>{{ $c['title'] }}</h2>
            <p>{{ $c['description'] }}</p>
            <p style="font-size:.75rem;color:var(--subtle)">{{ $n }} fiche{{ $n>1?'s':'' }}</p>
          </span>
        </a>
      </li>
    @endforeach
  </ul>
  @if($featured)
    <article class="v-card" style="margin-top:2.4rem;padding:0;overflow:hidden">
      <div style="height:.45rem;background:{{ $tones[$featured['cat']] ?? '#1b4332' }}"></div>
      <div style="padding:1.4rem 1.6rem">
        <p class="vera-kicker">À la une · {{ $featured['cat'] }} · {{ $featured['minutes'] }} min · Proof {{ $featured['proof'] }}</p>
        <h2><a href="/n/vera/savoirs/{{ $featured['cat'] }}/{{ $featured['slug'] }}">{{ $featured['title'] }}</a></h2>
        <p>{{ $featured['excerpt'] }}</p>
      </div>
    </article>
  @endif
  <div class="vera-grid g2" style="margin-top:1.2rem">
    @foreach(array_slice($arts,1) as $a)
      <a class="v-card" href="/n/vera/savoirs/{{ $a['cat'] }}/{{ $a['slug'] }}">
        <p class="vera-kicker">{{ $a['cat'] }} · {{ $a['minutes'] }} min · proof {{ $a['proof'] }}</p>
        <h3>{{ $a['title'] }}</h3>
        <p>{{ $a['excerpt'] }}</p>
      </a>
    @endforeach
  </div>
</div>
@endsection
