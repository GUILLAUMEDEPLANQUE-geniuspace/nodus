@extends('layouts.vera')
@section('title', 'Épreuve → module → retry | Vera')
@section('description', 'Boucle Vera : épreuve métier 6 min. Si vous ratez, un module tagué, puis vous rejouez. Standards nommés, relecteurs métier, pas un QCM LinkedIn.')
@section('canonical', url('/n/vera/preuve'))
@php $arenas = \App\Support\VeraCatalog::json('arenas'); $cred = \App\Support\VeraCatalog::json('credibility'); @endphp
@section('content')
<div class="vera-wrap" style="padding:2.6rem 0 4rem;max-width:48rem" x-data="preuve(@js($arenas))">
  <p class="vera-kicker">La boucle</p>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem)">Ratez, apprenez, rejouez. Pas un silence.</h1>
  <p class="vera-lead">HackerRank s’arrête au portillon. Vera tague le geste manqué, ouvre un module de 8 min, puis vous laisse rejouer. Le passeport ne tamponne qu’un tenu.</p>
  <ol class="vera-grid g3" style="margin-top:1.8rem;padding:0;list-style:none">
    <li class="v-card"><p class="vera-kicker">1</p><h3>Épreuve</h3><p>Geste, 6 min, seuil 70.</p></li>
    <li class="v-card"><p class="vera-kicker">2</p><h3>Module</h3><p>Seulement le tag loupé.</p></li>
    <li class="v-card"><p class="vera-kicker">3</p><h3>Retry + tampon</h3><p>Passport, puis candidature.</p></li>
  </ol>

  <h2 style="margin-top:2.6rem">Passez-en une</h2>
  <p style="color:var(--muted)">Consignation pour le bassin industriel. Garde-fous pour le remote Europe. Le même produit.</p>
  <div class="chips" style="margin:1rem 0">
    <template x-for="a in arenas" :key="a.id">
      <button type="button" class="badge" :class="arena && arena.id===a.id && 'primary'" @click="start(a)" x-text="a.skillTitle"></button>
    </template>
  </div>
  <div class="sim-box" x-show="arena" x-cloak>
    <p class="kicker" x-text="arena && (arena.kicker+' · '+arena.standard+' · '+arena.minutes+' min')"></p>
    <h3 style="font-family:var(--display);margin:.4rem 0" x-text="arena && arena.title"></h3>
    <p x-text="arena && arena.brief"></p>
    <template x-if="q">
      <div>
        <p style="font-weight:500" x-text="q.prompt"></p>
        <template x-for="ch in q.choices" :key="ch.id">
          <button type="button" @click="answer(ch)" x-text="ch.fr"></button>
        </template>
      </div>
    </template>
    <p class="ok" x-show="done && passed" x-cloak>Tenu. Score <span x-text="score"></span>. Tampon passeport.</p>
    <p class="ko" x-show="done && !passed" x-cloak>Sous le seuil. Tags : <span x-text="missed.join(', ')"></span>. Module 8 min, puis retry.</p>
    <button class="vera-btn" type="button" x-show="done" @click="start(arena)" style="margin-top:.8rem">Rejouer</button>
  </div>

  <section style="margin-top:2.8rem">
    <h2>Qui a signé les items</h2>
    <p style="color:var(--muted)">À l’étranger on demandera : qui a validé, est-ce à jour, le module tient-il ? Voici le registre.</p>
    <ul style="list-style:none;padding:0;margin:1.2rem 0;display:grid;gap:.7rem">
      @foreach($cred as $c)
        <li class="v-card">
          <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <p style="font-weight:500;margin:0">{{ $c['standard'] }}</p>
            <p style="font-size:.75rem;color:var(--muted);margin:0">n={{ $c['n'] }} · {{ $c['passRate'] }}% réussite · {{ $c['reviewed'] }}</p>
          </div>
          <p style="font-size:.9rem;color:var(--muted)">{{ $c['reviewer'] }}</p>
          <p>{{ $c['note'] }}</p>
        </li>
      @endforeach
    </ul>
  </section>
  <p><a href="/n/vera/passport" style="color:var(--primary)">Ouvrir le passport</a> · <a href="/n/vera/europe" style="color:var(--primary)">Europe</a></p>
</div>
<script>
function preuve(arenas){
  return {
    arenas, arena:null, i:0, score:0, missed:[], done:false, passed:false,
    get q(){ return this.arena ? this.arena.questions[this.i] : null },
    start(a){ this.arena=a; this.i=0; this.score=0; this.missed=[]; this.done=false; this.passed=false; },
    answer(ch){
      if(ch.ok) this.score += Math.round(100/this.arena.questions.length);
      else this.missed.push(ch.tag);
      if(this.i+1 < this.arena.questions.length){ this.i++; return; }
      this.done = true;
      this.passed = this.score >= (this.arena.threshold||70);
      try {
        const p = JSON.parse(localStorage.getItem('vera-passport')||'{"ledger":[]}');
        p.ledger.push({ id: this.arena.id, title: this.arena.skillTitle, score: this.score, passed: this.passed, at: new Date().toISOString(), missed: this.missed });
        localStorage.setItem('vera-passport', JSON.stringify(p));
      } catch(e){}
    }
  }
}
</script>
@endsection
