@extends('layouts.app')
@section('title', $thread->title.' | '.$node->title)
@section('description', $thread->body)
@section('canonical', url('/n/'.$node->slug.'/t/'.$thread->id))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode([
  '@'.'context' => 'https://schema.org',
  '@'.'type' => 'DiscussionForumPosting',
  'headline' => $thread->title,
  'articleBody' => $thread->body,
  'author' => ['@'.'type' => 'Person', 'name' => $thread->author],
  'image' => url($thread->cover ?: $node->hero),
  'interactionStatistic' => [
    ['@'.'type' => 'InteractionCounter', 'interactionType' => 'https://schema.org/CommentAction', 'userInteractionCount' => $replies->count()],
  ],
  'url' => url()->current(),
  'isPartOf' => ['@'.'type'=>'CreativeWork','name'=>$node->title,'url'=>url('/n/'.$node->slug.'/forum')],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="holo {{ $node->kind==='auto'?'skin-auto':'' }}" x-data="{ mode: 'legacy' }">
  <aside class="holo-hero">
    <div class="holo-topic">
      <img src="{{ $thread->cover ?: $node->hero }}" alt="">
      <div class="veil"></div>
      <div class="holo-copy">
        <p class="kicker"><a href="/n/{{ $node->slug }}/forum">Forum · {{ $node->title }}</a> · {{ $thread->author }}</p>
        <h1 class="font-display">{{ $thread->title }}</h1>
        <p>{!! \App\Support\Linker::html($node, $thread->body) !!}</p>
        <p class="muted">{{ $thread->views }} vues · {{ $thread->fires }} feux · {{ $replies->count() }} réponses</p>
      </div>
    </div>
  </aside>
  <section class="holo-pane">
    <header class="holo-pane-h">
      <button type="button" :class="mode==='legacy' && 'on'" @click="mode='legacy'">Legacy (SEO)</button>
      <button type="button" :class="mode==='live' && 'on'" @click="mode='live'">Live</button>
    </header>
    <div class="holo-stream">
      <div x-show="mode==='legacy'">
        @forelse($replies as $r)
          @include('partials.holo-reply', ['r'=>$r,'node'=>$node])
        @empty
          <p class="muted">Écrivez la première réponse indexable.</p>
        @endforelse
        <form method="post" action="/n/{{ $node->slug }}/t/{{ $thread->id }}/reply" class="holo-compose">
          @csrf
          <textarea name="body" required placeholder="Réponse. Relique / vidéo / fichier en pièces jointes natives."></textarea>
          <div class="holo-tools">
            <select name="product_id">
              <option value="">Relique…</option>
              @foreach($node->products as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
            </select>
            <select name="video_path">
              <option value="">Vidéo…</option>
              @foreach($node->media as $m)<option value="{{ $m->path }}">{{ $m->title }}</option>@endforeach
            </select>
            <select name="file_path">
              <option value="">Fichier…</option>
              @foreach($files as $f)<option value="{{ $f->path }}">{{ $f->title }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Publier</button>
          </div>
        </form>
      </div>
      <div x-show="mode==='live'">
        @foreach($live as $l)
          <div class="live-row">
            <span class="av">{{ mb_substr($l->author,0,2) }}</span>
            <p class="bubble"><span class="primary" style="font-size:0.75rem">{{ $l->author }}</span> {{ $l->body }}</p>
          </div>
        @endforeach
        <form method="post" action="/n/{{ $node->slug }}/t/{{ $thread->id }}/live">
          @csrf<input name="body" required placeholder="Live…" style="width:100%">
        </form>
      </div>
    </div>
  </section>
</div>
@endsection
