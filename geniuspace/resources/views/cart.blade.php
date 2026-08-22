@extends('layouts.app')
@section('title', 'Panier — Geniuspace')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem;max-width:36rem">
  <p class="kicker">Chaudron</p>
  <h1 class="font-display">Panier</h1>
  @forelse($cart as $id => $row)
    <p class="card" style="padding:1rem;margin:.4rem 0"><strong>{{ $row['title'] }}</strong> <span class="primary">{{ $row['price'] }}</span></p>
  @empty
    <p class="muted">Vide. Une pièce du Club 205 ?</p>
    <p><a class="btn" href="/n/lumen/boutique_expert">Voir la vitrine Lumen</a></p>
  @endforelse
  @if($cart)
    <form method="post" action="/cart/checkout">@csrf<button class="btn" type="submit">Payer (ledger)</button></form>
  @endif
</main>
@endsection
