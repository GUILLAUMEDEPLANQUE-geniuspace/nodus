@extends('layouts.vera')
@php
  $C = \App\Support\VeraCatalog::class;
  $j = $job;
  $co = $j['company'];
  $p = $j['pack'] ?? null;
  $pos = $C::payPosition($j);
  $pay = $p['pay'] ?? null;
@endphp
@section('title', $j['title'].' — '.$co['name'].' · '.$j['salaryLabel'].' | Vera')
@section('description', \Illuminate\Support\Str::limit($j['description'], 165))
@section('canonical', url('/n/vera/offres/'.$j['slug']))
@section('og_type', 'article')
@push('jsonld')
<script type="application/ld+json">{!! json_encode($C::jobPostingLd($j), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@section('content')
<div class="vera-wrap" style="padding:2rem 0 4rem" x-data="veraJob(@js($p['sim'] ?? null), @js($p['gates'] ?? []))">
  <nav class="crumb">
    @if(!empty($trail))
      @foreach($trail as $c)
        @if($c['href'])<a href="{{ $c['href'] }}">{{ $c['title'] }}</a>@else{{ $c['title'] }}@endif
        @if(!$loop->last) · @endif
      @endforeach
    @else
      <a href="/n/vera">Vera</a> · <a href="/n/vera/offres">Offres</a> · {{ $j['title'] }}
    @endif
  </nav>
  <div style="display:grid;gap:2rem;margin-top:1.4rem" class="job-layout">
    <article>
      <div class="job-head">
        <span class="mark">{{ mb_substr($co['name'],0,1) }}</span>
        <div>
          <p class="co"><a href="/n/vera/maisons/{{ $co['slug'] }}">{{ $co['name'] }}</a> · {{ $co['industry'] }} · {{ $co['hqCity'] }}</p>
          <h1 style="font-size:clamp(2rem,5vw,3.2rem);margin:.25rem 0 0">{{ $j['title'] }}</h1>
          <p style="color:var(--muted);margin-top:.5rem">{{ $j['location'] }} · {{ $j['remoteLabel'] }} · {{ $j['contractLabel'] }} · {{ $j['seniorityLabel'] }} · équipe {{ $j['team'] }}</p>
        </div>
      </div>
      <div class="chips" style="margin:1rem 0">
        <span class="salary">{{ isset($details['salaire']) ? \App\Support\Engine::surface($details['salaire'], 'badge') : $j['salaryLabel'] }}</span>
        @if($pos)<span class="badge {{ $pos['band']==='below'?'bad':($pos['band']==='above'?'good':'') }}">{{ $pos['label'] }}</span>@endif
        @if(($pos['band'] ?? '') === 'below')<span class="badge bad">Cette offre est sous le marché</span>@endif
        <span class="badge {{ $j['honorTone'] }}">{{ $j['honorCaption'] }} · {{ $heritage['delayDays'] ?? $co['slaDays'] }} j</span>
        <span class="badge">Ghost {{ $j['ghostRisk'] }}</span>
        @if(!empty($j['full']))<span class="badge primary">Offre lue · épreuve</span>@endif
        <span class="badge primary">{{ $j['scarcity']['label'] }} {{ $j['scarcity']['score'] }}</span>
        @if(!empty($align))
          <span class="badge {{ $align['level']==='fort'?'good':($align['level']==='faible'?'bad':'') }}">{{ $align['word'] }}</span>
        @endif
        @if(!empty($j['pass']))<span class="badge bad">Verdict : Passez</span>
        @else<span class="badge good">Verdict : Allez</span>@endif
      </div>
      @if(!empty($align))
        <p style="font-size:.9rem;color:var(--muted);max-width:40rem">{{ $align['plain'] }}</p>
        @if(!empty($align['missing']))
          <p style="font-size:.85rem;max-width:40rem">Chaque critère manquant est une preuve à tenir — pas un badge de couleur.</p>
        @endif
      @endif

      <p style="font-size:1.05rem;max-width:42rem">{{ $j['description'] }}</p>

      @if($pay)
      <section class="section" style="padding:1.6rem 0;border:0">
        <p class="vera-kicker">Salaire vs marché</p>
        <h2>Bande {{ $pay['region'] }}</h2>
        <p style="font-size:.85rem;color:var(--muted)">{{ $pay['role'] }} · n={{ $pay['n'] }} · {{ $pay['year'] }} · {{ $pay['source'] }}</p>
        <div class="band">
          <div class="{{ $pos && $pos['mid'] <= $pay['p25'] ? 'on' : '' }}"><small>P25</small><strong>{{ (int)round($pay['p25']/1000) }}&nbsp;k€</strong></div>
          <div class="{{ $pos && $pos['mid'] > $pay['p25'] && $pos['mid'] <= $pay['p50'] ? 'on' : '' }}"><small>P50</small><strong>{{ (int)round($pay['p50']/1000) }}&nbsp;k€</strong></div>
          <div class="{{ $pos && $pos['mid'] > $pay['p50'] && $pos['mid'] <= $pay['p75'] ? 'on' : '' }}"><small>P75</small><strong>{{ (int)round($pay['p75']/1000) }}&nbsp;k€</strong></div>
          <div class="{{ $pos && $pos['mid'] > $pay['p75'] ? 'on' : '' }}"><small>P90</small><strong>{{ (int)round($pay['p90']/1000) }}&nbsp;k€</strong></div>
        </div>
      </section>
      @endif

      @if($p)
      <section>
        <p class="vera-kicker">Honnêteté</p>
        <h2>Le difficile, le bien, l’exceptionnel</h2>
        <div class="honesty">
          <article class="hard"><p class="vera-kicker">Difficile</p><p>{{ $p['honesty']['hard'] }}</p></article>
          <article class="good"><p class="vera-kicker">Bien</p><p>{{ $p['honesty']['good'] }}</p></article>
          <article class="ex"><p class="vera-kicker">Exceptionnel</p><p>{{ $p['honesty']['exceptional'] }}</p></article>
        </div>
      </section>

      @if(!empty($p['week']))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Semaine réelle</p>
        <h2>Où va le temps</h2>
        <div class="week">
          @foreach($p['week'] as $w)
            <div class="row">
              <strong>{{ $w['label'] }}</strong>
              <div class="bar"><i style="width:{{ $w['pct'] }}%"></i></div>
              <span>{{ $w['pct'] }}%</span>
            </div>
            <p style="font-size:.8rem;color:var(--muted);margin:0 0 .6rem">{{ $w['note'] }}</p>
          @endforeach
        </div>
      </section>
      @endif

      @if(!empty($p['career']))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Carrière</p>
        <h2>Trois étapes, pas un titre</h2>
        <div class="career">
          @foreach($p['career'] as $c)
            <article class="{{ !empty($c['current']) ? 'cur' : '' }}">
              <p class="vera-kicker">{{ $c['years'] }}{{ !empty($c['current']) ? ' · poste actuel' : '' }}</p>
              <h3>{{ $c['title'] }} · {{ $c['pay'] }}</h3>
              <div class="chips" style="margin-top:.4rem">
                @foreach($c['skills'] as $s)<span class="badge">{{ $s }}</span>@endforeach
                @foreach($c['certs']??[] as $s)<span class="badge primary">{{ $s }}</span>@endforeach
              </div>
            </article>
          @endforeach
        </div>
      </section>
      @endif

      @if(!empty($p['workplace']['image']))
      <section style="margin-top:2rem" x-data="{ spot: null }">
        <p class="vera-kicker">Le lieu</p>
        <h2>{{ $p['workplace']['title'] }}</h2>
        <p style="color:var(--muted)">{{ $p['workplace']['caption'] }}</p>
        <div class="tour" style="margin-top:.8rem">
          <img src="{{ $p['workplace']['image'] }}" alt="{{ $p['workplace']['title'] }}">
          @foreach($p['workplace']['hotspots']??[] as $h)
            <button class="hot" type="button" style="left:{{ $h['x'] }}%;top:{{ $h['y'] }}%" @click="spot = spot===@js($h['id']) ? null : @js($h['id'])">+</button>
          @endforeach
        </div>
        @foreach($p['workplace']['hotspots']??[] as $h)
          <div class="v-card" style="margin-top:.7rem" x-show="spot===@js($h['id'])" x-cloak>
            <h3>{{ $h['title'] }}</h3>
            <p>{{ $h['body'] }}</p>
          </div>
        @endforeach
      </section>
      @endif

      @if(!empty($p['voices']))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Voix</p>
        <h2>Les collègues, pas un slogan</h2>
        <div class="voices" style="margin-top:.8rem">
          @foreach($p['voices'] as $v)
            <article class="voice">
              <img src="{{ $v['portrait'] }}" alt="{{ $v['name'] }}">
              <div>
                <p style="font-weight:500;margin:0">{{ $v['name'] }}</p>
                <p style="font-size:.75rem;color:var(--muted);margin:0">{{ $v['role'] }} · {{ $v['years'] }}</p>
                <p style="margin:.5rem 0 0"><em>{{ $v['question'] }}</em> {{ $v['answer'] }}</p>
                @if(!empty($v['video']))
                  <video src="{{ $v['video'] }}" controls style="width:100%;margin-top:.6rem;border-radius:.5rem"></video>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      </section>
      @endif

      @if(!empty($p['tools']))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Outils</p>
        <h2>Le matériel, nommé</h2>
        <div class="tools" style="margin-top:.8rem">
          @foreach($p['tools'] as $t)
            <figure>
              @if(!empty($t['image']))<img src="{{ $t['image'] }}" alt="{{ $t['name'] }}">@endif
              <figcaption><strong>{{ $t['name'] }}</strong><br>{{ $t['why'] }}</figcaption>
            </figure>
          @endforeach
        </div>
      </section>
      @endif

      @if(!empty($p['benefits']))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Avantages concrets</p>
        <div class="vera-grid g2">
          @foreach($p['benefits'] as $b)
            <article><strong>{{ $b['label'] }}</strong><p style="margin:.2rem 0 0;font-size:.9rem">{{ $b['why'] }}</p></article>
          @endforeach
        </div>
      </section>
      @endif

      @if(!empty($p['sim']))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Épreuve métier</p>
        <h2>Le geste avant le CV</h2>
        <p><a href="/n/vera/videos" style="color:var(--primary)">Voir l’épreuve filmée · 6 min → preuve dans le carnet</a></p>
        <div class="sim-box">
          <p class="kicker">Simulation · {{ $p['sim']['kind'] }} · 6 min</p>
          <p>{{ $p['sim']['brief'] }}</p>
          @if(!empty($p['sim']['symptom']))<p><em>{{ $p['sim']['symptom'] }}</em></p>@endif
          @if(!empty($p['sim']['setting']))<p><em>{{ $p['sim']['setting'] }}</em></p>@endif
          @if(($p['sim']['kind']??'')==='code' && !empty($p['sim']['snippet']))
            <pre style="overflow:auto;background:#111;padding:.8rem;border-radius:.5rem;font-size:.78rem">{{ $p['sim']['snippet'] }}</pre>
            <p>{{ $p['sim']['prompt'] ?? '' }}</p>
          @endif

          {{-- machine : ordre des étapes --}}
          <template x-if="sim && sim.kind==='machine'">
            <div>
              <p style="font-size:.8rem;color:#b7c4b0">Cliquez dans l’ordre. Un geste trop tôt échoue.</p>
              <template x-for="st in sim.steps" :key="st.id">
                <button type="button" @click="pick(st.id)" :class="picked.includes(st.id) && 'on'" x-text="st.text"></button>
              </template>
            </div>
          </template>
          <template x-if="sim && (sim.kind==='circuit' || sim.kind==='code' || sim.kind==='care')">
            <div>
              <template x-if="sim.kind==='circuit'">
                <div class="chips" style="margin:.6rem 0">
                  <template x-for="pr in (sim.probes||[])" :key="pr.id">
                    <span class="badge" style="background:#2a2a24;color:#eee;border-color:#444" x-text="pr.label+' · '+pr.reading"></span>
                  </template>
                </div>
              </template>
              <template x-if="sim.kind==='care'">
                <div>
                  <p x-text="(sim.beats[beat]||{}).prompt"></p>
                  <template x-for="ch in ((sim.beats[beat]||{}).choices||[])" :key="ch.id">
                    <button type="button" @click="choose(ch)" x-text="ch.text"></button>
                  </template>
                </div>
              </template>
              <template x-if="sim.kind!=='care'">
                <div>
                  <template x-for="ch in (sim.choices||[])" :key="ch.id">
                    <button type="button" @click="choose(ch)" x-text="ch.text"></button>
                  </template>
                </div>
              </template>
            </div>
          </template>
          <p class="ok" x-show="verdict===true" x-cloak>Tenu. Score <span x-text="score"></span> — le profil est qualifié.</p>
          <p class="ko" x-show="verdict===false" x-cloak>Manqué. <span x-text="lesson"></span> Module 8 min, puis on rejoue.</p>
        </div>
      </section>
      @endif
      @endif

      @if(!empty($also))
      <section style="margin-top:2rem">
        <p class="vera-kicker">Aussi dans cet univers</p>
        <h2>Les profils qui ont réussi un test proche ont aussi regardé</h2>
        <div class="vera-grid g2" style="margin-top:.8rem">
          @foreach($also as $a)
            <a class="v-card" href="{{ $a['href'] }}" style="display:block">
              <p class="vera-kicker">{{ $a['nature'] }}</p>
              <h3 style="margin:.2rem 0 0">{{ $a['title'] }}</h3>
              <p style="font-size:.85rem;color:var(--muted);margin:.3rem 0 0">{{ $a['plain'] }}</p>
            </a>
          @endforeach
        </div>
      </section>
      @endif

      <section style="margin-top:2rem">
        <p class="vera-kicker">Le poste</p>
        <h2>Responsabilités</h2>
        <ul>@foreach($j['responsibilities']??[] as $r)<li>{{ $r }}</li>@endforeach</ul>
        <h2>Exigences</h2>
        <ul>@foreach($j['requirements']??[] as $r)<li>{{ $r }}</li>@endforeach</ul>
        @if(!empty($j['nice']))<h2>Un plus</h2><ul>@foreach($j['nice'] as $r)<li>{{ $r }}</li>@endforeach</ul>@endif
      </section>
    </article>

    <aside class="side apply-box">
      <p class="vera-kicker">Candidater</p>
      <p class="salary">{{ isset($details['salaire']) ? \App\Support\Engine::surface($details['salaire'], 'badge') : $j['salaryLabel'] }}</p>
      <p style="font-size:.85rem;color:var(--muted)">Facture entreprise si le test est réussi : {{ $j['ppqc']['euros'] }} €</p>
      <p style="font-size:.8rem;color:var(--muted)">{{ $j['ppqc']['why'] }}</p>
      <p style="font-size:.8rem;margin-top:.6rem">{{ $heritage['from']->title ?? $co['name'] }} répond en {{ $heritage['delayDays'] ?? $co['slaDays'] }} jours. Fiabilité {{ $heritage['honor'] ?? $co['honorScore'] }}.</p>
      <div class="stepper" style="margin-top:.8rem">
        <span :class="step>=1 && 'on'">1 Lire</span>
        <span :class="step>=2 && 'on'">2 Honnêteté</span>
        <span :class="step>=3 && 'on'">3 Semaine</span>
        <span :class="step>=4 && 'on'">4 Test</span>
        <span :class="step>=5 && 'on'">5 Grille</span>
        <span :class="step>=6 && 'on'">6 Brief</span>
        <span :class="step>=7 && 'on'">7 Envoi</span>
      </div>
      <div x-show="step<7">
        <p style="font-size:.85rem" x-text="['','Lisez le salaire et le difficile.','Le difficile n’est pas un slogan.','La semaine est écrite.','Passez le test à gauche.','La grille est publique.','Trois faits, pas un CV.'][step]"></p>
        <button class="vera-btn" type="button" style="margin-top:.7rem;width:100%" @click="step=Math.min(7,step+1)">Continuer</button>
      </div>
      <form x-show="step>=7" x-cloak @submit.prevent="sent=true">
        <label style="font-size:.75rem;color:var(--muted)">Brief — livré, refusé, suite
          <textarea required rows="5" style="width:100%;margin-top:.3rem;border:1px solid var(--border);border-radius:.5rem;padding:.6rem;font:inherit;background:var(--bg)" placeholder="Trois faits. Pas quatre pages."></textarea>
        </label>
        <button class="vera-btn" type="submit" style="margin-top:.7rem;width:100%" x-show="!sent">Envoyer — réponse sous {{ $co['slaDays'] }} j</button>
        <p class="ok" x-show="sent" style="color:var(--good)">Candidature envoyée. Réponse sous {{ $co['slaDays'] }} jours.</p>
      </form>
      <p style="font-size:.75rem;color:var(--subtle);margin-top:.8rem">Coordonnées après le test, pas avant. Un 55 ouvre un module, pas un silence.</p>
    </aside>
  </div>
</div>
<style>
@media (min-width: 960px) {
  .job-layout { grid-template-columns: 1fr 20rem; }
}
[x-cloak]{display:none!important}
</style>
<script>
function veraJob(sim, gates){
  return {
    sim, gates, step: 1, picked: [], verdict: null, score: 0, lesson: '', beat: 0, sent: false,
    pick(id){
      if(this.picked.includes(id)) return;
      this.picked.push(id);
      const order = this.sim.order || [];
      if(this.picked.length === order.length){
        const ok = this.picked.every((x,i)=>x===order[i]);
        this.finish(ok, ok? 86 : 42, ok? '' : (this.sim.explain||'Ordre faux. On consigne d’abord.'));
      }
    },
    choose(ch){
      if(this.sim.kind==='care'){
        if(!ch.ok){ this.finish(false, 40, ch.why); return; }
        if(this.beat+1 < (this.sim.beats||[]).length){ this.beat++; this.lesson = ch.why; return; }
        this.finish(true, 88, ch.why);
        return;
      }
      this.finish(!!ch.ok, ch.ok? 90 : 38, ch.why||'');
    },
    finish(ok, score, lesson){
      this.verdict = ok; this.score = score; this.lesson = lesson;
      if(ok) this.step = Math.max(this.step, 5);
    }
  }
}
</script>
@endsection
