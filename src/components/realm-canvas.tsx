import { useEffect, useRef } from "react";

type Marker = { title: string; image: string };

export function RealmCanvas({ markers, ocean = true }: { markers: Marker[]; ocean?: boolean }) {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    let dead = false;
    let renderer: { dispose: () => void; forceContextLoss?: () => void } | null = null;
    let raf = 0;

    void (async () => {
      const THREE = await import("three");
      if (dead || !el) return;
      const w = el.clientWidth || 800;
      const h = el.clientHeight || 480;
      const scene = new THREE.Scene();
      scene.fog = new THREE.Fog(ocean ? 0x0a1c24 : 0x07080c, 8, 42);
      scene.background = new THREE.Color(ocean ? 0x07141c : 0x08090e);
      const camera = new THREE.PerspectiveCamera(50, w / h, 0.1, 80);
      camera.position.set(0, 6.5, 14);
      const rend = new THREE.WebGLRenderer({ antialias: true, alpha: false });
      rend.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      rend.setSize(w, h);
      el.appendChild(rend.domElement);
      renderer = rend;

      const hemi = new THREE.HemisphereLight(0xffe6b8, 0x123040, 1.1);
      scene.add(hemi);
      const sun = new THREE.DirectionalLight(0xffd9a0, 1.4);
      sun.position.set(-8, 12, 6);
      scene.add(sun);

      const sea = new THREE.Mesh(
        new THREE.CircleGeometry(28, 64),
        new THREE.MeshStandardMaterial({
          color: ocean ? 0x1a4a5c : 0x1a1d28,
          roughness: 0.35,
          metalness: 0.1,
        }),
      );
      sea.rotation.x = -Math.PI / 2;
      scene.add(sea);

      const loader = new THREE.TextureLoader();
      markers.slice(0, 8).forEach((m, i) => {
        const angle = (i / Math.max(markers.length, 1)) * Math.PI * 2;
        const r = 4.2 + (i % 3);
        const x = Math.cos(angle) * r;
        const z = Math.sin(angle) * r;
        const isle = new THREE.Mesh(
          new THREE.CylinderGeometry(0.7, 1.1, 0.5, 8),
          new THREE.MeshStandardMaterial({ color: 0xc9a36a }),
        );
        isle.position.set(x, 0.25, z);
        scene.add(isle);
        const tex = loader.load(m.image);
        const mat = new THREE.SpriteMaterial({ map: tex });
        const spr = new THREE.Sprite(mat);
        spr.position.set(x, 1.8, z);
        spr.scale.set(1.6, 2.2, 1);
        scene.add(spr);
      });

      const onResize = () => {
        if (!el) return;
        const ww = el.clientWidth;
        const hh = el.clientHeight;
        camera.aspect = ww / hh;
        camera.updateProjectionMatrix();
        rend.setSize(ww, hh);
      };
      window.addEventListener("resize", onResize);

      let t = 0;
      const loop = () => {
        if (dead) return;
        t += 0.004;
        camera.position.x = Math.sin(t) * 14;
        camera.position.z = Math.cos(t) * 14;
        camera.lookAt(0, 0.6, 0);
        rend.render(scene, camera);
        raf = requestAnimationFrame(loop);
      };
      loop();

      (el as HTMLDivElement & { _cleanup?: () => void })._cleanup = () => {
        window.removeEventListener("resize", onResize);
      };
    })();

    return () => {
      dead = true;
      cancelAnimationFrame(raf);
      const n = el;
      const extra = (n as HTMLDivElement & { _cleanup?: () => void })._cleanup;
      extra?.();
      const canvas = n.querySelector("canvas");
      canvas?.remove();
      renderer?.dispose();
    };
  }, [markers, ocean]);

  return <div ref={ref} className="h-[62vh] w-full overflow-hidden rounded-3xl bg-[#07141c]" />;
}
