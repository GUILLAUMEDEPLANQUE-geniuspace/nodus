/**
 * God Canvas — peau 3D. Les tools LLM / CCK / Drive écrivent la DB.
 * Drop fichier → attach_media. Palette CCK → add_cck_field.
 */
(function () {
  const GOLD = 0xc9a36a;
  const INK = 0x05060a;
  const slug = window.GP_SLUG;
  const csrf = window.GP_CSRF;
  const container = document.getElementById("webgl");
  const scene = new THREE.Scene();
  scene.fog = new THREE.FogExp2(INK, 0.0018);
  const camera = new THREE.PerspectiveCamera(55, innerWidth / innerHeight, 0.1, 2500);
  camera.position.set(40, 70, 180);
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  container.appendChild(renderer.domElement);
  const controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.maxDistance = 700;
  scene.add(new THREE.AmbientLight(0xf3eadc, 0.28));
  const sun = new THREE.PointLight(GOLD, 2.2, 900);
  sun.position.set(40, 120, 40);
  scene.add(sun);
  const rim = new THREE.PointLight(0x8aa4c8, 0.6, 700);
  rim.position.set(-80, 20, -40);
  scene.add(rim);
  const starsGeo = new THREE.BufferGeometry();
  const pos = new Float32Array(12000);
  for (let i = 0; i < pos.length; i++) pos[i] = (Math.random() - 0.5) * 1400;
  starsGeo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
  const starMesh = new THREE.Points(starsGeo, new THREE.PointsMaterial({ size: 1.1, color: GOLD, opacity: 0.5, transparent: true }));
  scene.add(starMesh);

  const meshes = [];
  const lines = [];
  let linkMode = false;
  let linkFrom = null;
  let selected = null;

  function geomFor(kind) {
    if (kind === "job") return new THREE.BoxGeometry(9, 16, 9);
    if (kind === "crypto") return new THREE.OctahedronGeometry(10, 0);
    if (kind === "video") return new THREE.SphereGeometry(7, 28, 28);
    if (kind === "character") return new THREE.ConeGeometry(7, 14, 6);
    if (kind === "shop") return new THREE.TorusGeometry(7, 2.2, 10, 20);
    return new THREE.SphereGeometry(22, 64, 64);
  }
  function colorFor(kind) {
    if (kind === "job") return 0x8aa4c8;
    if (kind === "crypto") return 0xe0b15a;
    if (kind === "video") return 0xb07cc8;
    if (kind === "character") return GOLD;
    if (kind === "shop") return 0xd4c4a0;
    return 0x1c2420;
  }
  function clearScene() {
    meshes.splice(0).forEach((m) => scene.remove(m));
    lines.splice(0).forEach((l) => scene.remove(l));
  }
  function spawn(state) {
    if (!state || !state.nodes) return;
    clearScene();
    const byId = {};
    state.nodes.forEach(function (n) {
      const mat = new THREE.MeshStandardMaterial({
        color: colorFor(n.kind),
        emissive: GOLD,
        emissiveIntensity: n.kind === "core" ? 0.35 : 0.14,
        roughness: 0.28,
        metalness: 0.45,
        wireframe: n.kind === "core",
      });
      const mesh = new THREE.Mesh(geomFor(n.kind), mat);
      mesh.position.set(n.x, n.y, n.z);
      mesh.userData = n;
      scene.add(mesh);
      meshes.push(mesh);
      byId[n.id] = mesh;
      if (n.kind === "core") {
        const ring = new THREE.Mesh(
          new THREE.RingGeometry(34, 34.7, 80),
          new THREE.MeshBasicMaterial({ color: GOLD, side: THREE.DoubleSide, transparent: true, opacity: 0.55 })
        );
        ring.rotation.x = Math.PI / 2;
        mesh.add(ring);
      }
    });
    (state.edges || []).forEach(function (e) {
      const a = byId[e.from_id];
      const b = byId[e.to_id];
      if (!a || !b) return;
      const geo = new THREE.BufferGeometry().setFromPoints([a.position.clone(), b.position.clone()]);
      const line = new THREE.Line(geo, new THREE.LineBasicMaterial({ color: GOLD, transparent: true, opacity: 0.4 }));
      line.userData = { from: e.from_id, to: e.to_id };
      scene.add(line);
      lines.push(line);
    });
  }
  function toast(t) {
    const el = document.getElementById("gp-msg");
    if (!el) return;
    el.textContent = t;
    el.style.display = "block";
    clearTimeout(el._t);
    el._t = setTimeout(function () {
      el.style.display = "none";
    }, 2800);
  }
  function api(path, body) {
    return fetch(path, {
      method: body ? "POST" : "GET",
      headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, Accept: "application/json" },
      body: body ? JSON.stringify(body) : undefined,
    }).then(function (r) {
      return r.json().then(function (j) {
        if (!r.ok) {
          toast(j.message || "Action refusée — cliquez Créateur pour sculpter.");
          throw j;
        }
        return j;
      });
    });
  }
  function coreOf(state) {
    const list = (state && state.nodes) || meshes.map((m) => m.userData);
    return list.find((n) => n.kind === "core") || list[0] || selected;
  }
  function addCck(type, name) {
    const target = selected || coreOf();
    if (!target || !target.id) {
      toast("Pas de noyau — Commencer vide d’abord.");
      return;
    }
    toast("Ajout « " + name + " »…");
    api("/builder/" + slug + "/sync", {
      id: target.id,
      field_type: type,
      field_name: name,
      field_value: "",
    }).then(function (st) {
      spawn(st);
      const n = (st.nodes || []).find((x) => x.id === target.id) || coreOf(st);
      if (n) openPanel(n);
      toast("Champ « " + name + " » collé.");
    });
  }
  function refresh() {
    return api("/builder/" + slug + "/state").then(spawn);
  }
  const panel = document.getElementById("panel");
  function openPanel(n) {
    selected = n;
    document.getElementById("ptitle").textContent = (n.title || "") + " · " + n.kind;
    document.getElementById("pid").value = n.id;
    document.getElementById("ptit").value = n.title || "";
    document.getElementById("psum").value = "";
    const box = document.getElementById("pfields");
    const fs = n.fields || [];
    box.innerHTML = fs.length
      ? fs.map((f) => "<p>" + f.type + " · <strong>" + f.name + "</strong> " + (f.value || "") + "</p>").join("")
      : "<p>Aucun champ — palette à gauche ou formulaire.</p>";
    panel.classList.add("on");
  }
  const raycaster = new THREE.Raycaster();
  const mouse = new THREE.Vector2();
  window.addEventListener("click", function (ev) {
    if (ev.target.closest(".gdock") || ev.target.closest(".slide") || ev.target.closest(".bang") || ev.target.closest("header") || ev.target.closest(".palette")) return;
    mouse.x = (ev.clientX / innerWidth) * 2 - 1;
    mouse.y = -(ev.clientY / innerHeight) * 2 + 1;
    raycaster.setFromCamera(mouse, camera);
    const hit = raycaster.intersectObjects(meshes)[0];
    if (!hit) return;
    const n = hit.object.userData;
    if (linkMode) {
      if (!linkFrom) {
        linkFrom = n.id;
        return;
      }
      api("/builder/" + slug + "/link", { from: linkFrom, to: n.id }).then(spawn);
      linkFrom = null;
      return;
    }
    const p = hit.object.position.clone();
    camera.position.set(p.x + 42, p.y + 22, p.z + 42);
    controls.target.copy(p);
    openPanel(n);
  });

  function revealHud() {
    const d = document.getElementById("dock");
    const pal = document.getElementById("palette");
    if (d) d.style.opacity = "1";
    if (pal) {
      pal.style.opacity = "1";
      pal.style.pointerEvents = "auto";
    }
    const b = document.getElementById("bang");
    if (b) b.style.display = "none";
  }

  document.getElementById("pclose").onclick = () => panel.classList.remove("on");
  document.getElementById("psync").onsubmit = function (e) {
    e.preventDefault();
    api("/builder/" + slug + "/sync", Object.fromEntries(new FormData(e.target))).then(function (st) {
      spawn(st);
      panel.classList.remove("on");
    });
  };
  document.querySelectorAll("[data-add]").forEach(function (btn) {
    btn.onclick = function (e) {
      e.stopPropagation();
      toast("Nœud…");
      api("/builder/" + slug + "/add", { type: btn.getAttribute("data-add") }).then(function (st) {
        spawn(st);
        toast("Nœud ajouté.");
      });
    };
  });
  document.addEventListener("click", function (e) {
    const btn = e.target.closest("[data-cck]");
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    addCck(btn.getAttribute("data-cck"), (btn.textContent || "").trim());
  });
  const lm = document.getElementById("link-mode");
  if (lm)
    lm.onclick = function () {
      linkMode = !linkMode;
      this.style.color = linkMode ? "#c9a36a" : "";
    };
  document.querySelectorAll(".bang .chip").forEach(function (c) {
    c.onclick = function () {
      document.getElementById("prompt").value = c.getAttribute("data-p");
    };
  });
  function compile() {
    const p = document.getElementById("prompt");
    return api("/builder/" + slug + "/compile", { prompt: p ? p.value : "" }).then(function (st) {
      revealHud();
      spawn(st);
    });
  }
  function propose() {
    const p = document.getElementById("prompt");
    return api("/builder/" + slug + "/propose", { prompt: p ? p.value : "" }).then(function (res) {
      const box = document.getElementById("ideas");
      if (!box) return;
      box.innerHTML = (res.suggestions || [])
        .map(function (s) {
          return '<button type="button" class="chip" data-sug-type="' + s.type + '" data-sug-title="' + s.title.replace(/"/g, "") + '">+ ' + s.title + "</button>";
        })
        .join("") || "<p class='muted'>Rien à extraire — écris une liste (Luffy, Zoro, carte) ou pose les briques à la main.</p>";
      box.querySelectorAll("[data-sug-title]").forEach(function (b) {
        b.onclick = function () {
          api("/builder/" + slug + "/add", { type: b.getAttribute("data-sug-type"), title: b.getAttribute("data-sug-title") }).then(function (st) {
            revealHud();
            spawn(st);
          });
        };
      });
    });
  }
  const go = document.getElementById("go-bang");
  if (go) go.onclick = compile;
  const gp = document.getElementById("go-propose");
  if (gp) gp.onclick = propose;
  const rc = document.getElementById("recompile");
  if (rc) rc.onclick = propose;
  const adv = document.getElementById("adv");
  if (adv)
    adv.onclick = function () {
      document.querySelectorAll(".pro").forEach(function (el) {
        el.style.display = el.style.display === "none" ? "" : "none";
      });
      this.textContent = this.textContent.indexOf("simple") >= 0 ? "Mode avancé" : "Mode simple";
    };
  const seoBtn = document.getElementById("seo-go");
  if (seoBtn)
    seoBtn.onclick = function () {
      api("/builder/" + slug + "/seo").then(function (r) {
        alert("SEO : " + (r.title || JSON.stringify(r)));
      });
    };

  function sendFile(file) {
    const fd = new FormData();
    fd.append("file", file);
    if (selected) fd.append("node_id", selected.id);
    fetch("/builder/" + slug + "/media", { method: "POST", headers: { "X-CSRF-TOKEN": csrf, Accept: "application/json" }, body: fd })
      .then((r) => r.json())
      .then(spawn);
  }
  document.body.addEventListener("dragover", function (e) {
    e.preventDefault();
    document.body.classList.add("dragging");
  });
  document.body.addEventListener("dragleave", function () {
    document.body.classList.remove("dragging");
  });
  document.body.addEventListener("drop", function (e) {
    e.preventDefault();
    document.body.classList.remove("dragging");
    if (e.dataTransfer.files[0]) sendFile(e.dataTransfer.files[0]);
  });
  const imp = document.getElementById("imp");
  if (imp) imp.onchange = function () {
    if (imp.files[0]) sendFile(imp.files[0]);
  };

  function tick() {
    requestAnimationFrame(tick);
    starMesh.rotation.y += 0.00035;
    meshes.forEach(function (m) {
      const n = m.userData;
      if (n.kind === "core") {
        m.rotation.y += 0.003;
        return;
      }
      if (n.radius > 0) {
        n.angle += n.speed || 0.002;
        m.position.x = Math.cos(n.angle) * n.radius;
        m.position.z = Math.sin(n.angle) * n.radius;
      }
    });
    lines.forEach(function (l) {
      const a = meshes.find((m) => m.userData.id === l.userData.from);
      const b = meshes.find((m) => m.userData.id === l.userData.to);
      if (!a || !b) return;
      const arr = l.geometry.attributes.position.array;
      arr[0] = a.position.x;
      arr[1] = a.position.y;
      arr[2] = a.position.z;
      arr[3] = b.position.x;
      arr[4] = b.position.y;
      arr[5] = b.position.z;
      l.geometry.attributes.position.needsUpdate = true;
    });
    controls.update();
    renderer.render(scene, camera);
  }
  tick();
  addEventListener("resize", function () {
    camera.aspect = innerWidth / innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(innerWidth, innerHeight);
  });
  if (!window.GP_FRESH) {
    revealHud();
    refresh();
  }
})();
