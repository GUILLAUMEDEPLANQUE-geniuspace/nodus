@extends('layouts.app')
@section('title', 'Salle d’épreuve — '.$node->title)
@section('description', 'Ghost s’entraîne. Douze épreuves, huit niveaux. L’autonomie reste plafonnée.')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem;max-width:58rem">
  <p class="kicker">Salle d’épreuve · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.2rem);line-height:.95">Ghost ne lit pas plus. Il s’entraîne.</h1>
  <p class="lede">Chaque tour laisse un fait, une observation, parfois une erreur. Une hallucination n’entre pas en mémoire. Acheter, embaucher, ouvrir : confirmation humaine.</p>

  <form method="post" action="/n/{{ $node->slug }}/ghost/gym" style="margin:1.2rem 0">
    @csrf
    <button class="btn" type="submit">{{ $run ? 'Relancer les 12 épreuves' : 'Lancer 12 épreuves' }}</button>
  </form>

  <div class="grid-2" style="gap:.6rem;margin:1rem 0">
    @foreach(['knowledge'=>'Connaissance','reasoning'=>'Raisonnement','planning'=>'Plan','tools'=>'Outils','grounding'=>'Ancrage','verification'=>'Vérif','autonomy'=>'Autonomie','reliability'=>'Fiabilité'] as $k => $lab)
      <div class="card" style="padding:.8rem 1rem">
        <p class="kicker">{{ $lab }}</p>
        <p class="font-display" style="font-size:1.8rem;margin:0">{{ $maturity[$k] ?? 0 }}</p>
      </div>
    @endforeach
  </div>
  <p class="muted">{{ $maturity['skills'] ?? 8 }} compétences · {{ $maturity['facts'] ?? 0 }} faits · {{ $maturity['episodes'] ?? 0 }} épisodes · {{ $maturity['fails'] ?? 0 }} erreurs · autonomie plafonnée à 54</p>

  @if($run)
    <p class="kicker" style="margin-top:2rem">Résultat · {{ $run['wins'] }}/{{ count($run['episodes']) }} tenus</p>
    <ol style="padding-left:1.1rem">
      @foreach($run['episodes'] as $e)
        <li style="margin:.6rem 0">
          <strong>{{ $e['success'] ? 'tenu' : 'raté' }}</strong>
          · L{{ $e['level'] }} · {{ $e['skill'] }}
          <br>{{ $e['task'] }}
          <br><span class="muted">{{ \Illuminate\Support\Str::limit($e['reply'], 180) }}</span>
        </li>
      @endforeach
    </ol>
  @else
    <p class="kicker" style="margin-top:2rem">Douze problèmes</p>
    <ol style="padding-left:1.1rem">
      @foreach($tasks as $t)
        <li style="margin:.4rem 0">L{{ $t['level'] }} · {{ $t['prompt'] }}</li>
      @endforeach
    </ol>
  @endif

  <p style="margin-top:2rem"><a href="/n/{{ $node->slug }}">Retour au lieu</a> · <a href="/n/{{ $node->slug }}/ghost/maturity">Maturité JSON</a></p>
</main>
@endsection
