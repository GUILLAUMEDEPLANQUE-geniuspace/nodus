{{-- Peau d’une salle : couleur, fond, anim, SEO. --}}
@php
  $sk = $tabs->firstWhere('key', $key);
@endphp
<section class="room {{ ($sk->animate ?? false) ? 'room-anim' : '' }}"
  x-show="tab==='{{ $key }}'"
  x-cloak
  style="--room-color: {{ $sk->color ?? '#c9a36a' }}; {{ ($sk->bg ?? '') ? 'background-image:url('.$sk->bg.');' : '' }}">
  @if($sk && ($sk->seo_title || $sk->seo_desc))
    <header class="wrap" style="padding-top:1.2rem">
      @if($sk->seo_title)<h2 class="font-display" style="font-size:2rem;color:var(--room-color)">{{ $sk->seo_title }}</h2>@endif
      @if($sk->seo_desc)<p class="muted">{{ $sk->seo_desc }}</p>@endif
    </header>
  @endif
  {{ $slot }}
</section>
