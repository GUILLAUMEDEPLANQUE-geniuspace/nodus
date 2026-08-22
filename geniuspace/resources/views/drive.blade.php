@extends('layouts.app')
@section('title', 'Drive — fichiers locaux | Geniuspace')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2.5rem 1.25rem 6rem">
    <p class="kicker">Stockage serveur</p>
    <h1 class="font-display" style="font-size:2.8rem;margin:0.2rem 0">Drive</h1>
    <p class="muted" style="max-width:40rem">Les MP4 vivent dans <code>public/media</code> — le disque de o2switch, d’un VPS ou d’un dédié. Pas YouTube. R2 reste optionnel.</p>
    <form method="post" action="{{ url('/drive') }}" enctype="multipart/form-data" class="card" style="padding:1.25rem;margin:1.5rem 0;max-width:32rem">
        @csrf
        <label class="muted" style="display:block;margin-bottom:0.4rem">Fichier (mp4, webm, mp3, image, zip)</label>
        <input type="file" name="file" required>
        <label class="muted" style="display:block;margin:0.8rem 0 0.4rem">Titre</label>
        <input type="text" name="title" placeholder="Making-of">
        <label class="muted" style="display:block;margin:0.8rem 0 0.4rem">Node (slug)</label>
        <input type="text" name="node_slug" value="one-piece">
        <button class="btn" type="submit" style="margin-top:1rem">Déposer sur le serveur</button>
    </form>
    <h2 class="font-display">Sur le disque</h2>
    <ul class="muted">
        @forelse($files as $f)
            <li><a href="/media/{{ $f }}">/media/{{ $f }}</a></li>
        @empty
            <li>Aucun fichier.</li>
        @endforelse
    </ul>
    <h2 class="font-display">Fiches</h2>
    @foreach($rows as $m)
        <p><a href="/media/{{ basename($m->path) }}">{{ $m->title }}</a> · {{ $m->path }}</p>
    @endforeach
</main>
@endsection
