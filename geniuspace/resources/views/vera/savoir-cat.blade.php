@extends('layouts.vera')
@section('title', ($cat['seoTitle'] ?? $cat['title']).' | Vera')
@section('description', $cat['seoDescription'] ?? $cat['description'])
@section('canonical', url('/n/vera/savoirs/'.$cat['slug']))
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · <a href="/n/vera/savoirs">Fiches</a> · {{ $cat['title'] }}</nav>
  <p class="vera-kicker" style="margin-top:.8rem">{{ $cat['kicker'] }}</p>
  <h1>{{ $cat['title'] }}</h1>
  <p class="vera-lead">{{ $cat['description'] }}</p>
  <div class="vera-grid g2" style="margin-top:1.6rem">
    @foreach($arts as $a)
      <a class="v-card" href="/n/vera/savoirs/{{ $a['cat'] }}/{{ $a['slug'] }}">
        <p class="vera-kicker">{{ $a['minutes'] }} min · proof {{ $a['proof'] }} · {{ $a['author'] }}</p>
        <h3>{{ $a['title'] }}</h3>
        <p>{{ $a['excerpt'] }}</p>
      </a>
    @endforeach
  </div>
</div>
@endsection
