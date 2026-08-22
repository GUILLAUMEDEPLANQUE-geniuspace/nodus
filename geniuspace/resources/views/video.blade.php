@extends('layouts.app')
@section('title', $media->title.' | '.$node->title)
@section('description', $media->transcript)
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'VideoObject','name'=>$media->title,'description'=>$media->transcript,'embedUrl'=>url()->current()], JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem" x-data="{ t:0, granted: {{ $media->access==='free' ? 'true':'false' }}, teaser: {{ $media->teaser_sec }} }">
    <a class="kicker" href="/n/{{ $node->slug }}">{{ $node->title }}</a>
    <h1 class="font-display" style="font-size:2.5rem">{{ $media->title }}</h1>
    <div class="video-box" style="border-radius:1rem;overflow:hidden;border:1px solid var(--border)">
        <video src="{{ $src }}" controls playsinline
            @timeupdate="t=$event.target.currentTime; if(!granted && teaser && t>=teaser){ $event.target.pause() }"></video>
        <div class="paywall" x-show="!granted && teaser && t>=teaser">
            <button class="btn" type="button" @click="granted=true">Débloquer {{ $media->price }}</button>
        </div>
    </div>
    <p class="muted" style="margin-top:1rem">{{ $media->transcript }}</p>
    <pre class="muted" style="white-space:pre-wrap;font-family:inherit">{{ $media->chapters }}</pre>
</main>
@endsection
