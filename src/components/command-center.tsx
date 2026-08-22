/**
 * Command Center produit — 3 colonnes (vidéo | galerie RWA | chaudron).
 * Tokens Geniuspace (or/encre). Pas de cyan, pas de Cinzel.
 * Drag → chaudron = panier + énergie (drop perso). Crowd-goal = quête Node.
 * WebXR = preview CSS 3D (prod : navigator.xr).
 */
import { useEffect, useMemo, useRef, useState } from "react";
import { Link } from "@tanstack/react-router";
import {
  Flame,
  Gem,
  Link2,
  Pause,
  Play,
  ShoppingBag,
  Star,
  Users,
} from "lucide-react";
import { ShareBar, Stars } from "@/components/share-bar";
import type { AdSlot, CrowdGoal, GraphMedia, Neighbor, ShopProduct, VideoNews } from "@/lib/graph";
import { addToCart, checkoutCart } from "@/lib/platform-api";
import { ThemeToggle } from "@/lib/theme";

function parsePrice(label: string) {
  const n = Number(label.replace(/[^\d.,]/g, "").replace(",", "."));
  return Number.isFinite(n) ? n : 0;
}

export function CommandCenter({
  slug,
  products,
  media,
  ads,
  goal,
  neighbors,
  news,
}: {
  slug: string;
  products: ShopProduct[];
  media?: GraphMedia;
  ads: AdSlot[];
  goal: CrowdGoal | null;
  neighbors: Neighbor[];
  news: VideoNews[];
}) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const src = media?.url || "/media/atelier.mp4";
  const teaser = media?.teaserSec ?? 8;
  const gated = (media?.accessKind ?? "free") !== "free";
  const [t, setT] = useState(0);
  const [playing, setPlaying] = useState(false);
  const [granted, setGranted] = useState(!gated);
  const [ar, setAr] = useState(false);
  const [energy, setEnergy] = useState(0);
  const [lines, setLines] = useState<{ name: string; price: string }[]>([]);
  const [toasts, setToasts] = useState<string[]>([]);
  const [viewers] = useState(142);
  const [loot, setLoot] = useState(false);
  const ad = ads[0];
  const showAd = Boolean(ad && granted && t >= (ad?.startSec ?? 8) && t <= (ad?.endSec ?? 16));
  const wall = gated && !granted && t >= teaser;
  const total = useMemo(
    () => lines.reduce((s, l) => s + parsePrice(l.price), 0),
    [lines],
  );
  const fill = goal ? Math.min(100, Math.round((goal.current / goal.target) * 100)) : 0;

  useEffect(() => {
    const id = window.setInterval(() => {
      setToasts((cur) => [`Réservation · collectionneur ${Math.floor(Math.random() * 90)}`, ...cur].slice(0, 3));
    }, 14000);
    return () => window.clearInterval(id);
  }, []);

  function onTime() {
    const el = videoRef.current;
    if (!el) return;
    setT(el.currentTime);
    if (gated && !granted && el.currentTime >= teaser) {
      el.pause();
      setPlaying(false);
    }
  }

  function toggle() {
    const el = videoRef.current;
    if (!el) return;
    if (wall) return;
    if (el.paused) {
      void el.play();
      setPlaying(true);
    } else {
      el.pause();
      setPlaying(false);
    }
  }

  function dropIn(p: ShopProduct) {
    setLines((cur) => [...cur, { name: p.title, price: p.price }]);
    const next = Math.min(100, energy + (p.energy || 20));
    setEnergy(next);
    if (next >= 100) {
      setLoot(true);
      setEnergy(0);
    }
    void addToCart({ data: { productId: p.id } }).catch(() => {});
  }

  return (
    <div className="flex min-h-[calc(100dvh-3.5rem)] flex-col bg-bg">
      <header className="flex items-center justify-between border-b border-border px-4 py-3">
        <p className="font-display text-xl text-primary">Galerie & Masterclass</p>
        <div className="flex items-center gap-2 text-xs">
          <span className="inline-flex items-center gap-1 rounded-full border border-border px-3 py-1 text-muted">
            <Users className="size-3.5 text-primary" /> {viewers} collectionneurs
          </span>
          <ThemeToggle />
        </div>
      </header>
      {news[0] ? (
        <p className="overflow-hidden border-b border-border px-4 py-2 text-xs text-muted">
          <span className="mr-2 rounded-full bg-primary px-2 py-0.5 text-primary-fg">Holo-News</span>
          {news.map((n) => n.body).join(" · ")}
        </p>
      ) : null}

      <div className="grid min-h-0 flex-1 lg:grid-cols-[minmax(0,3fr)_minmax(0,4.5fr)_minmax(0,2.5fr)]">
        <section className="flex min-h-0 flex-col border-b border-border lg:border-r lg:border-b-0">
          <div className="relative bg-black">
            <video
              ref={videoRef}
              src={src}
              poster="/realms/actor-hero.jpg"
              className={`aspect-video w-full object-contain ${wall ? "blur-md" : ""}`}
              onTimeUpdate={onTime}
              onPlay={() => setPlaying(true)}
              onPause={() => setPlaying(false)}
              playsInline
            />
            {wall ? (
              <div className="absolute inset-0 grid place-items-center bg-bg/70">
                <button
                  type="button"
                  onClick={() => {
                    setGranted(true);
                    void videoRef.current?.play();
                  }}
                  className="h-11 rounded-full bg-primary px-4 text-sm text-primary-fg"
                >
                  Débloquer masterclass {media?.price}
                </button>
              </div>
            ) : null}
            {showAd && ad ? (
              <Link
                to="/n/$slug/p/$pid"
                params={{ slug, pid: products[0]?.id ?? "sp-at-1" }}
                search={{ view: "fiche" }}
                className="absolute top-3 right-3 flex items-center gap-2 rounded-xl border border-border bg-bg/90 p-2 text-xs"
              >
                <img src={ad.imageUrl} alt="" className="size-10 rounded-md object-cover" />
                <span>
                  Sponsorisé
                  <br />
                  {ad.title}
                </span>
              </Link>
            ) : null}
            <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-bg/90 to-transparent p-3">
              <div className="relative mb-2 h-1.5 overflow-hidden rounded-full bg-surface-2">
                <div
                  className="h-full bg-primary"
                  style={{ width: `${Math.min(100, (t / (videoRef.current?.duration || 22)) * 100)}%` }}
                />
              </div>
              <div className="flex items-center justify-between text-xs">
                <button type="button" onClick={toggle} className="grid size-10 place-items-center text-primary">
                  {playing ? <Pause className="size-4" /> : <Play className="size-4" />}
                </button>
                <span className="font-mono text-muted">
                  {Math.floor(t)}s · {media?.duration}
                </span>
              </div>
            </div>
          </div>
          <div className="flex flex-wrap gap-2 border-b border-border p-2">
            <span className="inline-flex items-center gap-1 rounded-full border border-border px-3 py-1 text-xs">
              <Star className="size-3 text-primary" /> {products[0]?.rating ?? "4.9"}
            </span>
            <span className="inline-flex items-center gap-1 rounded-full border border-border px-3 py-1 text-xs text-muted">
              {neighbors.length} nœuds liés
            </span>
          </div>
          <div className="flex-1 space-y-2 overflow-y-auto p-3">
            <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Chat communauté</p>
            <p className="rounded-xl bg-surface p-3 text-sm">Cette pièce RWA doit rester dans le Node, pas sur une marketplace plate.</p>
          </div>
        </section>

        <section className="relative flex flex-wrap items-center justify-center gap-4 overflow-hidden p-6">
          <div className="holo-floor pointer-events-none absolute inset-x-[-20%] bottom-[-30%] h-[55%]" />
          {products.map((p) => (
            <article
              key={p.id}
              draggable
              onDragStart={(e) => e.dataTransfer.setData("text/product", p.id)}
              className="relative w-[240px] cursor-grab rounded-2xl border border-border bg-bg/80 p-3 backdrop-blur-md"
            >
              {p.stock?.toLowerCase().includes("unique") || p.rwa ? (
                <span className="absolute -top-2 -right-2 rounded-full bg-primary px-2 py-0.5 text-[10px] font-medium text-primary-fg">
                  Pièce unique
                </span>
              ) : null}
              {p.rwa ? (
                <span className="absolute top-3 left-3 inline-flex items-center gap-1 rounded-md border border-border bg-bg/80 px-2 py-0.5 text-[10px] text-primary">
                  <Link2 className="size-3" /> RWA
                </span>
              ) : null}
              <img src={p.imageUrl || "/realms/actor-hero.jpg"} alt="" className="mb-2 h-40 w-full rounded-xl object-cover" />
              <button
                type="button"
                onClick={() => setAr(true)}
                className="mb-2 h-9 w-full rounded-full border border-border text-xs"
              >
                Voir en AR
              </button>
              <Stars rating={p.rating} votes={p.votes} />
              <h2 className="font-display text-xl">{p.title}</h2>
              <p className="font-mono text-lg text-primary">{p.price}</p>
              <p className="mt-2 text-[11px] text-muted">Glisser au chaudron</p>
            </article>
          ))}
          <div className="pointer-events-none absolute bottom-4 left-4 space-y-2">
            {toasts.map((t) => (
              <p key={t} className="pointer-events-auto rounded-xl border border-border bg-bg/90 px-3 py-2 text-xs">
                {t}
              </p>
            ))}
          </div>
        </section>

        <aside className="flex flex-col gap-4 border-t border-border p-4 lg:border-t-0 lg:border-l">
          {goal ? (
            <div className="rounded-2xl border border-border p-3">
              <p className="flex justify-between text-[11px] tracking-[0.14em] text-primary uppercase">
                Quête globale
                <span>
                  {goal.current} / {goal.target} €
                </span>
              </p>
              <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-2">
                <div className="h-full bg-primary" style={{ width: `${fill}%` }} />
              </div>
              <p className="mt-2 text-xs text-muted">{goal.reward}</p>
            </div>
          ) : null}
          <div>
            <p className="mb-1 flex justify-between font-mono text-xs text-primary">
              Énergie (drop perso) <span>{energy} / 100</span>
            </p>
            <div className="h-2 overflow-hidden rounded-full bg-surface-2">
              <div className="h-full bg-primary" style={{ width: `${energy}%` }} />
            </div>
          </div>
          <div
            onDragOver={(e) => e.preventDefault()}
            onDrop={(e) => {
              e.preventDefault();
              const id = e.dataTransfer.getData("text/product");
              const p = products.find((x) => x.id === id);
              if (p) dropIn(p);
            }}
            className="grid min-h-36 place-items-center rounded-2xl border border-dashed border-primary/40"
          >
            <Flame className="size-8 text-primary" />
            <p className="text-xs text-muted">Chaudron — déposez l'œuvre</p>
            <ul className="w-full space-y-1 px-3 text-xs">
              {lines.map((l, i) => (
                <li key={`${l.name}-${i}`} className="flex justify-between">
                  <span>{l.name}</span>
                  <span className="text-primary">{l.price}</span>
                </li>
              ))}
            </ul>
          </div>
          <p className="text-right font-mono text-2xl">{total.toFixed(2)} €</p>
          <button
            type="button"
            onClick={() => void checkoutCart({ data: { provider: "card" } }).catch(() => {})}
            className="h-12 rounded-full bg-primary text-sm font-medium text-primary-fg"
          >
            <ShoppingBag className="mr-2 inline size-4" /> Acquérir
          </button>
          <ShareBar title={products[0]?.title ?? slug} path={`/n/${slug}/p/${products[0]?.id ?? ""}`} text="RWA" />
        </aside>
      </div>

      {ar ? (
        <div className="fixed inset-0 z-50 grid place-items-center bg-bg/90 p-6">
          <div className="ar-wall relative h-[60vh] w-full max-w-lg">
            <img
              src={products[0]?.imageUrl || "/realms/actor-hero.jpg"}
              alt=""
              className="absolute top-[18%] left-1/2 h-[42%] w-[48%] -translate-x-1/2 object-cover shadow-[var(--shadow-border)]"
            />
          </div>
          <button type="button" onClick={() => setAr(false)} className="mt-4 h-11 rounded-full border border-border px-4 text-sm">
            Fermer l'AR
          </button>
        </div>
      ) : null}
      {loot ? (
        <div className="fixed inset-0 z-50 grid place-items-center bg-bg/90">
          <div className="rounded-3xl border border-border bg-surface p-8 text-center">
            <Gem className="mx-auto size-10 text-primary" />
            <h3 className="mt-3 font-display text-3xl">Énergie atteinte</h3>
            <p className="mt-2 text-sm text-muted">Wallpaper HD ajouté au Drive.</p>
            <button type="button" onClick={() => setLoot(false)} className="mt-4 h-11 rounded-full bg-primary px-4 text-sm text-primary-fg">
              Continuer
            </button>
          </div>
        </div>
      ) : null}
    </div>
  );
}

export function ProductJsonLd({ product, slug }: { product: ShopProduct; slug: string }) {
  const json = {
    "@context": "https://schema.org",
    "@graph": [
      {
        "@type": product.rwa ? "VisualArtwork" : "Product",
        name: product.title,
        description: product.summary,
        image: product.imageUrl,
        offers: {
          "@type": "Offer",
          priceCurrency: "EUR",
          price: product.price.replace(/[^\d.,]/g, "") || "0",
          availability: "https://schema.org/InStock",
        },
        url: `/n/${slug}/p/${product.id}`,
      },
    ],
  };
  return <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(json) }} />;
}

