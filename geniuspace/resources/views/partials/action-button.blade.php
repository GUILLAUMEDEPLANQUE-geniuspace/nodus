{{-- Un bouton dont le libellé / style / href viennent de node_actions. Le handler (panier, unlock) reste dans le code. --}}
@php
  $product = $product ?? null;
  $alpine = $alpine ?? '';
  $class = \App\Support\Chrome::cssClass($action);
  $href = \App\Support\Chrome::href($node, $action, $product);
@endphp
@if($action->action_key === 'add_cart' && $product)
  <form method="post" action="/cart" style="display:inline">
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">
    <button class="{{ $class }}" type="submit">{{ $action->label }}</button>
  </form>
@elseif(in_array($action->action_key, ['play', 'unlock'], true))
  <button class="{{ $class }}" type="button" {!! $alpine !!}>{{ $action->label }}</button>
@elseif($action->action_key === 'share')
  <a class="{{ $class }}" href="{{ $href }}" target="_blank" rel="noopener">{{ $action->label }}</a>
@else
  <a class="{{ $class }}" href="{{ $href }}">{{ $action->label }}</a>
@endif
