/**
 * God Canvas — Three.js r128.
 * Tokens or/encre (0xc9a36a). Chaque mesh = nœud Eloquent. Les lignes = edges.
 */
(function () {
  const GOLD = 0xc9a36a;
  const INK = 0x07080c;
  const slug = window.GP_SLUG;
  const csrf = window.GP_CSRF;
  const container = document.getElementById("webgl");
  const scene = new THREE.Scene();
  scene.fog = new THREE.FogExp2(INK, 0.002);
  const camera = new THREE.PerspectiveCamera(60, innerWidth / innerHeight, 0.1, 2000);
  camera.position.set(0, 50, 150);
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setSize(innerWidth, innerHeight);
  renderer.setPixelRatio(Math.min(devicePixelRatio, 2));
  container.appendChild(renderer.domElement);
  const controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  scene.add(new THREE.AmbientLight(0xffffff, 0.35));
  const lamp = new THREE.PointLight(GOLD, 1.4, 600);
  lamp.position.set(0, 80, 0);
  scene.add(lamp);
  const starsGeo = new THREE.BufferGeometry();
  const pos = new Float32Array(6000);
  for (let i = 0; i < pos.length; i++) pos[i] = (Math.random() - 0.5) * 900;
  starsGeo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
  const starMesh = new THREE.Points(starsGeo, new THREE.PointsMaterial({ size: 1.2, color: GOLD, opacity: 0.45, transparent: true }));
  scene.add(starMesh);

  const meshes = [];
  const lines = [];
  let linkMode = false;
  let linkFrom = null;

  function geomFor(kind) {
    if (kind === "job") return new THREE.BoxGeometry(8, 14, 8);
    if (kind === "crypto") return new THREE.OctahedronGeometry(8, 0);
    if (kind === "video") return new THREE.SphereGeometry(6, 24, 24);
    if (kind === "character") return new THREE.ConeGeometry(6, 12, 5);
    if (kind === "shop") return new THREE.TorusGeometry(6, 2, 8, 16);
    return new THREE.SphereGeometry(18, 48, 48);
  }
  function colorFor(kind) {
    if (kind === "job") return 0x8aa4c8;
    if (kind === "crypto") return 0xd4a24a;
    if (kind === "video") return 0xb07cc8;
    if (kind === "character") return GOLD;
    return 0x3d4a3a;
  }

  function clearScene() {
    meshes.splice(0).forEach((m) => scene.remove(m));
    lines.splice(0).forEach((l) => scene.remove(l));
  }

  function spawn(state) {
    clearScene();
    const byId = {};
    state.nodes.forEach(function (n) {
      const g = geomFor(n.kind);
      const mat = new THREE.MeshStandardMaterial({
        color: colorFor(n.kind),
        emissive: GOLD,
        emissiveIntensity: n.kind === "core" ? 0.25 : 0.12,
        roughness: 0.35,
        wireframe: n.kind === "core",
      });
      const mesh = new THREE.Mesh(g, mat);
      mesh.position.set(n.x, n.y, n.z);
      mesh.userData = n;
      scene.add(mesh);
      meshes.push(mesh);
      byId[n.id] = mesh;
      if (n.kind === "core") {
        const ring = new THREE.Mesh(
          new THREE.RingGeometry(28, 28.6, 64),
          new THREE.MeshBasicMaterial({ color: GOLD, side: THREE.DoubleSide, transparent: true, opacity: 0.45 })
        );
        ring.rotation.x = Math.PI / 2;
        mesh.add(ring);
      }
    });
    state.edges.forEach(function (e) {
      const a = byId[e.from_id];
      const b = byId[e.to_id];
      if (!a || !b) return;
      const geo = new THREE.BufferGeometry().setFromPoints([a.position.clone(), b.position.clone()]);
      const line = new THREE.Line(geo, new THREE.LineBasicMaterial({ color: GOLD, transparent: true, opacity: 0.35 }));
      line.userData = { from: e.from_id, to: e.to_id };
      scene.add(line);
      lines.push(line);
    });
  }

  function api(path, body) {
    return fetch(path, {
      method: body ? "POST" : "GET",
      headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrf, Accept: "application/json" },
      body: body ? JSON.stringify(body) : undefined,
    }).then(function (r) {
      return r.json();
    });
  }

  function refresh() {
    return api("/builder/" + slug + "/state").then(spawn);
  }

  const raycaster = new THREE.Raycaster();
  const mouse = new THREE.Vector2();
  const panel = document.getElementById("panel");
  function openPanel(n) {
    document.getElementById("ptitle").textContent = n.title + " · " + n.kind;
    document.getElementById("pid").value = n.id;
    document.getElementById("ptit").value = n.title;
    document.getElementById("psum").value = "";
    panel.classList.add("on");
  }
  window.addEventListener("click", function (ev) {
    if (ev.target.closest(".gdock") || ev.target.closest(".slide") || ev.target.closest(".bang") || ev.target.closest("header")) return;
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
    camera.position.set(p.x + 36, p.y + 18, p.z + 36);
    controls.target.copy(p);
    openPanel(n);
  });

  document.getElementById("pclose").onclick = function () {
    panel.classList.remove("on");
  };
  document.getElementById("psync").onsubmit = function (e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const body = Object.fromEntries(fd.entries());
    api("/builder/" + slug + "/sync", body).then(function () {
      panel.classList.remove("on");
      return refresh();
    });
  };
  document.querySelectorAll("[data-add]").forEach(function (btn) {
    btn.onclick = function () {
      api("/builder/" + slug + "/add", { type: btn.getAttribute("data-add") }).then(spawn);
    };
  });
  document.getElementById("link-mode").onclick = function () {
    linkMode = !linkMode;
    this.style.color = linkMode ? "#c9a36a" : "";
  };
  document.querySelectorAll(".bang .chip").forEach(function (c) {
    c.onclick = function () {
      document.getElementById("prompt").value = c.getAttribute("data-p");
    };
  });
  document.getElementById("go-bang").onclick = function () {
    api("/builder/" + slug + "/bang", { prompt: document.getElementById("prompt").value }).then(function (st) {
      document.getElementById("bang").style.display = "none";
      document.getElementById("dock").style.opacity = "1";
      document.getElementById("cross").style.opacity = "1";
      spawn(st);
    });
  };

  function tick() {
    requestAnimationFrame(tick);
    starMesh.rotation.y += 0.0004;
    meshes.forEach(function (m) {
      const n = m.userData;
      if (n.kind === "core") {
        m.rotation.y += 0.004;
        return;
      }
      if (n.radius > 0) {
        n.angle += n.speed || 0.003;
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
})();
