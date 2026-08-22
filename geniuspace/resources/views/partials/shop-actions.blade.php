{{-- Boutons d’une carte produit : labels depuis node_actions.scope = shop_card. --}}
@php
  $actions = $actions ?? ($chrome['shopCardActions'] ?? collect());
@endphp
<div class="rel" style="margin-top:.7rem">
  @forelse($actions as $a)
    @include('partials.action-button', ['action' => $a, 'node' => $node, 'product' => $p])
  @empty
    <form method="post" action="/cart">@csrf<input type="hidden" name="product_id" value="{{ $p->id }}"><button class="btn" type="submit">Ajouter au panier</button></form>
    <a class="btn-line" href="/n/{{ $node->slug }}/p/{{ $p->id }}">Fiche</a>
  @endforelse
</div>
