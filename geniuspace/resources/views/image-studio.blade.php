@extends('layouts.app')
@section('title', 'Éditeur d’images — Geniuspace')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:1.5rem 1.25rem 5rem" id="img-studio">
  <p class="kicker">Image Studio</p>
  <h1 class="font-display" style="font-size:2.4rem;margin:0.2rem 0">Travailler l’image</h1>
  <p class="muted">Produits, covers forum, héros d’univers, avatars, Drive. Crop, filtres, texte, pinceau, undo. Sauvegarde dans <code>public/media/edits</code>.</p>
  <div class="rel" style="margin:0.8rem 0">
    <input id="is-file" type="file" accept="image/*">
    <input id="is-color" type="color" value="#c9a36a">
    <button class="chip" type="button" id="is-rot">90°</button>
    <button class="chip" type="button" id="is-flip">Miroir</button>
    <button class="chip" type="button" id="is-gray">N&B</button>
    <button class="chip" type="button" id="is-sepia">Sépia</button>
    <button class="chip" type="button" id="is-brush">Pinceau</button>
    <button class="chip" type="button" id="is-text">Texte</button>
    <button class="chip" type="button" id="is-crop">Cadre</button>
    <button class="chip" type="button" id="is-apply-crop">Rogner</button>
    <button class="chip" type="button" id="is-undo">Annuler</button>
    <button class="chip" type="button" id="is-reset">Reset</button>
  </div>
  <p class="muted">Luminosité <input id="is-bright" type="range" min="40" max="160" value="100"> Contraste <input id="is-contrast" type="range" min="40" max="160" value="100"> Saturation <input id="is-sat" type="range" min="0" max="200" value="100"></p>
  <canvas id="is-canvas" style="max-width:100%;background:#111;border-radius:1rem;border:1px solid var(--border)"></canvas>
  <form id="is-save" method="post" action="/studio/image" style="margin-top:1rem">
    @csrf
    <input type="hidden" name="data" id="is-data">
    <input type="hidden" name="target" value="{{ $target }}">
    <input type="hidden" name="id" value="{{ $id }}">
    <input type="hidden" name="slug" value="{{ $slug }}">
    <input type="hidden" name="src" id="is-src" value="{{ $src }}">
    <button class="btn" type="submit">Enregistrer sur le serveur</button>
    <a class="btn-ghost" href="javascript:history.back()">Annuler</a>
  </form>
</main>
<script src="/js/image-studio.js"></script>
@endsection
