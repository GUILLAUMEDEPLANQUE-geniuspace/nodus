{{-- Réponse riche : texte + vidéo Drive + fichier locké + relique boutique. --}}
<article class="holo-card">
  <header class="holo-who">
    <img src="{{ $r->author_avatar ?? \App\Support\Faces::of($r->author) }}" alt="">
    <div>
      <strong>{{ $r->author }}</strong>
      @if($r->badge ?? '')<span class="holo-badge">{{ $r->badge }}</span>@endif
      <p class="muted" style="margin:0;font-size:.75rem">{{ $r->votes }} votes</p>
    </div>
  </header>
  <p>{!! \App\Support\Linker::html($node, $r->body) !!}</p>
  @if(!empty($r->video_path))
    <a class="holo-vid" href="/n/{{ $node->slug }}/v/{{ \Illuminate\Support\Str::slug($r->video_title ?: 'clip') }}">
      <span class="play">▶</span>
      <span>
        <strong>{{ $r->video_title ?: 'Analyse vidéo' }}</strong>
        <small>{{ $r->video_meta ?: 'Drive' }}</small>
      </span>
    </a>
  @endif
  @if(!empty($r->file_title))
    <p class="holo-file {{ !empty($r->file_locked) ? 'is-lock' : '' }}">
      <span>📄 {{ $r->file_title }}</span>
      @if(!empty($r->file_locked))<em>Membres Premium</em>@else<a href="{{ $r->file_path }}">ouvrir</a>@endif
    </p>
  @endif
  @if(!empty($r->product_id))
    @php $pr = $node->products->firstWhere('id', $r->product_id); @endphp
    @if($pr)
      <div class="holo-shop">
        <img src="{{ $pr->image }}" alt="">
        <div>
          <strong>{{ $pr->title }}</strong>
          <p class="primary">{{ $pr->price }}</p>
        </div>
        <form method="post" action="/cart">
          @csrf
          <input type="hidden" name="product_id" value="{{ $pr->id }}">
          <input type="hidden" name="koc" value="1">
          <button class="btn" type="submit">Ajouter</button>
        </form>
      </div>
    @endif
  @endif
  <form method="post" action="/n/{{ $node->slug }}/t/{{ $r->thread_id }}/award" class="holo-vote">
    @csrf ↑ {{ $r->votes }}
  </form>
</article>
