/**
 * Geniuspace Image Studio — éditeur embarqué (crop, filtres, texte, pinceau, undo).
 * Utilisé pour produits, covers forum, héros d'univers, Drive.
 * Pas de CDN obligatoire : canvas natif.
 */
(function () {
  const root = document.getElementById("img-studio");
  if (!root) return;
  const canvas = document.getElementById("is-canvas");
  const ctx = canvas.getContext("2d");
  const srcInput = document.getElementById("is-src");
  const fileInput = document.getElementById("is-file");
  const form = document.getElementById("is-save");
  const dataField = document.getElementById("is-data");
  let img = new Image();
  let angle = 0;
  let flipH = 1;
  let flipV = 1;
  let bright = 100;
  let contrast = 100;
  let sat = 100;
  let gray = false;
  let sepia = false;
  let brush = false;
  let drawing = false;
  let strokes = [];
  let texts = [];
  let crop = null;
  const undo = [];

  function snapshot() {
    undo.push(canvas.toDataURL("image/png"));
    if (undo.length > 20) undo.shift();
  }
  function render() {
    const w = img.naturalWidth || 800;
    const h = img.naturalHeight || 450;
    canvas.width = w;
    canvas.height = h;
    ctx.save();
    ctx.filter = `brightness(${bright}%) contrast(${contrast}%) saturate(${sat}%) grayscale(${gray ? 1 : 0}) sepia(${sepia ? 1 : 0})`;
    ctx.translate(w / 2, h / 2);
    ctx.rotate((angle * Math.PI) / 180);
    ctx.scale(flipH, flipV);
    ctx.drawImage(img, -w / 2, -h / 2, w, h);
    ctx.restore();
    ctx.filter = "none";
    strokes.forEach(function (s) {
      ctx.strokeStyle = s.color;
      ctx.lineWidth = s.size;
      ctx.lineCap = "round";
      ctx.beginPath();
      s.pts.forEach(function (p, i) {
        if (i === 0) ctx.moveTo(p.x, p.y);
        else ctx.lineTo(p.x, p.y);
      });
      ctx.stroke();
    });
    texts.forEach(function (t) {
      ctx.fillStyle = t.color;
      ctx.font = t.size + "px Georgia";
      ctx.fillText(t.text, t.x, t.y);
    });
    if (crop) {
      ctx.fillStyle = "rgba(0,0,0,0.35)";
      ctx.fillRect(0, 0, w, h);
      ctx.clearRect(crop.x, crop.y, crop.w, crop.h);
      ctx.strokeStyle = "#c9a36a";
      ctx.strokeRect(crop.x, crop.y, crop.w, crop.h);
    }
  }
  function loadUrl(url) {
    img = new Image();
    img.crossOrigin = "anonymous";
    img.onload = function () {
      angle = 0;
      strokes = [];
      texts = [];
      crop = null;
      render();
    };
    img.src = url;
  }
  if (srcInput && srcInput.value) loadUrl(srcInput.value);
  fileInput.addEventListener("change", function () {
    const f = fileInput.files[0];
    if (!f) return;
    loadUrl(URL.createObjectURL(f));
  });
  canvas.addEventListener("mousedown", function (e) {
    const r = canvas.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * canvas.width;
    const y = ((e.clientY - r.top) / r.height) * canvas.height;
    if (brush) {
      snapshot();
      drawing = true;
      strokes.push({ color: document.getElementById("is-color").value, size: 6, pts: [{ x: x, y: y }] });
    }
  });
  canvas.addEventListener("mousemove", function (e) {
    if (!drawing) return;
    const r = canvas.getBoundingClientRect();
    const x = ((e.clientX - r.left) / r.width) * canvas.width;
    const y = ((e.clientY - r.top) / r.height) * canvas.height;
    strokes[strokes.length - 1].pts.push({ x: x, y: y });
    render();
  });
  window.addEventListener("mouseup", function () {
    drawing = false;
  });
  function bind(id, fn) {
    const el = document.getElementById(id);
    if (el) el.addEventListener(el.tagName === "INPUT" && el.type === "range" ? "input" : "click", fn);
  }
  bind("is-rot", function () {
    snapshot();
    angle = (angle + 90) % 360;
    render();
  });
  bind("is-flip", function () {
    snapshot();
    flipH *= -1;
    render();
  });
  bind("is-gray", function () {
    snapshot();
    gray = !gray;
    render();
  });
  bind("is-sepia", function () {
    snapshot();
    sepia = !sepia;
    render();
  });
  bind("is-brush", function () {
    brush = !brush;
    this.classList.toggle("active");
  });
  bind("is-bright", function () {
    bright = this.value;
    render();
  });
  bind("is-contrast", function () {
    contrast = this.value;
    render();
  });
  bind("is-sat", function () {
    sat = this.value;
    render();
  });
  bind("is-text", function () {
    const t = prompt("Texte à poser");
    if (!t) return;
    snapshot();
    texts.push({ text: t, x: 40, y: 80, size: 48, color: document.getElementById("is-color").value });
    render();
  });
  bind("is-crop", function () {
    snapshot();
    crop = { x: canvas.width * 0.1, y: canvas.height * 0.1, w: canvas.width * 0.8, h: canvas.height * 0.8 };
    render();
  });
  bind("is-apply-crop", function () {
    if (!crop) return;
    snapshot();
    const tmp = document.createElement("canvas");
    tmp.width = crop.w;
    tmp.height = crop.h;
    tmp.getContext("2d").drawImage(canvas, crop.x, crop.y, crop.w, crop.h, 0, 0, crop.w, crop.h);
    img = new Image();
    img.onload = function () {
      crop = null;
      strokes = [];
      texts = [];
      render();
    };
    img.src = tmp.toDataURL();
  });
  bind("is-undo", function () {
    const last = undo.pop();
    if (!last) return;
    img = new Image();
    img.onload = function () {
      strokes = [];
      texts = [];
      crop = null;
      render();
    };
    img.src = last;
  });
  bind("is-reset", function () {
    if (srcInput.value) loadUrl(srcInput.value);
  });
  form.addEventListener("submit", function () {
    dataField.value = canvas.toDataURL("image/jpeg", 0.92);
  });
})();
