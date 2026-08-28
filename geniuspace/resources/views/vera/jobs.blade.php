@extends('layouts.vera')
@php
  $C = \App\Support\VeraCatalog::class;
  $q = request()->only(['q','remote','contract','seniority','collection','pacte','sort']);
  $jobs = $C::filter($q);
  $cols = $C::json('collections');
  $viviers = $C::json('viviers');
  $col = collect($cols)->firstWhere('slug', $q['collection'] ?? '');
  $slugs = array_column($jobs, 'slug');
  $jobNodes = $slugs ? \App\Models\GpNode::query()->whereIn('slug', $slugs)->get() : collect();
  $alignBySlug = [];
  if ($jobNodes->isNotEmpty()) {
      $bag = \App\Support\Engine::alignMany(\App\Support\Grantor::carnetId(), $jobNodes->pluck('id')->all());
      foreach ($jobNodes as $jn) {
          $alignBySlug[$jn->slug] = $bag[$jn->id] ?? null;
      }
  }
@endphp
@section('title', $col ? $col['label'].' — offres d’emploi 2026 | Vera' : 'Offres d’emploi à salaire publié | Vera')
@section('description', $col ? $col['blurb'].' Salaire publié, délai de réponse, grille publique.' : 'Toutes les offres Vera : salaire publié, délai de réponse, Schema JobPosting. Classées par adéquation, jamais par budget pub.')
@section('canonical', url('/n/vera/offres'))
@push('jsonld')
<script type="application/ld+json">
{!! json_encode(['@context'=>'https://schema.org','@type'=>'ItemList','name'=>'Offres Vera','itemListElement'=>array_map(fn($j,$i)=>['@type'=>'ListItem','position'=>$i+1,'url'=>url('/n/vera/offres/'.$j['slug']),'name'=>$j['title'].' — '.$j['company']['name']], $jobs, array_keys($jobs))], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem">
  <h1 style="font-size:clamp(2rem,5vw,3rem)">{{ $col['label'] ?? 'Toutes les offres' }}</h1>
  <p class="vera-lead">{{ $col['blurb'] ?? 'Classées par adéquation, fiabilité, annonces fantômes. Jamais par budget pub.' }}</p>
  <form class="vera-search" method="get">
    <input name="q" value="{{ $q['q'] ?? '' }}" placeholder="Métier, ville, geste">
    <button class="vera-btn" type="submit">Filtrer</button>
  </form>
  <form class="filters" method="get">
    <input type="hidden" name="q" value="{{ $q['q'] ?? '' }}">
    <select name="remote" onchange="this.form.submit()">
      <option value="">Lieu</option>
      @foreach($C::REMOTE as $k=>$l)<option value="{{ $k }}" @selected(($q['remote']??'')===$k)>{{ $l }}</option>@endforeach
    </select>
    <select name="contract" onchange="this.form.submit()">
      <option value="">Contrat</option>
      @foreach($C::CONTRACT as $k=>$l)<option value="{{ $k }}" @selected(($q['contract']??'')===$k)>{{ $l }}</option>@endforeach
    </select>
    <select name="seniority" onchange="this.form.submit()">
      <option value="">Niveau</option>
      @foreach($C::SENIORITY as $k=>$l)<option value="{{ $k }}" @selected(($q['seniority']??'')===$k)>{{ $l }}</option>@endforeach
    </select>
    <select name="sort" onchange="this.form.submit()">
      <option value="signal" @selected(($q['sort']??'signal')==='signal')>Signal</option>
      <option value="honneur" @selected(($q['sort']??'')==='honneur')>Fiabilité</option>
      <option value="recent" @selected(($q['sort']??'')==='recent')>Récent</option>
      <option value="salary" @selected(($q['sort']??'')==='salary')>Salaire</option>
    </select>
  </form>
  <div class="chips" style="margin-bottom:1.2rem">
    <a class="badge {{ ($q['pacte']??'')==='solide'?'primary':'' }}" href="/n/vera/offres?{{ http_build_query(array_filter($q+['pacte'=>($q['pacte']??'')==='solide'?'':'solide'])) }}">Répondent à l’heure</a>
    @foreach($cols as $c)
      <a class="badge {{ ($q['collection']??'')===$c['slug']?'primary':'' }}" href="/n/vera/offres?collection={{ $c['slug'] }}">{{ $c['label'] }}</a>
    @endforeach
  </div>
  <p style="font-size:.8rem;color:var(--muted)">{{ count($jobs) }} offres · salaires publiés</p>
  <div class="vera-grid" style="margin-top:1rem;gap:.9rem">
    @foreach($jobs as $job)
      @include('vera.partials.job-card', ['job' => $job, 'align' => $alignBySlug[$job['slug'] ?? ''] ?? null])
    @endforeach
  </div>
</div>
@endsection
