{{-- Cadre omni : la VOD s’efface, le labo / mixer / tableau prend la place. --}}
<div class="omni-frame" x-show="omniNow" x-cloak>
  <p class="kicker" x-text="omniNow && omniNow.kind"></p>
  <h2 class="font-display" x-text="omniNow && omniNow.title"></h2>
  <p class="muted" x-text="omniNow && omniNow.body"></p>

  <div x-show="omniNow && omniNow.kind==='labo'" style="margin-top:.8rem;display:grid;gap:.5rem;justify-items:center">
    <textarea x-model="omniNote" rows="3" placeholder="Colle l’erreur." style="width:min(22rem,90%)"></textarea>
    <button class="btn" type="button" @click="hold()">Tenir l’exo</button>
  </div>

  <div class="omni-mixer" x-show="omniNow && omniNow.kind==='mixer'">
    <label>Kick <input type="range" min="0" max="100" value="70"></label>
    <label>Bass <input type="range" min="0" max="100" value="55"></label>
    <label>Vocal <input type="range" min="0" max="100" value="80"></label>
    <label>Fx <input type="range" min="0" max="100" value="30"></label>
  </div>

  <div class="omni-pitch" x-show="omniNow && omniNow.kind==='tactique'" aria-label="Tableau tactique">
    <button class="omni-dot" type="button" style="left:42%;top:18%" @click="hold('N°9')">9</button>
    <button class="omni-dot" type="button" style="left:58%;top:32%" @click="hold('N°10')">10</button>
    <button class="omni-dot" type="button" style="left:30%;top:48%" @click="hold('Ailier')">7</button>
  </div>

  <div x-show="omniNow && omniNow.kind==='route'" style="margin-top:.8rem">
    <input x-model="omniNote" placeholder="Fenêtre boss (0:42)" style="width:12rem">
    <button class="btn" type="button" @click="hold()">Tenir la route</button>
  </div>

  <p class="muted" x-show="preuve" x-text="preuve && preuve.quoi" style="margin-top:.6rem"></p>
  <div class="rel" style="margin-top:1rem;justify-content:center">
    <button class="btn-line" type="button" @click="resume()">Reprendre le film</button>
  </div>
</div>
