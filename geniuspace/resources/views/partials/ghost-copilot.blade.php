{{-- Ghost Studio : observe → plan → preview → apply → undo. Pas de SQL dans le LLM. --}}
<section class="ghost-copilot" x-data="ghostCopilot()" x-cloak>
  <div class="ghost-copilot__fiche">
    <p class="kicker">Composition · Offre industrie</p>
    <p class="muted" style="margin:0 0 .8rem">Cliquez entre les blocs : c’est le curseur Ghost. « Ici » s’ancre là.</p>
    <template x-for="(b, i) in blocks" :key="b.id">
      <div>
        <button type="button" class="ghost-cursor" :class="cursor.block===b.id && cursor.position==='before' && 'is-on'" @click="cursor={block:b.id, position:'before'}" x-show="i===0">Curseur Ghost</button>
        <article class="ghost-block" :class="cursor.block===b.id && 'is-on'">
          <p class="kicker" x-text="kind(b.type)"></p>
          <p class="font-display" style="font-size:1.2rem;margin:.1rem 0" x-text="b.label"></p>
          <p class="muted" x-show="b.value" x-text="b.value"></p>
        </article>
        <button type="button" class="ghost-cursor" :class="cursor.block===b.id && cursor.position==='after' && 'is-on'" @click="cursor={block:b.id, position:'after'}">ici</button>
      </div>
    </template>
  </div>
  <aside class="ghost-copilot__chat">
    <p class="kicker">Ghost</p>
    <div class="ghost-log">
      <template x-for="(m, i) in log" :key="i">
        <p :class="m.role==='you' ? 'you' : 'host'" x-text="m.body"></p>
      </template>
    </div>
    <div class="ghost-preview" x-show="pending && pending.ops && pending.ops.length && pending.status==='preview'">
      <p class="kicker">Ghost veut modifier</p>
      <template x-for="line in (pending.preview || [])" :key="line">
        <p x-text="line"></p>
      </template>
      <p class="muted" x-text="(pending.level||'')+' · '+(pending.autonomy==='confirm' ? 'confirmation exigée' : (pending.autonomy||''))"></p>
      <div class="rel" style="margin-top:.6rem">
        <button class="btn-line" type="button" @click="cancel()">Annuler</button>
        <button class="btn" type="button" @click="apply()">Appliquer</button>
      </div>
    </div>
    <div class="rel" x-show="pending && pending.citations && pending.ask" style="margin:.5rem 0">
      <template x-for="c in pending.citations" :key="c.id">
        <button class="chip" type="button" @click="pick(c.id)" x-text="c.label"></button>
      </template>
    </div>
    <div class="rel" style="margin:.6rem 0;flex-wrap:wrap">
      <template x-for="c in chips" :key="c">
        <button class="chip" type="button" @click="say(c)" x-text="c"></button>
      </template>
    </div>
    <form @submit.prevent="say(q); q=''">
      <input x-model="q" placeholder="Une phrase. Ghost observe d’abord." style="width:100%;margin:.3rem 0">
      <button class="btn" type="submit">Plan</button>
      <button class="btn-line" type="button" @click="undoLast()" :disabled="!lastId">Annuler la dernière</button>
    </form>
  </aside>
</section>
<script>
function ghostCopilot() {
  const slug = @json($node->slug);
  const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
  return {
    blocks: @json($blocks ?? []),
    cursor: { block: null, position: 'after' },
    pending: null,
    lastId: null,
    q: '',
    log: [{ role: 'host', body: 'Le LLM propose. Le moteur décide. Je lis la fiche, je prépare, je n’écris qu’après Appliquer.' }],
    chips: [
      'Ajoute un champ salaire après contrat',
      'Mets une photo de l’atelier ici',
      'Ajoute la playlist Ambient 01 sous la présentation',
      'Fais-moi une fiche pour cette offre industrielle',
      'Quels clients ont acheté le cel ?',
      'Lance une campagne pour les clients qui ont acheté le cel et n’ont pas le print. Propose-leur le print avec 15 % de réduction.',
      'Envoie le suivi de commande aux clients qui ont commandé hier.',
    ],
    kind(t) {
      return ({ text: 'Texte', field: 'Champ', image: 'Image', video: 'Vidéo', playlist: 'Playlist' })[t] || t;
    },
    async say(message) {
      if (!message) return;
      this.log.push({ role: 'you', body: message });
      const r = await fetch('/n/' + slug + '/ghost/plan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ message, editor_context: { selected_block: this.cursor.block, cursor: this.cursor } }),
      });
      const j = await r.json();
      this.pending = j.action;
      this.log.push({ role: 'host', body: (j.action.preview || []).join(' ') + (j.action.ask ? ' ' + j.action.ask : '') });
    },
    async apply() {
      if (!this.pending) return;
      const r = await fetch('/n/' + slug + '/ghost/apply', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ id: this.pending.id }),
      });
      const j = await r.json();
      if (j.blocks) this.blocks = j.blocks;
      this.lastId = j.action?.id;
      this.pending = null;
      this.log.push({ role: 'host', body: j.action?.result || 'Appliqué.' });
    },
    cancel() {
      this.pending = null;
      this.log.push({ role: 'host', body: 'Annulé. Rien n’est écrit.' });
    },
    async undoLast() {
      if (!this.lastId) return;
      const r = await fetch('/n/' + slug + '/ghost/undo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ id: this.lastId }),
      });
      const j = await r.json();
      if (j.blocks) this.blocks = j.blocks;
      this.lastId = null;
      this.log.push({ role: 'host', body: j.action?.result || 'Annulé.' });
    },
    pick(id) {
      this.say(id);
    },
  };
}
</script>
