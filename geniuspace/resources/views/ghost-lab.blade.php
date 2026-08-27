@extends('layouts.app')
@section('title', 'Laboratoire de stratégies — '.$node->title)
@section('description', 'Ghost estime, observe, cherche à réfuter. Il ne déploie jamais tout seul.')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem;max-width:58rem">
  <p class="kicker">Laboratoire · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.2rem);line-height:.95">Autonome sur la stratégie. Jamais sur l’autorité.</h1>
  <p class="lede">Une prédiction n’est pas une observation. Ghost lit le monde, pose une hypothèse, tente de la réfuter. Le déploiement reste une preview.</p>

  <form method="post" action="/n/{{ $node->slug }}/ghost/lab" style="margin:1.2rem 0;display:flex;flex-wrap:wrap;gap:.5rem">
    @csrf
    <input name="objective" value="{{ $objective }}" maxlength="240" style="flex:1;min-width:16rem" placeholder="Objectif">
    <button class="btn" type="submit">Chercher</button>
  </form>

  @if($board)
    @php $w = $board['world'] ?? []; $h = $board['hypotheses'][0] ?? []; $best = $board['best'] ?? []; @endphp
    <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
      <p class="kicker">Monde observé</p>
      <p class="muted">{{ $w['threads'] ?? 0 }} sujets · {{ $w['replies'] ?? 0 }} réponses · {{ $w['media'] ?? 0 }} médias · {{ $w['grants'] ?? 0 }} preuves</p>
      <p>Participation {{ number_format(($w['participation'] ?? 0)*100, 2, ',', ' ') }} % · complétion {{ number_format(($w['completion'] ?? 0)*100, 2, ',', ' ') }} %</p>
    </section>

    @if($h)
      <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
        <p class="kicker">Hypothèse · {{ $h['code'] ?? '' }} · {{ $h['status'] ?? 'draft' }}</p>
        <p>{{ $h['observation'] ?? '' }}</p>
        <p><strong>{{ $h['hypothesis'] ?? '' }}</strong></p>
        <p class="muted">Prédiction : {{ $h['prediction'] ?? '' }}</p>
        <p class="muted">Contre-hypothèse : {{ $h['counter_hypothesis'] ?? '' }}</p>
        @if(!empty($h['verdict']))<p>{{ $h['verdict'] }}</p>@endif
      </section>
    @endif

    <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
      <p class="kicker">Objectif</p>
      <p class="font-display" style="font-size:1.4rem;margin:.2rem 0 .8rem">{{ $board['objective'] }}</p>
      <div class="grid-2" style="gap:.5rem">
        <p><span class="muted">Stratégies explorées</span><br><strong>{{ $board['explored'] }}</strong></p>
        <p><span class="muted">Expériences dessinées</span><br><strong>{{ $board['experiments'] }}</strong></p>
        <p><span class="muted">Observées dans le monde</span><br><strong>{{ $board['experiments_observed'] }}</strong></p>
        <p><span class="muted">Tenues</span><br><strong>{{ $board['winners'] }}</strong></p>
        <p><span class="muted">Mode</span><br><strong>{{ ($board['mode'] ?? '') === 'explore' ? 'exploration' : 'exploitation' }}</strong></p>
      </div>
      <p class="muted" style="margin-top:.6rem">{{ $board['honesty'] ?? '' }}</p>
    </section>

    @if($best)
      <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
        <p class="kicker">Candidate · {{ $best['code'] ?? '' }} · {{ $best['status'] ?? 'untested' }}</p>
        <p>
          Prédiction {{ number_format(($best['expected_gain'] ?? 0)*100, 1, ',', ' ') }} %
          · observation {{ $best['observed_gain'] === null ? 'inconnue' : number_format($best['observed_gain']*100, 1, ',', ' ').' %' }}
          · nouveauté {{ number_format(($best['novelty'] ?? 0)*100, 0) }} %
          · risque {{ number_format(($best['risk'] ?? 0)*100, 0) }} %
        </p>
        <p class="muted">
          Leviers :
          @foreach(($best['genome']['mechanisms'] ?? []) as $m)
            <span class="chip">{{ \App\Support\GhostStrategy::label($m) }}</span>
          @endforeach
        </p>
        <form method="post" action="/n/{{ $node->slug }}/ghost/lab/deploy" style="margin-top:.8rem"
              onsubmit="event.preventDefault();fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({code:'{{ $best['code'] }}'})}).then(r=>r.json()).then(d=>this.querySelector('[data-out]').textContent=d.applied===false?'Preview. Rien n’est déployé.':'?');">
          @csrf
          <button class="btn" type="submit">Préparer le déploiement</button>
          <span class="muted" data-out></span>
        </form>
      </section>
    @endif

    @if(!empty($board['attacks']))
      <p class="kicker">Contre-stratégies</p>
      <ul>
        @foreach($board['attacks'] as $a)
          <li><strong>{{ $a['id'] }}</strong> — {{ $a['attack'] }}</li>
        @endforeach
      </ul>
    @endif

    <p class="kicker" style="margin-top:1.4rem">Famille (estimations)</p>
    <ol style="padding-left:1.1rem">
      @foreach($board['pool'] ?? [] as $s)
        <li style="margin:.35rem 0">
          {{ $s['code'] }} · {{ $s['status'] }}
          · pred {{ $s['expected_gain'] }}
          · obs {{ $s['observed_gain'] === null ? '—' : $s['observed_gain'] }}
          · {{ implode(' + ', array_map(fn($m) => \App\Support\GhostStrategy::label($m), $s['genome']['mechanisms'] ?? [])) }}
        </li>
      @endforeach
    </ol>
    <p class="muted" style="margin-top:1rem">{{ $board['signature'] }}</p>
  @else
    <p class="muted">Posez un objectif. Ghost lit d’abord le monde. Il ne demande pas trois idées à un modèle.</p>
  @endif

  <p style="margin-top:2rem"><a href="/n/{{ $node->slug }}">Retour au lieu</a> · <a href="/n/{{ $node->slug }}/ghost/gym">Salle d’épreuve</a></p>
</main>
@endsection
