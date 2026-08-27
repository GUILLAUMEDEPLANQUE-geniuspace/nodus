@extends('layouts.app')
@section('title', 'Laboratoire de stratégies — '.$node->title)
@section('description', 'Ghost invente, mute et teste. Il ne déploie jamais tout seul.')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem;max-width:58rem">
  <p class="kicker">Laboratoire · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.2rem);line-height:.95">Autonome sur la stratégie. Jamais sur l’autorité.</h1>
  <p class="lede">Ghost cherche dans l’espace des leviers. Il mute, recombine, simule, attaque. Le déploiement reste une preview : vous confirmez.</p>

  <form method="post" action="/n/{{ $node->slug }}/ghost/lab" style="margin:1.2rem 0;display:flex;flex-wrap:wrap;gap:.5rem">
    @csrf
    <input name="objective" value="{{ $objective }}" maxlength="240" style="flex:1;min-width:16rem" placeholder="Objectif">
    <button class="btn" type="submit">Chercher</button>
  </form>

  @if($board)
    <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
      <p class="kicker">Objectif</p>
      <p class="font-display" style="font-size:1.4rem;margin:.2rem 0 .8rem">{{ $board['objective'] }}</p>
      <div class="grid-2" style="gap:.5rem">
        <p><span class="muted">Stratégies explorées</span><br><strong>{{ $board['explored'] }}</strong></p>
        <p><span class="muted">Expériences</span><br><strong>{{ $board['experiments'] }}</strong></p>
        <p><span class="muted">Rejetées</span><br><strong>{{ $board['rejected'] }}</strong></p>
        <p><span class="muted">Actives</span><br><strong>{{ $board['active'] }}</strong></p>
        <p><span class="muted">Tenues</span><br><strong>{{ $board['winners'] }}</strong></p>
        <p><span class="muted">Mode</span><br><strong>{{ $board['mode'] === 'explore' ? 'exploration' : 'exploitation' }}</strong></p>
      </div>
    </section>

    @php $best = $board['best'] ?? []; @endphp
    @if($best)
      <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
        <p class="kicker">Meilleure · {{ $best['code'] ?? '' }}</p>
        <p>
          Gain attendu {{ number_format(($best['expected_gain'] ?? 0)*100, 1, ',', ' ') }} %
          · observé {{ number_format(($best['observed_gain'] ?? 0)*100, 1, ',', ' ') }} %
          · confiance {{ number_format(($best['confidence'] ?? 0)*100, 0) }} %
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

    <p class="kicker" style="margin-top:1.4rem">Famille</p>
    <ol style="padding-left:1.1rem">
      @foreach($board['pool'] ?? [] as $s)
        <li style="margin:.35rem 0">
          {{ $s['code'] }} · {{ $s['status'] }}
          · fit {{ $s['fitness'] }}
          · {{ implode(' + ', array_map(fn($m) => \App\Support\GhostStrategy::label($m), $s['genome']['mechanisms'] ?? [])) }}
        </li>
      @endforeach
    </ol>
    <p class="muted" style="margin-top:1rem">{{ $board['signature'] }}</p>
  @else
    <p class="muted">Posez un objectif. Ghost explore l’espace — il ne demande pas trois idées à un modèle.</p>
  @endif

  <p style="margin-top:2rem"><a href="/n/{{ $node->slug }}">Retour au lieu</a> · <a href="/n/{{ $node->slug }}/ghost/gym">Salle d’épreuve</a></p>
</main>
@endsection
