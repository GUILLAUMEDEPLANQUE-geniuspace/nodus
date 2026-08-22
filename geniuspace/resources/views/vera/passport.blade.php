@extends('layouts.vera')
@section('title', 'Mon carnet de preuves | Vera')
@section('description', 'Les tests réussis, les modules, les scores. Export JSON. Pas un CV généré par IA.')
@section('canonical', url('/n/vera/carnet'))
@section('content')
<div class="vera-wrap" style="padding:2.6rem 0 4rem;max-width:44rem" x-data="passport()">
  <p class="vera-kicker">Carnet de preuves</p>
  <h1 style="font-size:clamp(2rem,5vw,3.2rem)">Une preuve que vous emportez</h1>
  <p class="vera-lead">Pas un PDF LinkedIn. Un registre de tests tenus, de modules, de scores. Les entreprises paient ce dossier — les CV générés restent sur Indeed.</p>

  @if(!empty($carnet['preuves']))
  <div class="v-card" style="margin-top:1.6rem">
    <p class="vera-kicker">{{ $carnet['titre'] ?? 'Carnet' }}</p>
    <p style="font-size:.9rem;color:var(--muted)">Preuves déjà tenues, liées aux maisons. Ça voyage avec vous.</p>
    <ul style="list-style:none;padding:0;margin:.8rem 0">
      @foreach($carnet['preuves'] as $p)
        <li style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.7rem 0;gap:1rem">
          <div>
            <p style="font-weight:500;margin:0"><a href="{{ $p['href'] }}">{{ $p['titre'] }}</a></p>
            <p style="font-size:.75rem;color:var(--muted);margin:0">{{ $p['maison'] }} · {{ $p['quoi'] }}</p>
          </div>
          <p style="font-family:var(--display);font-size:1.1rem;color:var(--good);margin:0">Tenu</p>
        </li>
      @endforeach
    </ul>
    @if(!empty($carnet['details']))
      <div class="chips">
        @foreach($carnet['details'] as $d)
          <span class="badge">{{ $d['label'] }} · {{ $d['value'] }}</span>
        @endforeach
      </div>
    @endif
  </div>
  @endif

  <div class="v-card" style="margin-top:1.6rem">
    <p class="vera-kicker">Preuves tenues sur cet appareil</p>
    <p x-show="held.length===0" style="color:var(--muted)">Aucune encore. Passez un test. <a href="/n/vera/preuve" style="color:var(--primary)">Tests métier</a></p>
    <ul style="list-style:none;padding:0;margin:.8rem 0">
      <template x-for="r in held" :key="r.at">
        <li style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:.7rem 0">
          <div>
            <p style="font-weight:500;margin:0" x-text="r.title"></p>
            <p style="font-size:.75rem;color:var(--muted);margin:0" x-text="(r.missed||[]).join(', ') || 'tenu'"></p>
          </div>
          <p style="font-family:var(--display);font-size:1.8rem;color:var(--good);margin:0" x-text="r.score"></p>
        </li>
      </template>
    </ul>
    <div class="chips">
      <button class="vera-btn" type="button" @click="download()" :disabled="held.length===0">Exporter JSON</button>
      <a class="vera-btn ghost" href="/n/vera/preuve">Ajouter un test</a>
    </div>
  </div>
  <aside class="v-card" style="margin-top:1.4rem;background:var(--paper)">
    Format volontairement plat (JSON façon Open Badge). L’interop Credly / wallets vient après la boucle tenue — pas avant.
  </aside>
</div>
<script>
function passport(){
  let raw = { ledger: [] };
  try { raw = JSON.parse(localStorage.getItem('vera-passport')||'{"ledger":[]}'); } catch(e){}
  return {
    ledger: raw.ledger||[],
    get held(){ return this.ledger.filter(r => r.passed); },
    download(){
      const payload = { type:'VeraTalentPassport', version:'2026.1', issued: new Date().toISOString().slice(0,10), proofs: this.held };
      const blob = new Blob([JSON.stringify(payload,null,2)], {type:'application/json'});
      const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download='vera-passport.json'; a.click();
    }
  }
}
</script>
@endsection
