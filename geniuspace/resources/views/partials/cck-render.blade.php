{{-- Rendu public SEO d’un champ CCK. Le type dicte schema.org. --}}
@php
  $drip = !empty($f->drip_at) && strtotime($f->drip_at) > time();
@endphp
@if($drip)
  <p class="muted">Contenu drip — visible {{ $f->drip_at }}</p>
@else
<article class="cck-f" data-type="{{ $f->type }}">
  <h3 class="font-display" style="font-size:1.2rem;margin:0.4rem 0">{{ $f->name }}</h3>
  @switch($f->type)
    @case('image')
      <img src="{{ $f->value }}" alt="{{ $f->seo_title ?: $f->name }}" style="max-width:100%;border-radius:1rem">
      @break
    @case('gallery')
      <img src="{{ $f->value }}" alt="{{ $f->name }}" style="max-width:100%;border-radius:1rem">
      @break
    @case('video')
      <video src="{{ $f->value }}" controls poster="" style="width:100%;border-radius:1rem"></video>
      @break
    @case('audio')
      <audio src="{{ $f->value }}" controls></audio>
      @break
    @case('url')
      <a class="primary" href="{{ $f->value }}">{{ $f->value }}</a>
      @break
    @case('email')
      <a href="mailto:{{ $f->value }}">{{ $f->value }}</a>
      @break
    @case('html')
      <div>{!! $f->value !!}</div>
      @break
    @case('geo')
      @if($f->lat)
        <iframe title="Carte {{ $f->name }}" src="https://www.openstreetmap.org/export/embed.html?bbox={{ $f->lng-0.02 }}%2C{{ $f->lat-0.02 }}%2C{{ $f->lng+0.02 }}%2C{{ $f->lat+0.02 }}&layer=mapnik" style="width:100%;height:12rem;border:0;border-radius:1rem"></iframe>
      @endif
      @break
    @case('pay_download')
      <p class="primary">Pay to download · {{ $f->value }}</p>
      @break
    @case('og')
      <p class="muted">OG · {{ $f->value }}</p>
      @break
    @case('password')
      <p class="muted">Champ privé</p>
      @break
    @default
      <p>{{ $f->value }}</p>
  @endswitch
</article>
@endif
