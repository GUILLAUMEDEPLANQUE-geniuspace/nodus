/**
 * Fiche vidéo NODUS (parité JoomCCK vlog, tokens or/encre).
 * Player lazy, console (genre, durée, saison, langue, difficulté),
 * ruban, chapitres seek, transcript, JSON-LD via la route /v/:id.
 */
import { useMemo, useState } from "react";
import { Link } from "@tanstack/react-router";
import { Clapperboard, ListOrdered, ScrollText } from "lucide-react";
import type { GraphMedia } from "@/lib/graph";
import { parseChapters } from "@/lib/chapters";

export function VideoFiche({
  media,
  slug,
  related = [],
}: {
  media: GraphMedia;
  slug?: string;
  related?: GraphMedia[];
}) {
  const chapters = useMemo(() => parseChapters(media.chapters), [media.chapters]);
  const [playing, setPlaying] = useState(false);
  const [showCh, setShowCh] = useState(true);
  const [showTr, setShowTr] = useState(false);
  const [tab, setTab] = useState<"desc" | "tags" | "related">("desc");
  const [at, setAt] = useState(0);
  const poster = media.nodeId === "luffy" ? "/realms/luffy.jpg" : "/realms/sea-hero.jpg";
  const consoleLines = [
    ["GENRE", media.genre],
    ["DURÉE", media.duration],
    ["SAISON", media.season],
    ["ÉPISODE", media.episode],
    ["LANGUE", media.language],
    ["DIFFICULTÉ", media.difficulty],
  ].filter(([, v]) => Boolean(v));

  if (media.kind === "file") {
    return (
      <article className="rounded-2xl bg-surface p-4 shadow-[var(--shadow-border)]">
        <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Fichier · Drive</p>
        <h3 className="mt-1 font-display text-xl">{media.title}</h3>
        {media.transcript ? <p className="mt-2 text-sm text-muted">{media.transcript}</p> : null}
      </article>
    );
  }

  return (
    <article className="overflow-hidden rounded-3xl bg-surface shadow-[var(--shadow-border)]">
      <button
        type="button"
        onClick={() => setPlaying(true)}
        className="relative block aspect-video w-full overflow-hidden bg-bg"
        aria-label="Lire"
      >
        <img src={poster} alt="" className="size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-bg/80 to-transparent" />
        {!playing ? (
          <span className="absolute top-1/2 left-1/2 grid size-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-primary text-primary-fg">
            <Clapperboard className="size-7" />
          </span>
        ) : (
          <span className="absolute right-3 bottom-3 rounded-full bg-bg/80 px-3 py-1 text-xs">
            {chapters[at]?.t ?? "00:00"} · {chapters[at]?.title ?? "Lecture"}
          </span>
        )}
      </button>

      <div className="flex flex-wrap gap-2 p-3">
        <button type="button" onClick={() => setShowCh((v) => !v)} className="inline-flex h-11 items-center gap-1 rounded-full border border-border px-3 text-sm">
          <ListOrdered className="size-4 text-primary" /> Chapitres
        </button>
        <button type="button" onClick={() => setShowTr((v) => !v)} className="inline-flex h-11 items-center gap-1 rounded-full border border-border px-3 text-sm">
          <ScrollText className="size-4 text-primary" /> Transcript
        </button>
      </div>

      {showCh && chapters.length > 0 ? (
        <ol className="space-y-1 px-4 pb-3">
          {chapters.map((c, i) => (
            <li key={`${c.sec}-${c.title}`}>
              <button
                type="button"
                onClick={() => {
                  setAt(i);
                  setPlaying(true);
                }}
                className={`flex w-full items-baseline gap-3 rounded-lg px-2 py-2 text-left text-sm ${
                  i === at ? "bg-surface-2 text-primary" : "text-fg/90"
                }`}
              >
                <span className="w-12 shrink-0 font-mono text-xs text-muted tabular-nums">{c.t}</span>
                <span>{c.title}</span>
              </button>
            </li>
          ))}
        </ol>
      ) : null}

      {showTr && media.transcript ? (
        <p className="mx-4 mb-4 rounded-2xl border border-border bg-bg p-4 text-sm leading-relaxed">{media.transcript}</p>
      ) : null}

      <header className="flex flex-wrap items-start justify-between gap-4 px-5 pb-4">
        <div>
          {media.ribbon ? (
            <span className="mb-2 inline-block rounded-full bg-primary px-3 py-1 text-[11px] font-medium tracking-[0.12em] text-primary-fg uppercase">
              {media.ribbon}
            </span>
          ) : null}
          <h1 className="font-display text-3xl">{media.title}</h1>
          {slug && media.id ? (
            <Link
              to="/n/$slug/v/$vid"
              params={{ slug, vid: String(media.id) }}
              search={{ view: "fiche" }}
              className="mt-1 inline-block text-xs text-primary"
            >
              URL SEO de la fiche
            </Link>
          ) : null}
        </div>
        {consoleLines.length > 0 ? (
          <aside className="min-w-52 rounded-2xl border border-primary/30 bg-bg p-3 font-mono text-xs">
            {consoleLines.map(([k, v]) => (
              <p key={k} className="flex gap-2 py-0.5">
                <span className="text-muted">{k}:</span>
                <span className="text-primary">{v}</span>
              </p>
            ))}
          </aside>
        ) : null}
      </header>

      <div className="border-t border-border px-5 py-3">
        <div className="flex gap-3 text-sm">
          {(["desc", "tags", "related"] as const).map((id) => (
            <button
              key={id}
              type="button"
              onClick={() => setTab(id)}
              className={tab === id ? "text-primary" : "text-muted"}
            >
              {id === "desc" ? "Description" : id === "tags" ? "Mots-clés" : "Similaires"}
            </button>
          ))}
        </div>
        <div className="mt-3 text-sm leading-relaxed text-fg/90">
          {tab === "desc" ? media.transcript : null}
          {tab === "tags" ? (
            <div className="flex flex-wrap gap-2">
              {[media.genre, media.ribbon, media.season, media.language].filter(Boolean).map((t) => (
                <span key={t} className="rounded-full border border-border px-3 py-1 text-xs">
                  {t}
                </span>
              ))}
            </div>
          ) : null}
          {tab === "related" ? (
            related.length ? (
              <ul className="space-y-2">
                {related.map((r) => (
                  <li key={r.id} className="text-primary">
                    {r.title}
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-muted">Les connexions graphe apparaissent ici.</p>
            )
          ) : null}
        </div>
      </div>
    </article>
  );
}
