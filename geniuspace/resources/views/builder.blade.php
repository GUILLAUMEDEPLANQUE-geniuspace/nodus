<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex">
  <title>God Canvas — {{ $node->title }}</title>
  <link rel="stylesheet" href="/css/geniuspace.css">
  <style>
    html,body{margin:0;height:100%;overflow:hidden;background:#07080c;color:#f3eadc}
    #webgl{position:absolute;inset:0;z-index:1}
    .hud{position:absolute;z-index:10;pointer-events:none}
    .hud *{pointer-events:auto}
    .bang{position:absolute;inset:0;z-index:50;display:grid;place-items:center;background:rgba(7,8,12,.88);backdrop-filter:blur(10px)}
    .bang-box{max-width:40rem;width:90%;text-align:center}
    .gdock{position:absolute;bottom:1.2rem;left:50%;transform:translateX(-50%);z-index:20;display:flex;gap:.6rem;padding:.5rem .8rem;border:1px solid var(--border);border-radius:999px;background:color-mix(in srgb,var(--bg) 80%,transparent);backdrop-filter:blur(12px)}
    .gdock button{height:2.6rem;border:0;border-radius:999px;background:transparent;color:var(--muted);padding:0 .85rem;font-size:.8rem}
    .gdock button:hover{color:var(--fg)}
    .slide{position:absolute;top:0;right:0;z-index:30;width:min(24rem,100%);height:100%;background:color-mix(in srgb,var(--bg) 92%,transparent);border-left:1px solid var(--border);transform:translateX(110%);transition:.35s;padding:1.25rem;overflow:auto}
    .slide.on{transform:none}
    .cross{position:absolute;top:50%;left:50%;width:28px;height:28px;border:1px solid rgba(243,234,220,.25);border-radius:999px;transform:translate(-50%,-50%);pointer-events:none;z-index:5}
  </style>
</head>
<body>
<div id="webgl"></div>
<div class="cross" id="cross" style="opacity:0"></div>
<header class="hud" style="top:0;left:0;right:0;display:flex;justify-content:space-between;padding:1rem 1.25rem">
  <a href="/n/{{ $node->slug }}" class="brand">Geniuspace <span class="primary">God Canvas</span></a>
  <div>
    <a class="btn-line" href="/n/{{ $node->slug }}/studio">Studio CCK</a>
    <a class="btn-line" href="/n/{{ $node->slug }}">Quitter l'espace</a>
  </div>
</header>
<div class="bang" id="bang">
  <div class="bang-box">
    <p class="kicker">Big Bang</p>
    <h1 class="font-display" style="font-size:2.2rem">Sculpter l'univers</h1>
    <p class="muted">Le prompt choisit la peau. Les briques du dock écrivent des nœuds et des arêtes en base — la 3D n'est que la représentation.</p>
    <input id="prompt" class="prompt-input" style="width:100%;margin:1rem 0;height:3rem" value="{{ $node->summary }}">
    <div class="rel" style="justify-content:center;margin-bottom:1rem">
      <button type="button" class="chip" data-p="Un salon de recrutement RPG pour développeurs">Vera / jobs</button>
      <button type="button" class="chip" data-p="Une galerie RWA, îles flottantes, tokenisation">RWA / crypto</button>
      <button type="button" class="chip" data-p="Sanctuaire manga, constellations de personnages">Hub anime</button>
    </div>
    <button class="btn" type="button" id="go-bang">Générer le monde</button>
  </div>
</div>
<nav class="gdock" id="dock" style="opacity:0">
  <button type="button" data-add="video">Holo-Fiche</button>
  <button type="button" data-add="job">Offre / Quête</button>
  <button type="button" data-add="crypto">Token RWA</button>
  <button type="button" data-add="character">Personnage</button>
  <button type="button" data-add="shop">Produit</button>
  <button type="button" id="link-mode">Lier (rayon)</button>
</nav>
<aside class="slide" id="panel">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2 class="font-display" id="ptitle">Nœud</h2>
    <button class="btn-ghost" type="button" id="pclose">Fermer</button>
  </div>
  <form id="psync">
    <input type="hidden" name="id" id="pid">
    <label class="muted">Titre</label>
    <input name="title" id="ptit" style="width:100%;margin:.4rem 0">
    <label class="muted">Résumé / SEO</label>
    <textarea name="summary" id="psum" style="width:100%"></textarea>
    <label class="muted">Champ CCK</label>
    <input name="field_name" placeholder="ex. Salaire / Contrat / Prime" style="width:100%;margin:.4rem 0">
    <input name="field_value" placeholder="valeur" style="width:100%">
    <button class="btn" type="submit" style="margin-top:1rem;width:100%">Synchroniser la DB</button>
  </form>
  <p class="muted" style="margin-top:1rem;font-size:.8rem">Clic sur un astre = zoom + panneau. Mode Lier : clic A puis B = arête parent/enfant.</p>
</aside>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script>window.GP_SLUG = @json($node->slug); window.GP_CSRF = document.querySelector('meta[name="csrf-token"]').content;</script>
<script src="/js/god-canvas.js"></script>
</body>
</html>
