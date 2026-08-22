{{-- Éditeur visuel de champs de fiche. Internement : table cck_fields. L’opérateur n’a pas à le savoir. --}}
@php
  $catalog = \App\Llm\CckCatalog::all();
  $simple = \App\Llm\CckCatalog::simple();
@endphp
<section class="cck" x-data='{ type: "text", pro: false, name: "", value: "", labels: @json(array_map(fn ($m) => $m["label"], $catalog)) }' style="margin:1.6rem 0 2.4rem">
  <h2 class="font-display">Champs de la fiche</h2>
  <p class="muted" style="max-width:40rem">Cliquez une brique, nommez, voyez l’aperçu, enregistrez. Ça s’affiche sur la fiche publique. Pas une colonne SQL par métier.</p>

  <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(16rem,20rem);gap:1.2rem;align-items:start;margin-top:1rem">
    <div>
      <div class="cck-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(7.5rem,1fr));gap:.5rem;max-width:42rem">
        @foreach($catalog as $k => $m)
          <button type="button"
            @click="type='{{ $k }}'; name = name || '{{ $m['label'] }}'"
            x-show="{{ empty($m['pro']) ? 'true' : 'pro' }}"
            :style="type==='{{ $k }}' ? 'border-color:var(--primary);background:color-mix(in srgb,var(--primary) 12%,transparent)' : ''"
            style="text-align:left;border:1px solid var(--border);background:var(--surface);border-radius:.7rem;padding:.7rem .75rem">
            <span style="display:block;font-size:.65rem;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)">{{ $m['g'] }}</span>
            <strong>{{ $m['label'] }}</strong>
          </button>
        @endforeach
      </div>
      <label class="muted" style="display:flex;gap:.4rem;align-items:center;margin:.7rem 0 0">
        <input type="checkbox" x-model="pro"> Mode avancé ({{ count($catalog) - count($simple) }} types de plus)
      </label>

      <form method="post" action="/n/{{ $node->slug }}/studio/cck" class="card" style="padding:1rem;max-width:36rem;margin-top:.8rem;display:grid;gap:.45rem">
        @csrf
        <input type="hidden" name="type" :value="type">
        <p class="kicker">Nouveau champ · <span x-text="labels[type] || type"></span></p>
        <input name="name" x-model="name" placeholder="Nom (ex. Salaire, Lieu, Matière)" required>
        <input name="value" x-model="value" placeholder="Valeur">
        <select name="target_kind">
          <option value="node">Sur l’univers</option>
          <option value="product">Sur un produit</option>
          <option value="thread">Sur un sujet</option>
          <option value="media">Sur une vidéo</option>
        </select>
        <button class="btn" type="submit">Poser sur la fiche</button>
      </form>
    </div>

    <aside class="card" style="padding:1rem;position:sticky;top:1rem">
      <p class="kicker">Aperçu de la fiche</p>
      <h3 class="font-display" style="margin:.2rem 0 .8rem;font-size:1.3rem">{{ $node->title }}</h3>
      @forelse($cck as $f)
        <div style="border-top:1px solid var(--border);padding:.55rem 0">
          <p class="kicker" style="margin:0">{{ $catalog[$f->type]['label'] ?? $f->type }}</p>
          <p style="margin:.15rem 0 0;font-weight:500">{{ $f->name }}</p>
          <p class="muted" style="margin:.15rem 0 0">{{ $f->value !== '' ? $f->value : '…' }}</p>
        </div>
      @empty
        <p class="muted">Aucun champ encore. L’aperçu se remplit ici.</p>
      @endforelse
      <div x-show="name" style="border-top:1px dashed var(--primary);padding:.55rem 0;margin-top:.2rem">
        <p class="kicker" style="margin:0;color:var(--primary)">Nouveau</p>
        <p style="margin:.15rem 0 0;font-weight:500" x-text="name"></p>
        <p class="muted" style="margin:.15rem 0 0" x-text="value || '…'"></p>
      </div>
    </aside>
  </div>

  <div style="display:grid;gap:.5rem;margin-top:1.4rem;max-width:42rem">
    <p class="kicker">Champs déjà posés</p>
    @forelse($cck as $f)
      <article class="card" style="padding:.85rem 1rem;display:grid;gap:.35rem">
        <div style="display:flex;justify-content:space-between;gap:.6rem;flex-wrap:wrap">
          <div>
            <p class="kicker" style="margin:0">{{ $catalog[$f->type]['label'] ?? $f->type }}</p>
            <h3 style="margin:.15rem 0 0;font-size:1.05rem">{{ $f->name }}</h3>
            <p class="muted" style="margin:.2rem 0 0">{{ $f->value !== '' ? $f->value : 'vide' }}</p>
          </div>
          <form method="post" action="/n/{{ $node->slug }}/studio/cck/{{ $f->id }}/delete" onsubmit="return confirm('Retirer ce champ ?')">
            @csrf
            <button class="btn-ghost" type="submit">Retirer</button>
          </form>
        </div>
        <form method="post" action="/n/{{ $node->slug }}/studio/cck/{{ $f->id }}" class="rel" style="display:flex;gap:.4rem;flex-wrap:wrap">
          @csrf
          <input name="name" value="{{ $f->name }}" style="width:9rem">
          <input name="value" value="{{ $f->value }}" style="flex:1;min-width:8rem">
          <button class="btn-line" type="submit">Sauver</button>
        </form>
      </article>
    @empty
      <p class="muted">Aucun champ pour l’instant. Cliquez une brique ci-dessus.</p>
    @endforelse
  </div>
</section>
