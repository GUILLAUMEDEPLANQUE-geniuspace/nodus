@extends('layouts.vera')
@section('title', 'Vera — l’emploi enfin lisible | Offres à salaire publié')
@section('description', 'Jobboard indépendant. Verdict avant candidature, pacte de réponse public, brief à la place du CV, grilles d’évaluation visibles. Pas de pubs, pas de ghost cachés.')
@section('canonical', url('/n/vera'))
@php
  $C = \App\Support\VeraCatalog::class;
  $pulse = $C::pulse();
  $jobs = $C::jobs();
  $featured = array_values(array_filter($jobs, fn ($j) => !empty($j['full'])));
  $league = array_slice($C::honorLeague(), 0, 5);
  $viviers = $C::json('viviers');
  $cols = $C::json('collections');
  $glossary = array_slice($C::json('glossary'), 0, 8);
@endphp
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'Vera','url'=>url('/n/vera'),'description'=>'L’emploi, enfin lisible.','potentialAction'=>['@type'=>'SearchAction','target'=>url('/n/vera/offres').'?q={q}','query-input'=>'required name=q']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<section class="vera-hero">
  <div class="vera-wrap">
    <p class="vera-kicker">Jobboard indépendant</p>
    <h1>L’emploi,<br>enfin lisible.</h1>
    <p class="vera-lead">Vera dit aux professionnels quand passer leur chemin. <a href="/n/vera/lexique#verdict">Verdict</a> avant candidature, <a href="/n/vera/pacte">pacte</a> de réponse public, <a href="/n/vera/lexique#brief">brief</a> à la place du CV. Indeed n’a aucun intérêt à faire ça.</p>
    <form class="vera-search" action="/n/vera/offres" method="get">
      <input name="q" placeholder="Métier, ville, geste — pas un mot-clé RH" aria-label="Recherche">
      <button class="vera-btn" type="submit">Chercher</button>
    </form>
    <div class="chips" style="margin-top:1rem">
      <a href="/n/vera/preuve" style="color:var(--primary);font-weight:500">Passer une épreuve d’abord</a>
      <a href="/n/vera/europe" class="muted">Europe</a>
      <a href="/n/vera/passport">Passeport</a>
    </div>
    <dl class="vera-stats">
      <div><dt>Offres actives</dt><dd>{{ $pulse['activeJobs'] }}</dd></div>
      <div><dt>Salaires publiés</dt><dd>{{ $pulse['salaryPublishedPct'] }}&nbsp;%</dd></div>
      <div><dt>Ghost signalés</dt><dd>{{ $pulse['ghostFlagged'] }}</dd></div>
      <div><dt>Médiane haute</dt><dd>{{ $pulse['medianLabel'] }}</dd></div>
    </dl>
  </div>
</section>

<section class="section">
  <div class="vera-wrap vera-grid g3">
    <a class="v-card" href="/n/vera/journal"><p class="vera-kicker">Journal</p><h3>Blogs entreprises & journaux</h3><p>Relève, Northline, Kora écrivent le geste. Malik et Hélène tiennent un carnet. Pas un LinkedIn.</p></a>
    <a class="v-card" href="/n/vera/savoirs"><p class="vera-kicker">Fiches</p><h3>Catégories que vous créez</h3><p>Marché, droit, robotique — et les vôtres. L’opérateur ajoute un rayon en trente secondes.</p></a>
    <a class="v-card" href="/n/vera/ppqc"><p class="vera-kicker">PPQC</p><h3>Payer le qualifié, pas l’annonce</h3><p>Publication gratuite. Facture seulement si l’épreuve est tenue et la grille ≥ 55.</p></a>
  </div>
</section>

<section class="section paper">
  <div class="vera-wrap">
    <h2 style="font-size:2rem">Pourquoi les pros viennent ici</h2>
    <div class="vera-grid g4" style="margin-top:1.8rem">
      <article class="principle"><p class="n">01</p><h3>Le Verdict</h3><p>Avant de postuler, on calcule si l’offre mérite vos heures : ghost, honneur, fourchette, longueur du process. Un « Passez » est le produit. Pas un bug.</p><a href="/n/vera/offres" style="color:var(--primary)">Lire une offre</a></article>
      <article class="principle"><p class="n">02</p><h3>Le Pacte</h3><p>L’entreprise s’engage à une date. Si elle manque, son honneur baisse — public, pas négociable. Les entreprises sérieuses viennent pour le filtre. Les autres restent sur LinkedIn.</p><a href="/n/vera/pacte" style="color:var(--primary)">Voir le classement</a></article>
      <article class="principle"><p class="n">03</p><h3>Le Brief</h3><p>Une page : livré, refusé, suite. Pas un PDF de quatre pages. Les recruteurs lisent moins, et mieux. Vous n’avez plus à vous déguiser.</p><a href="/n/vera/passport" style="color:var(--primary)">Écrire le vôtre</a></article>
      <article class="principle"><p class="n">04</p><h3>L’épreuve avant le CV</h3><p>Épreuve métier 6 min. Échec → module tagué de 8 min → retry. Les coordonnées après, pas avant. Le passeport ne tamponne qu’un tenu.</p><a href="/n/vera/preuve" style="color:var(--primary)">Passer une épreuve</a></article>
    </div>
  </div>
</section>

<section class="section band-dark">
  <div class="vera-wrap">
    <p class="vera-kicker">Europe</p>
    <h2 style="font-size:clamp(1.8rem,4vw,2.4rem);max-width:22ch">La preuve avant le titre — aussi hors de France.</h2>
    <p style="max-width:36rem;margin-top:.8rem">Remote ±2h, bandes salariales UE, épreuve créditée, module si ça rate, Passeport exportable. Pas une traduction : des normes (AI Act, FHIR, LOTO) et des relecteurs métier.</p>
    <div class="chips" style="margin-top:1.4rem">
      <a class="vera-btn" href="/n/vera/europe" style="background:var(--bg);color:var(--ink)">Voir l’Europe</a>
      <a class="vera-btn line" href="/n/vera/preuve" style="border-color:color-mix(in srgb, var(--primary-fg) 30%, transparent);color:var(--primary-fg)">Passer une épreuve</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="vera-wrap">
    <p class="vera-kicker">L’offre augmentée</p>
    <h2 style="font-size:clamp(1.8rem,4vw,2.4rem);max-width:24ch">Lire un poste comme on lit un outil — pas une fiche.</h2>
    <p class="vera-lead">Salaire contre le marché. Semaine réelle. Carrière en trois nœuds. Visite du lieu. Collègues au téléphone. Une épreuve avant le CV. Indeed ne peut pas faire ça : ses clients paient pour le volume.</p>
    <div class="vera-grid g4" style="margin-top:1.6rem">
      @foreach(array_slice($featured, 0, 4) as $f)
        <a class="v-card" href="/n/vera/offres/{{ $f['slug'] }}">
          <p class="vera-kicker">{{ $f['company']['name'] }} · {{ $f['city'] }}</p>
          <h3>{{ $f['title'] }}</h3>
          <p>{{ \Illuminate\Support\Str::limit($f['description'], 110) }}</p>
          <p class="salary">{{ $f['salaryLabel'] }}</p>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section paper">
  <div class="vera-wrap">
    <p class="vera-kicker">Fiches métier</p>
    <h2>Fiches : le métier s’écrit, puis mène à l’offre.</h2>
    <p class="vera-lead">Robotique, droit, compta, terrain, fit culturel. Si le geste manque, vous suivez un module 8 min, puis vous tenez l’épreuve. Les fichiers portent la visite et le mode opératoire.</p>
    <div class="chips" style="margin-top:1.2rem">
      <a href="/n/vera/savoirs" style="color:var(--primary);font-weight:500">Entrer dans les fiches →</a>
      <a href="/n/vera/reliques" style="color:var(--primary)">Ouvrir les fichiers →</a>
      <a href="/n/vera/lexique" style="color:var(--primary)">Lexique →</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="vera-wrap">
    <p class="vera-kicker">Ce qu’Indeed n’active pas</p>
    <h2>Seniors, RSA, reprise</h2>
    <p class="vera-lead">Seniors à la journée, binômes, RSA, multi-activité, reprise. Publication gratuite, paiement sur candidat qualifié.</p>
    <div class="vera-grid g3" style="margin-top:1.5rem">
      @foreach($viviers as $v)
        <a class="v-card" href="/n/vera/viviers/{{ $v['slug'] }}">
          <p class="vera-kicker">{{ $v['kicker'] }}</p>
          <h3>{{ $v['name'] }}</h3>
          <p>{{ $v['description'] }}</p>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section paper">
  <div class="vera-wrap">
    <p class="vera-kicker">Collections</p>
    <h2>Sept portes, pas un filtre Indeed</h2>
    <div class="vera-grid g3" style="margin-top:1.4rem">
      @foreach($cols as $c)
        <a class="v-card" href="/n/vera/offres?collection={{ $c['slug'] }}">
          <h3>{{ $c['label'] }}</h3>
          <p>{{ $c['blurb'] }}</p>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="vera-wrap">
    <div style="display:flex;justify-content:space-between;align-items:end;gap:1rem;flex-wrap:wrap">
      <div>
        <p class="vera-kicker">Honneur</p>
        <h2>Qui répond à l’heure</h2>
      </div>
      <a href="/n/vera/pacte" style="color:var(--primary)">Classement public</a>
    </div>
    <ol style="margin:1.4rem 0 0;padding:0;list-style:none;border:1px solid var(--border);border-radius:1rem;overflow:hidden;background:var(--surface)">
      @foreach($league as $i => $h)
        <li style="display:flex;align-items:center;gap:1rem;padding:.9rem 1.1rem;border-top:{{ $i? '1px solid var(--border)':'0' }}">
          <span style="font-family:var(--display);width:1.4rem">{{ $i+1 }}</span>
          <span class="mark">{{ mb_substr($h['name'],0,1) }}</span>
          <div style="flex:1">
            <a href="/n/vera/maisons/{{ $h['slug'] }}" style="font-weight:500">{{ $h['name'] }}</a>
            <p style="font-size:.75rem;color:var(--muted);margin:0">{{ $h['industry'] }} · SLA {{ $h['slaDays'] }} j</p>
          </div>
          <div style="text-align:right">
            <div style="font-family:var(--display);font-size:1.8rem;line-height:1">{{ $h['honorScore'] }}</div>
            <p style="font-size:.7rem;color:var(--muted);margin:0">{{ $C::honorCaption($h['honorScore'], $h['honorDue'] ?? 1) }}</p>
          </div>
        </li>
      @endforeach
    </ol>
  </div>
</section>

<section class="section paper">
  <div class="vera-wrap">
    <p class="vera-kicker">Lexique</p>
    <h2>Les mots portent un !</h2>
    <p class="vera-lead">Verdict, Pacte, Brief, PPQC, épreuve. Chaque terme a une fiche candidat et une fiche entreprise.</p>
    <div class="chips" style="margin-top:1rem">
      @foreach($glossary as $g)
        <a class="badge" href="/n/vera/lexique#{{ $g['key'] }}">{{ $g['label'] }} <span class="term-bang">!</span></a>
      @endforeach
    </div>
  </div>
</section>
@endsection
