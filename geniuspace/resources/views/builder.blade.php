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
    html,body{margin:0;height:100%;overflow:hidden;background:#05060a;color:#f3eadc}
    #webgl{position:absolute;inset:0;z-index:1}
    .hud{position:absolute;z-index:10;pointer-events:none} .hud *{pointer-events:auto}
    .bang{position:absolute;inset:0;z-index:50;display:grid;place-items:center;background:rgba(5,6,10,.9);backdrop-filter:blur(12px)}
    .bang-box{max-width:42rem;width:92%;text-align:center}
    .gdock{position:absolute;bottom:1.1rem;left:50%;transform:translateX(-50%);z-index:20;display:flex;gap:.45rem;padding:.45rem .7rem;border:1px solid var(--border);border-radius:999px;background:rgba(7,8,12,.82);backdrop-filter:blur(12px);max-width:96vw;overflow-x:auto}
    .gdock button{height:2.5rem;border:0;background:transparent;color:var(--muted);padding:0 .7rem;font-size:.75rem;white-space:nowrap}
    .slide{position:absolute;top:0;right:0;z-index:30;width:min(26rem,100%);height:100%;background:rgba(7,8,12,.94);border-left:1px solid var(--border);transform:translateX(110%);transition:.35s;padding:1.1rem;overflow:auto}
    .slide.on{transform:none}
    .palette{position:absolute;top:4.2rem;left:.7rem;z-index:15;width:13.5rem;max-height:70dvh;overflow:auto;background:rgba(7,8,12,.8);border:1px solid var(--border);border-radius:1rem;padding:.6rem;backdrop-filter:blur(10px)}
    .palette button{display:block;width:100%;text-align:left;background:transparent;border:0;color:var(--muted);font-size:.72rem;padding:.28rem 0}
    .palette button:hover{color:var(--primary)}
    .drop-hint{position:absolute;inset:0;z-index:8;display:none;place-items:center;border:2px dashed var(--primary);background:rgba(201,163,106,.08);pointer-events:none;font-family:var(--display);font-size:1.6rem}
    body.dragging .drop-hint{display:grid}
  </style>
</head>
<body>
<div id="webgl"></div>
<div class="drop-hint">Déposez image / vidéo / audio — CCK + SEO auto</div>
<header class="hud" style="top:0;left:0;right:0;display:flex;justify-content:space-between;padding:.9rem 1.1rem">
  <a href="/n/{{ $node->slug }}" class="brand">Geniuspace <span class="primary">God Canvas</span></a>
  <div>
    <a class="btn-line" href="/n/{{ $node->slug }}/studio">Studio</a>
    <a class="btn-line" href="/n/{{ $node->slug }}">Vivre le monde</a>
  </div>
</header>
<div id="ideas" class="hud rel" style="top:3.4rem;left:50%;transform:translateX(-50%);z-index:16;max-width:90vw"></div>
<aside class="palette" id="palette" style="{{ $fresh ? 'opacity:0' : '' }}">
  <p class="kicker">CCK · coller sur le nœud visé</p>
  @php $g=''; @endphp
  @foreach($catalog as $key => $meta)
    @if($g !== $meta['g'])
      @php $g = $meta['g']; @endphp
      <p class="primary" style="margin:.5rem 0 .15rem;font-size:.7rem">{{ $g }}</p>
    @endif
    <button type="button" data-cck="{{ $key }}">{{ $meta['label'] }}</button>
  @endforeach
</aside>
@if($fresh)
<div class="bang" id="bang">
  <div class="bang-box">
    <p class="kicker">Création · monde vide</p>
    <h1 class="font-display" style="font-size:2.3rem">Sculpter {{ $node->title }}</h1>
    <p class="muted">Rien n’est prérempli. Tu poses les briques. L’IA peut proposer des nœuds à partir de ta liste — elle ne dump pas une flotte.</p>
    <input id="prompt" style="width:100%;margin:1rem 0;height:3rem" value="{{ $node->summary }}" placeholder="Ex. Luffy, Zoro, carte Grand Line — ou rien">
    <div class="rel" style="justify-content:center;margin-bottom:1rem">
      <button class="btn" type="button" id="go-bang">Commencer vide</button>
      <button class="btn-line" type="button" id="go-propose">Proposer depuis mon texte</button>
    </div>
  </div>
</div>
@else
<div id="bang" hidden></div>
<input id="prompt" type="hidden" value="{{ $node->summary }}">
@endif
<nav class="gdock" id="dock" style="{{ $fresh ? 'opacity:0' : '' }}">
  <button type="button" data-add="character">Personnage</button>
  <button type="button" data-add="shop">Produit</button>
  <button type="button" data-add="video">Vidéo</button>
  <button type="button" data-add="job">Offre</button>
  <button type="button" data-add="crypto">Actif</button>
  <button type="button" id="link-mode">Lier</button>
  <button type="button" id="recompile">Proposer (LLM)</button>
  <label class="muted" style="font-size:.75rem;padding:.4rem">Importer <input id="imp" type="file" accept="image/*,video/*,audio/*" style="width:8rem"></label>
</nav>
<aside class="slide" id="panel">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2 class="font-display" id="ptitle">Nœud</h2>
    <button class="btn-ghost" type="button" id="pclose">Fermer</button>
  </div>
  <form id="psync">
    <input type="hidden" name="id" id="pid">
    <label class="muted">Titre SEO</label>
    <input name="title" id="ptit" style="width:100%;margin:.3rem 0">
    <label class="muted">Description</label>
    <textarea name="summary" id="psum" style="width:100%"></textarea>
    <label class="muted">Ajouter un champ CCK</label>
    <select name="field_type" id="ftype" style="width:100%;margin:.3rem 0">
      @foreach($catalog as $key => $meta)
        <option value="{{ $key }}">{{ $meta['g'] }} · {{ $meta['label'] }}</option>
      @endforeach
    </select>
    <input name="field_name" placeholder="Libellé" style="width:100%;margin:.3rem 0">
    <input name="field_value" placeholder="Valeur / URL média" style="width:100%">
    <button class="btn" type="submit" style="margin-top:.8rem;width:100%">Synchroniser DB + SEO</button>
  </form>
  <div id="pfields" class="muted" style="margin-top:1rem;font-size:.8rem"></div>
  <p class="muted" style="margin-top:1rem;font-size:.75rem">Glissez un fichier sur l’espace = Drive + champ média + OG. Mode Lier = rayon parent/enfant.</p>
</aside>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script>
  window.GP_SLUG = @json($node->slug);
  window.GP_FRESH = @json($fresh);
  window.GP_CSRF = document.querySelector('meta[name="csrf-token"]').content;
</script>
<script src="/js/god-canvas.js"></script>
</body>
</html>
