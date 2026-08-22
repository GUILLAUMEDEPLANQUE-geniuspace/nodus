@extends('layouts.app')
@section('title', $thread->title.' | '.$node->title)
@section('description', $thread->body)
@section('canonical', url('/n/'.$node->slug.'/t/'.$thread->id))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'DiscussionForumPosting',
  'headline' => $thread->title,
  'articleBody' => $thread->body,
  'author' => ['@type' => 'Person', 'name' => $thread->author],
  'interactionStatistic' => [
    ['@type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/CommentAction', 'userInteractionCount' => $replies->count()],
  ],
  'url' => url()->current(),
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 6rem" x-data="{ mode: 'legacy' }">
    <a class="kicker" href="/n/{{ $node->slug }}?tab=forum">{{ $node->title }} · Holo-Forum</a>
    <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.2rem)">{{ $thread->title }}</h1>
    <p class="muted">{{ $thread->author }} · {{ $thread->views }} vues · {{ $thread->fires }} feux</p>
    <p style="max-width:40rem;font-size:1.1rem">{{ $thread->body }}</p>
    <div class="rel" style="margin:1rem 0">
        <button class="chip" type="button" @click="mode='legacy'">Top SEO</button>
        <button class="chip" type="button" @click="mode='live'">Live</button>
        <a class="chip" href="/n/{{ $node->slug }}?tab=forum&tid={{ $thread->id }}">Ouvrir dans le feed</a>
    </div>
    <section x-show="mode==='legacy'">
        <h2 class="font-display">Legacy (indexé)</h2>
        @forelse($replies as $r)
            <article class="legacy-card">
                <p class="kicker">{{ $r->author }} · {{ $r->votes }} votes</p>
                <p>{{ $r->body }}</p>
            </article>
        @empty
            <p class="muted">Écrivez la première réponse indexable.</p>
        @endforelse
        <form method="post" action="/n/{{ $node->slug }}/t/{{ $thread->id }}/reply">
            @csrf
            <textarea name="body" required placeholder="Réponse structurée — Google lit ceci." style="width:100%;min-height:5rem"></textarea>
            <button class="btn" type="submit" style="margin-top:0.5rem">Publier au Legacy</button>
        </form>
    </section>
    <section x-show="mode==='live'">
        <h2 class="font-display">Live (éphémère)</h2>
        @foreach($live as $l)
            <div class="live-row">
                <span class="av">{{ mb_substr($l->author,0,2) }}</span>
                <p class="bubble"><span class="primary" style="font-size:0.75rem">{{ $l->author }}</span> {{ $l->body }}</p>
            </div>
        @endforeach
        <form method="post" action="/n/{{ $node->slug }}/t/{{ $thread->id }}/live">
            @csrf
            <input name="body" required placeholder="Live…" style="width:100%">
        </form>
    </section>
</main>
@endsection
