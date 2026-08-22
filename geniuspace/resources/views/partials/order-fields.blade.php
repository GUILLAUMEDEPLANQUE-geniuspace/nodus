{{-- Options d’achat : taille, gravure, extra, logo. Champs audience=commande. --}}
@php
  $orderFields = $orderFields ?? \App\Support\Order::fields($p->id ?? '');
@endphp
@if($orderFields)
<div class="order-fields" style="display:grid;gap:.45rem;margin:.6rem 0">
  @foreach($orderFields as $f)
    @php $key = $f->field_key ?: \Illuminate\Support\Str::slug($f->name); $choices = \App\Support\Order::choices($f); @endphp
    <label style="display:grid;gap:.2rem">
      <span class="kicker">{{ $f->name }}</span>
      @if(($f->type ?? '') === 'select' && $choices)
        <select name="opt_{{ $key }}" style="height:2.2rem">
          @foreach($choices as $c)
            <option value="{{ $c['label'] }}">{{ $c['label'] }}@if($c['extra']) (+{{ $c['extra'] }} €)@endif</option>
          @endforeach
        </select>
      @elseif(in_array($f->type ?? '', ['file', 'image'], true))
        <input type="file" name="opt_{{ $key }}" accept="image/*,.pdf">
      @else
        <input type="text" name="opt_{{ $key }}" placeholder="{{ $f->name }}" maxlength="80">
      @endif
    </label>
  @endforeach
</div>
@endif
