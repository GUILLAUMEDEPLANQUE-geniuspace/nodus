@extends('layouts.app')
@section('title', $user->name.' — CV Geniuspace')
@section('canonical', url('/cv/'.$user->id))
@push('jsonld')
<script type="application/ld+json">{!! json_encode(['@'.'context'=>'https://schema.org','@'.'type'=>'Person','name'=>$user->name,'url'=>url('/cv/'.$user->id),'hasCredential'=>$pack->map(fn($i)=>['@'.'type'=>'EducationalOccupationalCredential','name'=>$i->label])->values()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:36rem">
  <p class="kicker">Identité portable</p>
  <h1 class="font-display">{{ $user->name }}</h1>
  <p class="muted">{{ $user->nodecoins ?? 0 }} NodeCoins · {{ $pack->count() }} reliques</p>
  @foreach($pack as $it)
    <p class="card" style="padding:.8rem;margin:.35rem 0"><span class="kicker">{{ $it->kind }}</span> {{ $it->label }}</p>
  @endforeach
</main>
@endsection
