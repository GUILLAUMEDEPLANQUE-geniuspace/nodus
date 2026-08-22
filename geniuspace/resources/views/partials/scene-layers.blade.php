{{-- Calques de scène, positions en %. Clic → salle, fiche, panier, URL. --}}
@if(($layers ?? collect())->count())
<div class="layers" aria-hidden="false">
  @foreach($layers as $l)
    @php $href = \App\Support\Chrome::layerHref($node, $l); @endphp
    <div class="layer layer-{{ $l->kind }} motion-{{ $l->motion ?: 'none' }}"
         style="left:{{ $l->x }}%;top:{{ $l->y }}%;width:{{ $l->w }}%;height:{{ $l->h }}%;opacity:{{ $l->opacity }};z-index:{{ (int) $l->z + 2 }};animation-delay:{{ (int) $l->delay_ms }}ms">
      @if($l->kind === 'image' && $l->src)
        @if($href)<a href="{{ $href }}">@endif
        <img src="{{ $l->src }}" alt="{{ $l->label }}">
        @if($href)</a>@endif
      @elseif($l->kind === 'video' && $l->src)
        <video src="{{ $l->src }}" muted loop autoplay playsinline></video>
      @elseif($l->kind === 'button')
        <a class="btn" href="{{ $href ?: '#' }}">{{ $l->body ?: $l->label }}</a>
      @elseif($l->kind === 'shape')
        <div class="layer-shape"></div>
      @else
        @if($href)<a href="{{ $href }}">@endif
        <p class="layer-text">{{ $l->body ?: $l->label }}</p>
        @if($href)</a>@endif
      @endif
    </div>
  @endforeach
</div>
@endif
