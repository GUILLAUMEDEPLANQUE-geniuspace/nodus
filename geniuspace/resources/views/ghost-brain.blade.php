@extends('layouts.app')
@section('title', 'Cerveau — '.$node->title)
@section('description', 'Ghost se souvient, relie, oublie. Il refuse de parler sans preuve. Rien n’est déployé.')
@section('robots', 'noindex')
@section('content')
<main class="wrap" style="padding:2rem 1.25rem 8rem;max-width:58rem">
  <p class="kicker">Cerveau · {{ $node->title }}</p>
  <h1 class="font-display" style="font-size:clamp(2rem,6vw,3.2rem);line-height:.95">Autonome sur la stratégie. Jamais sur l’autorité.</h1>
  <p class="lede">Ghost relie ce qu’il a vu, oublie ce qui n’est plus tenu, et se tait s’il n’a pas de preuve. Une consolidation n’écrit rien dans le monde.</p>

  @if($world)
    <section class="card" style="padding:1.1rem 1.3rem;margin:1.2rem 0">
      <p class="kicker">Monde tenu maintenant</p>
      <p>{{ (int) $world['visits'] }} visites · {{ (int) $world['grants'] }} preuves · {{ (int) $world['threads'] }} fils · {{ (int) $world['replies'] }} réponses</p>
      <p class="muted">Comptages du lieu. Pas une expérience. Ghost n’invente pas le delta.</p>
    </section>
  @endif

  <form method="post" action="/n/{{ $node->slug }}/ghost/cerveau/ask" style="margin:1.2rem 0;display:flex;flex-wrap:wrap;gap:.5rem">
    @csrf
    <input name="q" value="{{ $ask['q'] ?? '' }}" maxlength="400" style="flex:1;min-width:16rem" placeholder="Combien coûte le print ?">
    <button class="btn" type="submit">Demander une preuve</button>
  </form>

  <form method="post" action="/n/{{ $node->slug }}/ghost/cerveau" style="margin:.4rem 0 1.4rem">
    @csrf
    <button class="btn" type="submit">Consolider (hors interaction)</button>
  </form>

  @if($ask)
    @php $t = $ask['tribunal']; $c = $ask['consistency']; @endphp
    <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
      <p class="kicker">Réponse fondée · {{ $t['ok'] ? 'tenue' : 'refus' }}</p>
      @if($t['ok'])
        <pre style="white-space:pre-wrap;font:inherit">{{ $t['answer'] }}</pre>
        <p class="muted">Confiance {{ number_format($t['confidence']*100, 0) }} % · {{ implode(', ', str_replace(['world','evidence','belief'], ['monde','preuve','croyance'], $t['layers'])) }}</p>
      @else
        <p><strong>{{ $t['refusal'] }}</strong></p>
      @endif
      <p class="muted">Cohérence : {{ $c['status_fr'] ?? $c['status'] }} · recouvrement {{ number_format(($c['overlap'] ?? 0)*100, 0) }} %</p>
    </section>
  @endif

  @if($cycle)
    <section class="card" style="padding:1.2rem 1.3rem;margin:1rem 0">
      <p class="kicker">Consolidation · rien n’est déployé</p>
      <p>{{ $cycle['indexed'] }} indexations · {{ $cycle['pruned'] }} liens oubliés · {{ count($cycle['dreams']) }} liaisons candidates</p>
      <p class="muted">Ghost propose des rapprochements. L’autorité reste humaine.</p>
    </section>
  @endif

  @if($journal)
    <p class="kicker">Journal de consolidation</p>
    <ul>
      @forelse($journal as $d)
        <li><strong>{{ $d['code'] }}</strong> — {{ $d['insight'] }} <span class="muted">{{ $d['applied'] ? 'écrit' : 'candidat' }}</span></li>
      @empty
        <li class="muted">Encore vide. Consolider pour relier des souvenirs distants.</li>
      @endforelse
    </ul>
  @endif

  @if($synapses)
    <p class="kicker">Liens</p>
    <ul>
      @foreach($synapses as $s)
        <li>{{ $s['label_source'] ?? $s['source'] }} → {{ $s['label_target'] ?? $s['target'] }} · {{ $s['relation_fr'] ?? $s['relation'] }} · {{ number_format($s['effective']*100, 0) }} %</li>
      @endforeach
    </ul>
  @endif
</main>
@endsection
