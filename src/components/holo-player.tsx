/**
 * Cockpit vidéo NODUS — formation / jeu / boutique / entretien.
 * Paywall client = UX. Les URLs locked ne sont jamais dans le DOM public.
 * Prod : HLS à jetons + signed Drive. Ne pas copier un skin cyan/Orbitron.
 */
import { useEffect, useMemo, useRef, useState } from "react";
import { Link } from "@tanstack/react-router";
import {
  Box,
  Clapperboard,
  Download,
  Eye,
  Lock,
  MessageCircle,
  Network,
  ShoppingBag,
  Star,
  Terminal,
} from "lucide-react";
import type { GraphMedia, Neighbor, ShopProduct, VideoAsset, VideoNews } from "@/lib/graph";
import { parseChapters } from "@/lib/chapters";
import { unlockVideo } from "@/lib/graph-api";
import { MODE_COPY, videoModeOf } from "@/lib/video-mode";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { ShareBar, Stars } from "@/components/share-bar";

export function HoloPlayer({
  slug,
  media,
  assets,
  news,
  products,
  neighbors = [],
  related = [],
}: {
  slug: string;
  media: GraphMedia;
  assets: VideoAsset[];
  news: VideoNews[];
  products: ShopProduct[];
  neighbors?: Neighbor[];
  related?: GraphMedia[];
}) {
  const mode = videoModeOf(media.mode, media.genre);
  const copy = MODE_COPY[mode];
  const teaser = media.teaserSec ?? 0;
  const gated = (media.accessKind ?? "free") !== "free" && teaser > 0;
  const commerce = mode === "shop" || mode === "formation" || mode === "game" || products.length > 0;
  const offer = products[0];
  const displayPrice = offer?.price || media.price || "";
  const displayRating = offer?.rating || media.rating || "0";
  const displayVotes = offer?.votes ?? 0;
  const sharePath = `/n/${slug}/v/${media.id}`;
  const chapters = useMemo(() => parseChapters(media.chapters), [media.chapters]);
  const [t, setT] = useState(0);
  const [playing, setPlaying] = useState(false);
  const [granted, setGranted] = useState(!gated);
  const [files, setFiles] = useState(assets);
  const [panel, setPanel] = useState<"console" | "graph" | "shop" | "desc" | null>(null);
  const [ctx, setCtx] = useState<"drive" | "live">("drive");
  const [drop, setDrop] = useState(false);
  const videoRef = useRef<HTMLVideoElement>(null);
  const { user } = useCurrentUserState();
  const wall = gated && !granted && t >= teaser;
  const poster = media.nodeId === "orion" ? "/realms/studio-hero.jpg" : "/realms/sea-hero.jpg";
  const mine = news.filter((n) => n.mediaId === media.id);
  const chapFiles = (sec: number) => files.filter((a) => a.chapterSec <= sec + 5 && a.chapterSec >= Math.max(0, sec - 5));

  useEffect(() => {
    setFiles(assets);
    setGranted(!gated);
    setT(0);
    setPlaying(false);
    setPanel(null);
  }, [media.id, assets, gated]);

  useEffect(() => {
    if (granted && t >= 2 && t <= 6) setDrop(true);
    else setDrop(false);
  }, [granted, t]);

  async function unlock() {
    if (!user) return;
    try {
      const res = await unlockVideo({ data: { mediaId: media.id } });
      setGranted(true);
      setFiles(res.assets);
      setPlaying(true);
      setPanel(null);
    } catch {
      /* login */
    }
  }

  function fmt(s: number) {
    return `${String(Math.floor(s / 60)).padStart(2, "0")}:${String(s % 60).padStart(2, "0")}`;
  }

  return (
    <div className="flex h-[calc(100dvh-5.5rem)] flex-col bg-bg">
      {mine.length > 0 ? (
        <div className="flex items-center gap-3 overflow-hidden border-b border-border px-4 py-2 text-xs">
          <span className="shrink-0 rounded-full bg-primary px-2 py-0.5 font-medium text-primary-fg">{copy.ticker}</span>
          <p className="truncate text-muted">{mine.map((n) => n.body).join(" · ")}</p>
        </div>
      ) : null}

      <div className="flex min-h-0 flex-1">
        <div className="relative flex min-w-0 flex-1 flex-col">
          <div className="relative m-3 overflow-hidden rounded-2xl border border-border bg-black aspect-video max-h-[58vh]">
            <video
              ref={videoRef}
              src={media.url || "/media/teaser.mp4"}
              poster={poster}
              className={`size-full object-cover ${wall ? "blur-md" : ""}`}
              onTimeUpdate={(e) => setT(e.currentTarget.currentTime)}
              onPlay={() => setPlaying(true)}
              onPause={() => setPlaying(false)}
              playsInline
            />
            {!playing && !wall ? (
              <button
                type="button"
                onClick={() => {
                  setPlaying(true);
                  void videoRef.current?.play();
                }}
                className="absolute top-1/2 left-1/2 grid size-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-primary text-primary-fg"
                aria-label="Lecture"
              >
                <Clapperboard className="size-7" />
              </button>
            ) : null}
            {drop ? (
              <button
                type="button"
                onClick={() => setDrop(false)}
                className="absolute top-1/4 right-1/3 grid size-12 place-items-center rounded-full border border-primary bg-bg/80 text-primary"
                aria-label="Holo-drop"
              >
                <Box className="size-5" />
              </button>
            ) : null}
            {wall ? (
              <div className="absolute inset-0 grid place-items-center bg-bg/70 p-6">
                <div className="max-w-sm rounded-2xl bg-surface p-6 text-center shadow-[var(--shadow-border)]">
                  <Lock className="mx-auto size-8 text-primary" />
                  <h2 className="mt-3 font-display text-2xl">{copy.paywallTitle}</h2>
                  <p className="mt-2 text-sm text-muted">{copy.paywallBody}</p>
                  {user ? (
                    <button
                      type="button"
                      onClick={() => void unlock()}
                      className="mt-4 h-11 w-full rounded-full bg-primary text-sm font-medium text-primary-fg"
                    >
                      {copy.unlock} {media.price ? `· ${media.price}` : ""}
                    </button>
                  ) : (
                    <Link to="/login" className="mt-4 inline-flex h-11 items-center rounded-full bg-primary px-4 text-sm text-primary-fg">
                      Se connecter pour débloquer
                    </Link>
                  )}
                </div>
              </div>
            ) : null}
            <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-bg to-transparent p-3">
              <div className="relative h-1.5 overflow-hidden rounded-full bg-fg/20">
                <div className="h-full bg-primary" style={{ width: `${Math.min(100, (t / 90) * 100)}%` }} />
                {chapters.map((c) => (
                  <span
                    key={c.sec}
                    className="absolute top-0 h-full w-0.5 bg-fg/70"
                    style={{ left: `${Math.min(98, (c.sec / 90) * 100)}%` }}
                  />
                ))}
              </div>
              <div className="mt-2 flex justify-between font-mono text-[11px] text-fg/80">
                <button type="button" onClick={() => setPlaying((p) => !p)} className="text-primary">
                  {playing && !wall ? "Pause" : "Play"}
                </button>
                <span>
                  {fmt(t)} / {media.duration || "—"}
                </span>
              </div>
            </div>
          </div>

          <div className="mx-3 mb-2 flex flex-wrap items-center justify-center gap-1 rounded-full border border-border bg-surface/90 p-1">
            <button type="button" onClick={() => setPanel(panel === "console" ? null : "console")} className="h-10 rounded-full px-3 text-sm text-muted hover:text-fg">
              <Terminal className="mr-1 inline size-3.5" /> Console
            </button>
            <button type="button" onClick={() => setPanel(panel === "graph" ? null : "graph")} className="h-10 rounded-full px-3 text-sm text-muted hover:text-fg">
              <Network className="mr-1 inline size-3.5" /> Connexions
            </button>
            <button type="button" onClick={() => setPanel(panel === "desc" ? null : "desc")} className="h-10 rounded-full px-3 text-sm text-muted hover:text-fg">
              Contexte
            </button>
            <span className="hidden items-center gap-3 px-2 text-xs text-muted sm:inline-flex">
              <Eye className="size-3.5" /> {media.views ?? 0}
              <Star className="size-3.5 text-primary" /> {media.rating || displayRating || "—"}
            </span>
            <ShareBar title={media.title} path={sharePath} text={media.transcript.slice(0, 80)} />
            {gated ? (
              <button
                type="button"
                onClick={() => setPanel("shop")}
                className="h-10 rounded-full bg-primary px-3 text-sm font-medium text-primary-fg"
              >
                <ShoppingBag className="mr-1 inline size-3.5" />
                {granted ? "Accès actif" : copy.shop}
              </button>
            ) : commerce ? (
              <button
                type="button"
                onClick={() => setPanel("shop")}
                className="h-10 rounded-full bg-primary px-3 text-sm font-medium text-primary-fg"
              >
                <ShoppingBag className="mr-1 inline size-3.5" />
                {displayPrice || copy.shop}
              </button>
            ) : null}
          </div>

          {panel ? (
            <div className="absolute bottom-24 left-1/2 z-20 w-[min(90%,720px)] -translate-x-1/2 rounded-2xl border border-border bg-bg/95 p-5 shadow-[var(--shadow-border)]">
              <div className="mb-3 flex justify-between">
                <p className="text-sm text-primary">
                  {panel === "console" ? "Métadonnées" : panel === "graph" ? "Nœuds liés" : panel === "shop" ? copy.shop : "Description"}
                </p>
                <button type="button" onClick={() => setPanel(null)} className="text-xs text-muted">
                  Fermer
                </button>
              </div>
              {panel === "console" ? (
                <dl className="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                  {[
                    ["Type", mode],
                    ["Genre", media.genre],
                    ["Langue", media.language],
                    ["Difficulté", media.difficulty],
                    ["Accès", media.accessKind],
                    ["Tarif", media.price || "Libre"],
                  ].map(([k, v]) => (
                    <div key={k} className="rounded-xl bg-surface p-2">
                      <dt className="text-[10px] tracking-[0.14em] text-muted uppercase">{k}</dt>
                      <dd className="text-primary">{v || "—"}</dd>
                    </div>
                  ))}
                </dl>
              ) : null}
              {panel === "graph" ? (
                <div className="flex flex-wrap gap-2">
                  {neighbors.slice(0, 8).map((n) => (
                    <Link
                      key={n.node.id}
                      to="/n/$slug"
                      params={{ slug: n.node.slug }}
                      search={{ view: "fiche" }}
                      className="rounded-full border border-border px-3 py-1 text-xs"
                    >
                      {n.node.title}
                    </Link>
                  ))}
                  {related.map((r) => (
                    <span key={r.id} className="rounded-full border border-border px-3 py-1 text-xs text-muted">
                      {r.title}
                    </span>
                  ))}
                </div>
              ) : null}
              {panel === "desc" ? <p className="text-sm leading-relaxed">{media.transcript}</p> : null}
              {panel === "shop" ? (
                <div>
                  <p className="font-display text-2xl">{offer?.title || media.title}</p>
                  <p className="mt-1 text-sm text-muted">{offer?.summary || copy.paywallBody || media.transcript}</p>
                  <p className="mt-3 font-display text-3xl text-primary">{displayPrice || "Libre"}</p>
                  {displayRating !== "0" ? <Stars rating={displayRating} votes={displayVotes} /> : null}
                  {offer?.stock ? <p className="mt-1 text-xs text-muted">{offer.stock}</p> : null}
                  <div className="mt-4 flex flex-wrap gap-2">
                    {!granted && gated && user ? (
                      <button type="button" onClick={() => void unlock()} className="h-11 rounded-full bg-primary px-5 text-sm text-primary-fg">
                        {copy.unlock} {media.price ? `· ${media.price}` : ""}
                      </button>
                    ) : !granted && gated ? (
                      <Link to="/login" className="inline-flex h-11 items-center rounded-full bg-primary px-4 text-sm text-primary-fg">
                        Se connecter
                      </Link>
                    ) : (
                      <button type="button" className="h-11 rounded-full bg-primary px-5 text-sm text-primary-fg">
                        {mode === "shop" ? "Ajouter au panier" : "Accès ouvert"}
                      </button>
                    )}
                    <button type="button" className="h-11 rounded-full border border-border px-4 text-sm">
                      Carte
                    </button>
                    <button type="button" className="h-11 rounded-full border border-border px-4 text-sm">
                      Crypto
                    </button>
                  </div>
                  <div className="mt-4">
                    <ShareBar title={offer?.title || media.title} path={sharePath} text={`${displayPrice} · ${displayRating}/5`} />
                  </div>
                </div>
              ) : null}
            </div>
          ) : null}
        </div>

        <aside className="hidden w-80 shrink-0 flex-col border-l border-border md:flex">
          <div className="flex border-b border-border">
            <button type="button" onClick={() => setCtx("drive")} className={`flex-1 py-3 text-sm ${ctx === "drive" ? "text-primary" : "text-muted"}`}>
              Drive séquencé
            </button>
            <button type="button" onClick={() => setCtx("live")} className={`flex-1 py-3 text-sm ${ctx === "live" ? "text-primary" : "text-muted"}`}>
              <MessageCircle className="mr-1 inline size-3.5" /> Live
            </button>
          </div>
          <div className="flex-1 space-y-2 overflow-y-auto p-3">
            {ctx === "drive"
              ? chapters.map((c) => (
                  <button
                    key={c.sec}
                    type="button"
                    onClick={() => {
                      if (gated && !granted && c.sec >= teaser) return;
                      setT(c.sec);
                    }}
                    className={`w-full rounded-xl border border-border p-3 text-left ${t >= c.sec && t < c.sec + 40 ? "bg-surface" : ""}`}
                  >
                    <p className="font-mono text-[11px] text-primary">{c.t}</p>
                    <p className="text-sm">{c.title}</p>
                    <div className="mt-2 flex flex-wrap gap-1">
                      {chapFiles(c.sec).map((a) =>
                        a.locked && !granted ? (
                          <span key={a.id} className="inline-flex items-center gap-1 rounded-full border border-border px-2 py-0.5 text-[10px] text-muted">
                            <Lock className="size-3" /> {a.name}
                          </span>
                        ) : (
                          <span key={a.id} className="inline-flex items-center gap-1 rounded-full border border-primary/40 px-2 py-0.5 text-[10px] text-primary">
                            <Download className="size-3" /> {a.name}
                          </span>
                        ),
                      )}
                    </div>
                  </button>
                ))
              : (
                  <p className="text-sm text-muted">Le Live du Node (Holo-Forum) se synchronise ici au timestamp.</p>
                )}
          </div>
        </aside>
      </div>
    </div>
  );
}
