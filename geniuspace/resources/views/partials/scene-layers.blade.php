{{-- Calques de scène. Clic → lieu + preuve « a visité ». --}}
@if(($layers ?? collect())->count())
<div class="layers" aria-hidden="false" data-visit-url="/n/{{ $node->slug }}/visite">
  @foreach($layers as $l)
    @php $href = \App\Support\Chrome::layerHref($node, $l); @endphp
    <div class="layer layer-{{ $l->kind }} motion-{{ $l->motion ?: 'none' }}"
         style="left:{{ $l->x }}%;top:{{ $l->y }}%;width:{{ $l->w }}%;height:{{ $l->h }}%;opacity:{{ $l->opacity }};z-index:{{ (int) $l->z + 2 }};animation-delay:{{ (int) $l->delay_ms }}ms">
      @if($l->kind === 'image' && $l->src)
        @if($href)<a href="{{ $href }}" data-visit="{{ $l->label }}" data-target="{{ $l->action_target }}">@endif
        <img src="{{ $l->src }}" alt="{{ $l->label }}">
        @if($href)</a>@endif
      @elseif($l->kind === 'video' && $l->src)
        <video src="{{ $l->src }}" muted loop autoplay playsinline></video>
      @elseif($l->kind === 'button')
        <a class="btn" href="{{ $href ?: '#' }}" data-visit="{{ $l->label }}" data-target="{{ $l->action_target }}">{{ $l->body ?: $l->label }}</a>
      @elseif($l->kind === 'shape')
        <div class="layer-shape"></div>
      @else
        @if($href)<a href="{{ $href }}" data-visit="{{ $l->label }}" data-target="{{ $l->action_target }}">@endif
        <p class="layer-text">{{ $l->body ?: $l->label }}</p>
        @if($href)</a>@endif
      @endif
    </div>
  @endforeach
</div>
<script>
(function(){
  const root = document.querySelector('.layers[data-visit-url]');
  if (!root) return;
  const url = root.getAttribute('data-visit-url');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  root.querySelectorAll('a[data-visit]').forEach(a => {
    a.addEventListener('click', function(e){
      const href = a.getAttribute('href');
      if (!href || href === '#') return;
      e.preventDefault();
      fetch(url, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrf, 'Accept':'application/json', 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest'},
        credentials: 'same-origin',
        body: JSON.stringify({target: a.getAttribute('data-target') || '', label: a.getAttribute('data-visit') || ''})
      }).finally(() => { location.href = href; });
    });
  });
})();
</script>
@endif
