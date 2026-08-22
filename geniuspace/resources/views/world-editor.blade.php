@extends('layouts.app')
@section('title', 'Éditeur de monde — '.$node->title)
@section('robots', 'noindex')
@section('content')
@php
  $done = collect($checklist)->where('ok', true)->count();
  $total = count($checklist);
  $scopes = \App\Support\Chrome::scopes();
@endphp
<div class="we" x-data="{ mode: @js($mode), pick: null }">
  <header class="we-bar">
    <div>
      <p class="kicker">Éditeur de monde</p>
      <h1 class="font-display" style="font-size:1.6rem;margin:0">{{ $node->title }}</h1>
    </div>
    <nav class="we-modes">
      @foreach(['structure'=>'Structure','design'=>'Design','action'=>'Action','motion'=>'Motion'] as $k=>$lab)
        <a href="/n/{{ $node->slug }}/monde?mode={{ $k }}" class="{{ $mode===$k ? 'on' : '' }}">{{ $lab }}</a>
      @endforeach
    </nav>
    <div class="we-bar-right">
      <span class="muted">{{ $done }}/{{ $total }} prêt</span>
      <a class="btn-line" href="/n/{{ $node->slug }}">Voir comme un visiteur</a>
      <a class="btn-ghost" href="/n/{{ $node->slug }}/studio">Studio</a>
    </div>
  </header>

  @if($flagship)
    <p class="we-note">Vera officiel garde sa une éditoriale. Une maison née du template Vera, elle, suit cet éditeur de bout en bout.</p>
  @endif

  <div class="we-grid">
    <aside class="we-check">
      <p class="kicker">Première heure</p>
      <ol>
        @foreach($checklist as $c)
          <li class="{{ $c['ok'] ? 'ok' : '' }}">{{ $c['label'] }}</li>
        @endforeach
      </ol>
      <p class="muted" style="font-size:.8rem;margin-top:1rem">Identité, salles, boutons, fiches, player, boutique, Google, équipe — sans une ligne de code.</p>
    </aside>

    <section class="we-stage">
      <p class="kicker">Scène</p>
      <div class="hero we-hero" style="min-height:22rem;border-radius:1.2rem;overflow:hidden;position:relative;background:{{ $theme->bg }}">
        @if($theme->hero_video)
          <video class="bg" src="{{ $theme->hero_video }}" muted loop autoplay playsinline poster="{{ $theme->hero ?: $node->hero }}"></video>
        @else
          <img class="bg" src="{{ $theme->hero ?: $node->hero }}" alt="">
        @endif
        <div class="veil"></div>
        @include('partials.scene-layers', ['layers' => $chrome['heroLayers'], 'node' => $node])
        <div class="copy wrap" style="position:absolute;left:0;right:0;bottom:0;padding:1.4rem">
          @if($theme->logo)<img src="{{ $theme->logo }}" alt="" style="height:2rem;width:auto;margin-bottom:.4rem">@endif
          <p class="kicker">{{ $node->kind === 'company' ? 'Maison' : 'Univers' }}</p>
          <h2 class="font-display" style="font-size:clamp(1.6rem,4vw,2.4rem);margin:.2rem 0;color:{{ $theme->fg }}">{{ $node->title }}</h2>
          <p style="color:{{ $theme->fg }};opacity:.85">{{ $node->subtitle ?: $node->summary }}</p>
          <div class="rel" style="margin-top:.7rem">
            @foreach($chrome['heroActions'] as $a)
              @include('partials.action-button', ['action'=>$a,'node'=>$node])
            @endforeach
          </div>
        </div>
      </div>
      <nav class="dock dock-{{ $theme->dock }}" style="position:relative;left:auto;bottom:auto;transform:none;margin:1rem auto 0">
        @foreach($tabs->where('enabled', 1) as $t)
          <a href="/n/{{ $node->slug }}/{{ $t->key }}">{{ $t->label }}</a>
        @endforeach
      </nav>
    </section>

    <section class="we-side">
      @if($mode==='structure')
        <h2 class="font-display">Plan du lieu</h2>
        <p class="muted">Renommez, masquez, réordonnez. Une salle masquée n’apparaît plus dans le dock.</p>
        @foreach($tabs as $t)
          <form method="post" action="/n/{{ $node->slug }}/monde/tab" class="we-row">
            @csrf
            <input type="hidden" name="mode" value="structure">
            <input type="hidden" name="id" value="{{ $t->id }}">
            <input name="label" value="{{ $t->label }}" style="flex:1;min-width:8rem">
            <span class="muted" style="font-size:.75rem">{{ $t->key }}</span>
            <label class="muted" style="font-size:.8rem"><input type="checkbox" name="enabled" value="1" @checked($t->enabled ?? true)> visible</label>
            <button class="btn" type="submit">OK</button>
          </form>
          <form method="post" action="/n/{{ $node->slug }}/monde/tab/move" class="rel" style="margin:-.3rem 0 .7rem">
            @csrf
            <input type="hidden" name="mode" value="structure">
            <input type="hidden" name="id" value="{{ $t->id }}">
            <button class="btn-ghost" name="dir" value="up" type="submit">Monter</button>
            <button class="btn-ghost" name="dir" value="down" type="submit">Descendre</button>
          </form>
        @endforeach
        <form method="post" action="/n/{{ $node->slug }}/monde/tab/add" class="we-row" style="margin-top:1rem">
          @csrf
          <input type="hidden" name="mode" value="structure">
          <select name="key">
            @foreach($rooms as $k=>$r)
              <option value="{{ $k }}">{{ $r['label'] }}</option>
            @endforeach
          </select>
          <input name="label" placeholder="Libellé" required>
          <button class="btn" type="submit">Ajouter une salle</button>
        </form>

        <h2 class="font-display" style="margin-top:1.6rem">Fiches liées</h2>
        <div class="rel">
          @forelse($children as $c)
            <a class="chip" href="/n/{{ $node->slug }}/f/{{ $c->slug }}">{{ $c->title }}</a>
          @empty
            <p class="muted">Aucune fiche. Ajoutez un personnage, une offre, une œuvre.</p>
          @endforelse
        </div>
        <form method="post" action="/n/{{ $node->slug }}/monde/child" class="card" style="padding:1rem;margin-top:.6rem;display:grid;gap:.4rem">
          @csrf
          <input type="hidden" name="mode" value="structure">
          <input name="title" placeholder="Nom" required>
          <select name="kind">
            <option value="person">Personnage</option>
            <option value="job">Offre</option>
            <option value="product">Œuvre / produit</option>
            <option value="company">Maison</option>
          </select>
          <input name="summary" placeholder="Une phrase">
          <button class="btn" type="submit">Lier la fiche</button>
        </form>

        <h2 class="font-display" style="margin-top:1.6rem">Détails métier</h2>
        <form method="post" action="/n/{{ $node->slug }}/monde/pack" style="display:grid;gap:.4rem">
          @csrf
          <input type="hidden" name="mode" value="structure">
          @foreach($templates as $tid=>$t)
            <button class="btn-line" name="template" value="{{ $tid }}" type="submit">{{ $t['label'] }}</button>
          @endforeach
        </form>

        <h2 class="font-display" style="margin-top:1.6rem">Google</h2>
        <form method="post" action="/n/{{ $node->slug }}/monde/seo" class="card" style="padding:1rem;display:grid;gap:.4rem">
          @csrf
          <input type="hidden" name="mode" value="structure">
          <input name="title" value="{{ $seo->title ?? '' }}" placeholder="Titre (60 car.)">
          <textarea name="description" placeholder="Description">{{ $seo->description ?? '' }}</textarea>
          <button class="btn" type="submit">Publier la fiche Google</button>
        </form>

        <h2 class="font-display" style="margin-top:1.6rem">Équipe</h2>
        @foreach($staff as $s)
          <p class="muted">{{ $users[$s->user_id]->name ?? '#'.$s->user_id }} · {{ $s->role }}</p>
        @endforeach
        <form method="post" action="/n/{{ $node->slug }}/monde/staff" class="card" style="padding:1rem;display:grid;gap:.4rem">
          @csrf
          <input type="hidden" name="mode" value="structure">
          <input name="email" type="email" placeholder="email" required>
          <input name="name" placeholder="Prénom">
          <select name="role">
            <option value="mod">Modérateur</option>
            <option value="admin">Admin</option>
          </select>
          <button class="btn" type="submit">Inviter</button>
        </form>

      @elseif($mode==='design')
        <h2 class="font-display">Identité</h2>
        <form method="post" action="/n/{{ $node->slug }}/monde/theme" class="card" style="padding:1rem;display:grid;gap:.45rem">
          @csrf
          <input type="hidden" name="mode" value="design">
          <label class="muted">Couleur principale <input type="color" name="primary" value="{{ $theme->primary }}"></label>
          <label class="muted">Fond <input type="color" name="bg" value="{{ $theme->bg }}"></label>
          <label class="muted">Texte <input type="color" name="fg" value="{{ $theme->fg }}"></label>
          <label class="muted">Secondaire <input type="color" name="muted" value="{{ $theme->muted }}"></label>
          <input name="logo" value="{{ $theme->logo }}" placeholder="Logo (chemin Drive)">
          <input name="hero" value="{{ $theme->hero }}" placeholder="Image de fond">
          <input name="hero_video" value="{{ $theme->hero_video }}" placeholder="Vidéo de fond (mp4)">
          <input name="poster" value="{{ $theme->poster }}" placeholder="Poster player">
          <input name="favicon" value="{{ $theme->favicon }}" placeholder="Favicon">
          <select name="dock">
            <option value="bottom" @selected($theme->dock==='bottom')>Dock bas</option>
            <option value="top" @selected($theme->dock==='top')>Dock haut</option>
            <option value="side" @selected($theme->dock==='side')>Dock côté</option>
          </select>
          <select name="skin">
            <option value="living" @selected($theme->skin==='living')>Lieu de vie</option>
            <option value="vera" @selected($theme->skin==='vera')>Papier (recrutement)</option>
          </select>
          <button class="btn" type="submit">Sauver l’identité</button>
        </form>

        <h2 class="font-display" style="margin-top:1.4rem">Calques</h2>
        <p class="muted">Texte, image, bouton — posés en % sur la scène.</p>
        @foreach($layers as $l)
          <form method="post" action="/n/{{ $node->slug }}/monde/layer" class="card" style="padding:.8rem;margin:.4rem 0;display:grid;gap:.3rem">
            @csrf
            <input type="hidden" name="mode" value="design">
            <input type="hidden" name="id" value="{{ $l->id }}">
            <input type="hidden" name="kind" value="{{ $l->kind }}">
            <strong>{{ $l->kind }} · {{ $l->label }}</strong>
            <input name="label" value="{{ $l->label }}" placeholder="Nom">
            <input name="body" value="{{ $l->body }}" placeholder="Texte">
            <input name="src" value="{{ $l->src }}" placeholder="Image / vidéo">
            <div class="rel">
              <input name="x" type="number" step="0.5" value="{{ $l->x }}" style="width:4.2rem" title="x">
              <input name="y" type="number" step="0.5" value="{{ $l->y }}" style="width:4.2rem" title="y">
              <input name="w" type="number" step="0.5" value="{{ $l->w }}" style="width:4.2rem" title="w">
              <input name="h" type="number" step="0.5" value="{{ $l->h }}" style="width:4.2rem" title="h">
            </div>
            <input name="action_key" value="{{ $l->action_key }}" placeholder="Action (open_shop, open_jobs…)">
            <input name="action_target" value="{{ $l->action_target }}" placeholder="Cible (salle, URL)">
            <input type="hidden" name="visible" value="0">
            <label class="muted"><input type="checkbox" name="visible" value="1" @checked($l->visible)> visible</label>
            <button class="btn" type="submit">Sauver</button>
          </form>
          <form method="post" action="/n/{{ $node->slug }}/monde/layer/delete">
            @csrf<input type="hidden" name="mode" value="design"><input type="hidden" name="id" value="{{ $l->id }}">
            <button class="btn-ghost" type="submit">Retirer</button>
          </form>
        @endforeach
        <form method="post" action="/n/{{ $node->slug }}/monde/layer" class="card" style="padding:1rem;margin-top:.8rem;display:grid;gap:.35rem">
          @csrf
          <input type="hidden" name="mode" value="design">
          <p class="kicker">Nouveau calque</p>
          <select name="kind">
            <option value="text">Texte</option>
            <option value="image">Image</option>
            <option value="video">Vidéo</option>
            <option value="button">Bouton</option>
            <option value="shape">Forme</option>
          </select>
          <input name="label" placeholder="Nom" value="Calque">
          <input name="body" placeholder="Texte / libellé">
          <input name="src" placeholder="Chemin image ou mp4">
          <button class="btn" type="submit">Ajouter</button>
        </form>

        <h2 class="font-display" style="margin-top:1.4rem">Presets</h2>
        <form method="post" action="/n/{{ $node->slug }}/monde/preset" class="rel">
          @csrf
          <input type="hidden" name="mode" value="design">
          <button class="btn-line" name="preset" value="living" type="submit">Lieu de vie</button>
          <button class="btn-line" name="preset" value="merch" type="submit">Galerie</button>
          <button class="btn-line" name="preset" value="vera" type="submit">Maison (Vera)</button>
        </form>

      @elseif($mode==='action')
        <h2 class="font-display">Boutons</h2>
        <p class="muted">Le moteur (panier, déblocage) ne change pas. Vous changez le mot, l’ordre, la présence.</p>
        @foreach($scopes as $scope=>$lab)
          <h3 class="kicker" style="margin-top:1.1rem">{{ $lab }}</h3>
          @foreach($actions->where('scope', $scope) as $a)
            <form method="post" action="/n/{{ $node->slug }}/monde/action" class="card" style="padding:.8rem;margin:.35rem 0;display:grid;gap:.35rem">
              @csrf
              <input type="hidden" name="mode" value="action">
              <input type="hidden" name="id" value="{{ $a->id }}">
              <input name="label" value="{{ $a->label }}">
              <select name="variant">
                <option value="primary" @selected($a->variant==='primary')>Plein</option>
                <option value="line" @selected($a->variant==='line')>Contour</option>
                <option value="ghost" @selected($a->variant==='ghost')>Discret</option>
              </select>
              <input name="href" value="{{ $a->href }}" placeholder="Lien (vide = action système)">
              <label class="muted"><input type="checkbox" name="enabled" value="1" @checked($a->enabled)> visible</label>
              @if($scope==='player_paywall')
                @php $ex = \App\Support\Chrome::extra($a); @endphp
                <input name="paywall_title" value="{{ $ex['paywall_title'] ?? '' }}" placeholder="Titre du rideau">
                <textarea name="paywall_body" placeholder="Texte">{{ $ex['paywall_body'] ?? '' }}</textarea>
              @endif
              <button class="btn" type="submit">Sauver</button>
            </form>
            <form method="post" action="/n/{{ $node->slug }}/monde/action/move" class="rel" style="margin-bottom:.5rem">
              @csrf
              <input type="hidden" name="mode" value="action">
              <input type="hidden" name="id" value="{{ $a->id }}">
              <button class="btn-ghost" name="dir" value="up" type="submit">Monter</button>
              <button class="btn-ghost" name="dir" value="down" type="submit">Descendre</button>
            </form>
          @endforeach
        @endforeach

      @else
        <h2 class="font-display">Motion</h2>
        <p class="muted">Entrée fade / slide, délai. La timeline from→to est un addon Pro.</p>
        @forelse($layers as $l)
          <form method="post" action="/n/{{ $node->slug }}/monde/layer" class="card" style="padding:.8rem;margin:.4rem 0;display:grid;gap:.35rem">
            @csrf
            <input type="hidden" name="mode" value="motion">
            <input type="hidden" name="id" value="{{ $l->id }}">
            <input type="hidden" name="kind" value="{{ $l->kind }}">
            <input type="hidden" name="label" value="{{ $l->label }}">
            <input type="hidden" name="body" value="{{ $l->body }}">
            <input type="hidden" name="src" value="{{ $l->src }}">
            <input type="hidden" name="x" value="{{ $l->x }}">
            <input type="hidden" name="y" value="{{ $l->y }}">
            <input type="hidden" name="w" value="{{ $l->w }}">
            <input type="hidden" name="h" value="{{ $l->h }}">
            <input type="hidden" name="visible" value="{{ $l->visible ? 1 : 0 }}">
            <strong>{{ $l->label ?: $l->kind }}</strong>
            <select name="motion">
              <option value="none" @selected($l->motion==='none')>Aucune</option>
              <option value="fade" @selected($l->motion==='fade')>Fade</option>
              <option value="slide" @selected($l->motion==='slide')>Slide</option>
            </select>
            <label class="muted">Délai ms <input name="delay_ms" type="number" min="0" max="4000" value="{{ $l->delay_ms }}" style="width:6rem"></label>
            <button class="btn" type="submit">Sauver</button>
          </form>
        @empty
          <p class="muted">Ajoutez un calque en Design, puis animez-le ici.</p>
        @endforelse
        <p class="we-note" style="margin-top:1rem">Addon Pro : timeline, parallax scroll, typewriter. Le moat n’est pas Fluid Dynamics — c’est le lieu qui vit.</p>
      @endif
    </section>
  </div>
</div>
@endsection
