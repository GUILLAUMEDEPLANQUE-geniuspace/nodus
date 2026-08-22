@extends('layouts.app')
@section('title', $thread->title.' | '.$node->title)
@section('description', $thread->body)
@section('canonical', url('/n/'.$node->slug.'/t/'.$thread->id))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'DiscussionForumPosting','headline'=>$thread->title,'articleBody'=>$thread->body,'author'=>['@type'=>'Person','name'=>$thread->author],'url'=>url()->current()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem 6rem">
    <a class="kicker" href="/n/{{ $node->slug }}">{{ $node->title }} · Forum</a>
    <h1 class="font-display" style="font-size:3rem">{{ $thread->title }}</h1>
    <p class="muted">{{ $thread->author }}</p>
    <p style="max-width:40rem;font-size:1.1rem">{{ $thread->body }}</p>
    <p class="muted" style="margin-top:2rem">Vue Legacy : ce texte est indexé. Le Live (Telegram-like) reste hors Google.</p>
</main>
@endsection
