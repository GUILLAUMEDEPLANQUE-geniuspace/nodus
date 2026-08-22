@extends('layouts.app')
@section('title', 'DNS — '.$node->title)
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 5rem;max-width:40rem">
  <p class="kicker">Marque blanche</p>
  <h1 class="font-display">{{ $host }}.geniuspace.com</h1>
  <p>Chez o2switch / ton registrar, une ligne :</p>
  <pre class="card" style="padding:1rem">{{ $host }}    CNAME    geniuspace.com.</pre>
  <p class="muted">Aperçu sans DNS : <a class="primary" href="/w/{{ $node->slug }}">/w/{{ $node->slug }}</a></p>
  <p>Résolution actuelle : <code>{{ $target }}</code> {{ $ok ? '— ça pointe quelque part.' : '— pas encore (normal en local).' }}</p>
  <form method="post" action="/n/{{ $node->slug }}/host">
    @csrf
    <input type="hidden" name="slug" value="{{ $node->slug }}">
    <input name="host" value="{{ $host }}" pattern="[a-z0-9\-]+">
    <button class="btn" type="submit">Enregistrer le sous-domaine</button>
  </form>
</main>
@endsection
