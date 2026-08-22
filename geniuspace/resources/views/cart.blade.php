@extends('layouts.app')
@section('title', 'Panier — Geniuspace')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem;max-width:36rem">
  <p class="kicker">Chaudron</p>
  <h1 class="font-display">Panier</h1>
  @if(session('ok'))<p class="chip">{{ session('ok') }}</p>@endif
  @forelse($cart as $id => $row)
    <article class="card" style="padding:1rem;margin:.4rem 0">
      <p><strong>{{ $row['title'] }}</strong> <span class="primary">{{ $row['price'] }}</span></p>
      @if(!empty($row['options']['nego']))
        <p class="kicker">Prix tenu par l’hôte · {{ $row['options']['nego'] }}</p>
      @endif
      @if(!empty($row['options']))
        <p class="muted" style="margin:.3rem 0 0;font-size:.85rem">
          @foreach($row['options'] as $k => $v)
            <span class="chip">{{ $k }} · {{ $v }}</span>
          @endforeach
        </p>
      @endif
    </article>
  @empty
    <p class="muted">Vide. Une œuvre Lumen ?</p>
    <p><a class="btn" href="/n/lumen/boutique_expert">Voir la vitrine Lumen</a></p>
  @endforelse
  @if($cart)
    <p class="kicker" style="margin-top:1rem">À régler · {{ number_format(($held ?? 0)/100, 2, ',', ' ') }} €</p>
    <form method="post" action="/cart/checkout" class="rel" style="margin-top:1rem">
      @csrf
      @forelse(($chrome['cartActions'] ?? collect()) as $a)
        <button class="{{ \App\Support\Chrome::cssClass($a) }}" type="submit" name="provider" value="{{ $a->action_key }}">{{ $a->label }}</button>
      @empty
        <button class="btn" type="submit">Payer le prix tenu</button>
      @endforelse
    </form>
    <p class="muted" style="font-size:.8rem;margin-top:.6rem">Le montant encaissé est celui de l’hôte, pas le tarif affiché. Stripe en prod, coffre ici.</p>
  @endif
</main>
@endsection
