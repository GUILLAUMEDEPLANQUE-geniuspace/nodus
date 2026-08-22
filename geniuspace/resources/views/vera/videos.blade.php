@extends('layouts.vera')
@section('title', 'Épreuves filmées | Vera')
@section('description', 'Un test vidéo de 6 minutes. La suite ouvre le brief et tamponne le carnet. Pas une vidéo RH.')
@section('canonical', url('/n/vera/videos'))
@section('content')
<div class="vera-wrap" style="padding:2.4rem 0 4rem;max-width:44rem">
  <nav class="crumb"><a href="/n/vera">Vera</a> · Épreuves filmées</nav>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem);margin-top:.6rem">Le candidat ne regarde pas une vidéo RH</h1>
  <p class="vera-lead">Il valide une étape. La preuve voyage avec lui. Le brief s’ouvre. Le délai de la maison reste public.</p>
  @forelse($medias as $m)
    <article class="v-card" style="margin-top:1.2rem">
      <p class="vera-kicker">{{ $m->mode === 'interview' ? 'Épreuve' : 'Film' }} · {{ \App\Support\Grantor::canSeeMedia($m) ? 'Ouvert' : 'Teaser '.$m->teaser_sec.' s' }}</p>
      <h2><a href="/n/vera/v/{{ $m->id }}">{{ $m->title }}</a></h2>
      <p>{{ $m->transcript }}</p>
      <p style="margin-top:.6rem"><a class="vera-btn" href="/n/vera/v/{{ $m->id }}">Lancer</a></p>
    </article>
  @empty
    <p class="muted">Pas encore d’épreuve filmée.</p>
  @endforelse
</div>
@endsection
