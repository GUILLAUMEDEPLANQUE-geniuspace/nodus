@extends('layouts.app')
@section('title', $product->title.' — '.$product->price.' · '.$product->rating.'/5 | '.$node->title)
@section('description', $product->summary)
@section('canonical', url('/n/'.$node->slug.'/p/'.$product->id))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@'.'context'=>'https://schema.org','@'.'graph'=>[[
  '@'.'type' => $product->rwa ? 'VisualArtwork' : 'Product',
  'name' => $product->title,
  'description' => $product->summary,
  'image' => url($product->image),
  'offers' => ['@'.'type'=>'Offer','priceCurrency'=>'EUR','price'=>$product->priceAmount(),'availability'=>'https://schema.org/InStock'],
  'url' => url('/n/'.$node->slug.'/p/'.$product->id),
]]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="cc" x-data="{
  t: 0, granted: {{ $media && $media->access === 'free' ? 'true' : 'false' }},
  teaser: {{ $media->teaser_sec ?? 0 }},
  energy: 0, loot: false,
  add(el) { this.energy = Math.min(100, this.energy + parseInt(el.dataset.energy||'20')); if(this.energy>=100){ this.loot=true; this.energy=0 } }
}">
    <section class="cc-col cc-left">
        <div class="video-box">
            <video id="ccVid" src="{{ $src }}" poster="{{ $product->image }}" playsinline
                @timeupdate="t = $event.target.currentTime; if(!granted && teaser && t >= teaser){ $event.target.pause() }"></video>
            <div class="paywall" x-show="!granted && teaser && t >= teaser">
                <button class="btn" type="button" @click="granted=true; document.getElementById('ccVid').play()">Débloquer {{ $media->price ?? '' }}</button>
            </div>
        </div>
        <p class="kicker" style="padding:0.75rem">{{ $media->title ?? 'Masterclass' }} · {{ $media->mode ?? 'shop' }}</p>
        <p class="muted" style="padding:0 0.75rem 1rem">{{ $media->transcript ?? $product->summary }}</p>
    </section>
    <section class="cc-col" style="position:relative;display:flex;flex-wrap:wrap;gap:1rem;align-items:center;justify-content:center;padding:1.5rem">
        <div class="holo-floor"></div>
        @foreach($products as $p)
            <article class="product" draggable="true" data-id="{{ $p->id }}" data-energy="{{ $p->energy }}"
                @dragstart="$event.dataTransfer.setData('id', '{{ $p->id }}')">
                @if($p->rwa || str_contains(strtolower($p->stock), 'unique'))
                    <span class="badge">Pièce unique</span>
                @endif
                <img src="{{ $p->image }}" alt="{{ $p->title }}" style="height:10rem;width:100%;object-fit:cover;border-radius:0.6rem">
                <p class="kicker" style="margin-top:0.4rem">{{ $p->rwa ? 'RWA certifié' : $p->kind }}</p>
                <h2 class="font-display" style="margin:0.2rem 0;font-size:1.2rem">{{ $p->title }}</h2>
                <p class="primary" style="font-family:ui-monospace,monospace">{{ $p->price }}</p>
                <p class="muted" style="font-size:0.75rem">{{ $p->rating }}/5 · {{ $p->votes }} avis</p>
                    <form method="post" action="/cart" style="margin-top:0.5rem">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <button class="btn" type="submit" style="width:100%;height:2.4rem">Chaudron</button>
                </form>
            </article>
        @endforeach
    </section>
    <aside class="cc-col cc-right">
        @if($goal)
            <p class="kicker">Quête globale <span style="float:right">{{ $goal->current }} / {{ $goal->target }} €</span></p>
            <div class="bar"><span style="width: {{ min(100, (int) round($goal->current / max(1,$goal->target) * 100)) }}%"></span></div>
            <p class="muted" style="font-size:0.8rem">{{ $goal->reward }}</p>
        @endif
        <p class="kicker" style="margin-top:1rem">Énergie <span x-text="energy + ' / 100'"></span></p>
        <div class="bar"><span :style="'width:'+energy+'%'"></span></div>
        <div class="cauldron" @dragover.prevent @drop.prevent="add($event.target)">
            <p class="muted">Déposez l'œuvre</p>
        </div>
        <form method="post" action="/cart/checkout">
            @csrf
            <button class="btn" type="submit" style="width:100%;margin-top:0.75rem">Acquérir</button>
        </form>
        <p class="muted" style="font-size:0.75rem;margin-top:0.75rem">MP4 sur le disque du serveur (mutu / VPS / dédié) : <code>public/media</code>. Drive : <a href="/drive">/drive</a>.</p>
    </aside>
    <div x-show="loot" style="position:fixed;inset:0;background:color-mix(in srgb,var(--bg) 90%,transparent);display:grid;place-items:center;z-index:80">
        <div class="card" style="padding:2rem;text-align:center">
            <h3 class="font-display">Énergie atteinte</h3>
            <p class="muted">Wallpaper ajouté au Drive.</p>
            <button class="btn" type="button" @click="loot=false">Continuer</button>
        </div>
    </div>
</div>
@endsection
