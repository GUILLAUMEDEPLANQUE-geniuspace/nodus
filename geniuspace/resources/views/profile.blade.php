@extends('layouts.app')
@section('title', $user->name.' — profil | Geniuspace')
@section('content')
<section class="hero" style="min-height:40dvh">
  <img class="bg" src="{{ $user->banner }}" alt="">
  <div class="veil"></div>
  <div class="copy wrap" style="display:flex;gap:1rem;align-items:flex-end">
    <img src="{{ $user->avatar }}" alt="" style="width:6rem;height:6rem;border-radius:999px;object-fit:cover;border:2px solid var(--primary)">
    <div>
      <h1 class="font-display" style="font-size:2.4rem;margin:0">{{ $user->name }}</h1>
      <p>{{ $user->bio }}</p>
      <a class="btn-line" href="/studio/image?src={{ urlencode($user->avatar) }}&target=avatar">Éditer avatar</a>
      <a class="btn-line" href="/studio/image?src={{ urlencode($user->banner) }}&target=banner">Éditer bannière</a>
    </div>
  </div>
</section>
<main class="wrap" style="padding:1.5rem 1.25rem 5rem">
  @auth
    @if(auth()->id()===$user->id)
      <form method="post" action="/profil">
        @csrf
        <input name="name" value="{{ $user->name }}">
        <input name="bio" value="{{ $user->bio }}" placeholder="Bio">
        <button class="btn" type="submit">Sauver</button>
      </form>
      <p class="kicker">{{ $user->nodecoins ?? 0 }} NodeCoins</p>
      <h2 class="font-display">Sac à dos (cross-node)</h2>
      <p class="muted">Reliques, titres, épreuves — voyagent avec toi, sans blockchain.</p>
      @forelse($pack ?? [] as $it)
        <p class="card" style="padding:.7rem;margin:.3rem 0"><span class="kicker">{{ $it->kind }}</span> {{ $it->label }} <span class="muted">{{ $it->meta }}</span></p>
      @empty
        <p class="muted">Sac vide. Gagne une quête SEO ou achète une relique.</p>
      @endforelse
      @foreach($playlists as $p)
        <p><a href="/pl/{{ $p->share_slug }}">{{ $p->title }}</a> — /pl/{{ $p->share_slug }}</p>
      @endforeach
      <form method="post" action="/profil/playlist">@csrf<input name="title" placeholder="Nouvelle playlist"><button class="btn" type="submit">Créer</button></form>
      <h2 class="font-display">Notifications</h2>
      @foreach($notifs as $n)
        <p><a href="{{ $n->url }}">{{ $n->title }}</a></p>
      @endforeach
      <h2 class="font-display">Messages</h2>
      @foreach($dms as $d)
        <p class="muted">#{{ $d->from_id }} → #{{ $d->to_id }} : {{ $d->body }}</p>
      @endforeach
    @endif
  @endauth
</main>
@endsection
