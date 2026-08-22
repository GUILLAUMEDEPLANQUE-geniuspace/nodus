{{-- Ghost du lieu : agent ancré. Pas de jargon moteur. --}}
@isset($node)
@php
  $ghostProfile = \App\Support\Ghost::profile($node);
  $ghostLabel = \App\Support\Ghost::hostName($node);
@endphp
<div class="ghost-root" x-data="ghostOrb('{{ $node->slug }}')" x-cloak>
  <button type="button" class="ghost-orb" @click="open = !open; if(open && !boot){boot=true; hello()}" :title="ghostLabel" aria-label="{{ $ghostLabel }}">
    <span class="ghost-orb-core"></span>
  </button>
  <div class="ghost-panel" x-show="open" x-transition @click.outside="open=false">
    <header class="ghost-head">
      <strong>{{ $ghostLabel }}</strong>
      <span class="muted" x-text="profile || '{{ $ghostProfile }}'"></span>
      <button type="button" class="btn-ghost" @click="open=false" aria-label="Fermer">×</button>
    </header>
    <div class="ghost-log" x-ref="log">
      <template x-for="(m, i) in messages" :key="i">
        <div class="ghost-msg" :class="m.role">
          <p x-text="m.content"></p>
          <template x-if="m.actions && m.actions.length">
            <div class="ghost-actions">
              <template x-for="(a, j) in m.actions" :key="j">
                <a class="chip" :href="a.href" x-text="a.label"></a>
              </template>
            </div>
          </template>
        </div>
      </template>
      <p class="muted" x-show="loading">…</p>
    </div>
    <form class="ghost-form" @submit.prevent="send">
      <input type="text" x-model="input" maxlength="800" placeholder="Prix, certificat, épreuve, salles…" :disabled="loading" autocomplete="off">
      <button class="btn" type="submit" :disabled="loading || !input.trim()">OK</button>
    </form>
  </div>
</div>
<style>
  .ghost-root{position:fixed;bottom:5.5rem;right:1.25rem;z-index:80;font-family:var(--font,system-ui,sans-serif)}
  .ghost-orb{width:3.2rem;height:3.2rem;border-radius:999px;border:1px dashed var(--primary,#c9a36a);background:radial-gradient(circle at 30% 30%,color-mix(in srgb,var(--primary,#c9a36a) 55%,transparent),transparent 70%);box-shadow:0 0 24px color-mix(in srgb,var(--primary,#c9a36a) 35%,transparent);display:grid;place-items:center;padding:0}
  .ghost-orb-core{width:0.85rem;height:0.85rem;border-radius:999px;background:var(--primary,#c9a36a);animation:ghost-float 3.2s ease-in-out infinite}
  @keyframes ghost-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
  .ghost-panel{position:absolute;bottom:3.8rem;right:0;width:min(22rem,92vw);max-height:70vh;display:flex;flex-direction:column;background:color-mix(in srgb,var(--bg,#07080c) 92%,#000);border:1px solid var(--border,#2a2c38);border-radius:1rem;box-shadow:0 20px 50px rgba(0,0,0,.45);overflow:hidden}
  .ghost-head{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-bottom:1px solid var(--border,#2a2c38);font-size:.85rem}
  .ghost-head .btn-ghost{margin-left:auto}
  .ghost-log{flex:1;overflow:auto;padding:.75rem 1rem;display:flex;flex-direction:column;gap:.65rem;min-height:8rem}
  .ghost-msg{font-size:.9rem;line-height:1.4;white-space:pre-wrap}
  .ghost-msg.user{opacity:.85;text-align:right}
  .ghost-actions{display:flex;flex-wrap:wrap;gap:.35rem;margin-top:.35rem}
  .ghost-form{display:flex;gap:.4rem;padding:.65rem;border-top:1px solid var(--border,#2a2c38)}
  .ghost-form input{flex:1;background:transparent;border:1px solid var(--border,#2a2c38);border-radius:.5rem;color:inherit;padding:.5rem .65rem}
  [x-cloak]{display:none!important}
</style>
<script>
function ghostOrb(slug){
  return {
    open:false, boot:false, loading:false, input:'', profile:'', messages:[],
    async hello(){
      try{
        const r = await fetch('/n/'+slug+'/ghost');
        const j = await r.json();
        this.profile = j.profile || '';
        this.messages.push({role:'assistant', content:j.reply, actions:j.actions||[]});
      }catch(e){ this.messages.push({role:'assistant', content:'Ghost indisponible un instant.'}); }
    },
    async send(){
      const text = (this.input||'').trim(); if(!text||this.loading) return;
      this.messages.push({role:'user', content:text});
      this.input=''; this.loading=true;
      try{
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const hist = this.messages.filter(m=>m.role==='user'||m.role==='assistant').slice(-8).map(m=>({role:m.role, content:m.content}));
        const r = await fetch('/n/'+slug+'/ghost', {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'Accept':'application/json'},
          body: JSON.stringify({message:text, history:hist})
        });
        const j = await r.json();
        this.messages.push({role:'assistant', content:j.reply||'…', actions:j.actions||[]});
        this.profile = j.profile || this.profile;
      }catch(e){
        this.messages.push({role:'assistant', content:'Je n\'ai pas pu joindre le coffre. Réessayez.'});
      }
      this.loading=false;
      this.$nextTick(()=>{ const el=this.$refs.log; if(el) el.scrollTop=el.scrollHeight; });
    }
  }
}
</script>
@endisset
