{{-- Holo-forum : héros à gauche, Legacy riche à droite (vidéo / Drive / relique). --}}
<div class="holo">
  <aside class="holo-hero">
    @foreach($forumThreads as $t)
      <div class="holo-topic" x-show="openId==='{{ $t->id }}'" x-cloak>
        <img src="{{ $t->cover ?: $node->hero }}" alt="">
        <div class="veil"></div>
        <div class="holo-copy">
          <p class="kicker">Technique · {{ $t->author }} · {{ $t->views }} vues</p>
          <h1 class="font-display">{{ $t->title }}</h1>
          <p>{!! \App\Support\Linker::html($node, $t->body) !!}</p>
          <p class="muted">{{ $t->fires }} feux · {{ $t->replies_count }} réponses · <a href="/n/{{ $node->slug }}/t/{{ $t->id }}">page SEO ↗</a></p>
        </div>
      </div>
    @endforeach
    <nav class="holo-rail">
      @foreach($forumThreads as $t)
        <button type="button" :class="openId==='{{ $t->id }}' && 'on'" @click="openId='{{ $t->id }}'">{{ \Illuminate\Support\Str::limit($t->title, 48) }}</button>
      @endforeach
    </nav>
  </aside>
  <section class="holo-pane">
    <header class="holo-pane-h">
      <button type="button" :class="mode==='legacy' && 'on'" @click="mode='legacy'">Legacy (SEO)</button>
      <button type="button" :class="mode==='live' && 'on'" @click="mode='live'">Live</button>
    </header>
    <div class="holo-stream">
      @foreach($forumThreads as $t)
        <div x-show="openId==='{{ $t->id }}' && mode==='legacy'">
          @forelse($replies->get($t->id, collect()) as $r)
            @include('partials.holo-reply', ['r'=>$r,'node'=>$node])
          @empty
            <p class="muted">Première réponse indexable — Google la lira.</p>
          @endforelse
          <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/reply" class="holo-compose" @submit="ping('Toi')">
            @csrf
            <textarea name="body" required placeholder="Répondre. @joint-culasse-205-gti maillage. Relique / vidéo / fichier ci-dessous."></textarea>
            <div class="holo-tools">
              <select name="product_id"><option value="">Relique</option>@foreach($node->products as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select>
              <select name="video_path"><option value="">Vidéo</option>@foreach($node->media as $m)<option value="{{ $m->path }}">{{ $m->title }}</option>@endforeach</select>
              <select name="file_path"><option value="">Fichier</option>@foreach($files as $f)<option value="{{ $f->path }}">{{ $f->title }}</option>@endforeach</select>
              <button class="btn" type="submit">Publier</button>
            </div>
          </form>
        </div>
        <div x-show="openId==='{{ $t->id }}' && mode==='live'">
          @foreach($live->get($t->id, collect()) as $l)
            <div class="live-row">
              <img src="{{ \App\Support\Faces::of($l->author) }}" alt="" style="width:2rem;height:2rem;border-radius:999px;object-fit:cover">
              <p class="bubble"><span class="primary" style="font-size:0.75rem">{{ $l->author }}</span> {{ $l->body }}</p>
            </div>
          @endforeach
          <form method="post" action="/n/{{ $node->slug }}/t/{{ $t->id }}/live" @submit="ping('Toi')">
            @csrf<input name="body" required placeholder="Live…" style="width:100%;margin-top:.5rem">
          </form>
        </div>
      @endforeach
    </div>
    <form method="post" action="/forum" class="holo-new">
      @csrf
      <input type="hidden" name="slug" value="{{ $node->slug }}">
      <input name="title" required placeholder="Nouveau sujet (Google indexe)">
      <input name="body" required placeholder="Accroche">
      <button class="btn-line" type="submit">+ Sujet</button>
    </form>
  </section>
</div>
