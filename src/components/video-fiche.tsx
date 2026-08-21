import { useMemo, useState } from "react";
import { Clapperboard, Clock, ListOrdered } from "lucide-react";
import type { GraphMedia } from "@/lib/graph";

type Chapter = { t: string; title: string };

function parseChapters(raw: string): Chapter[] {
  return raw
    .split(/\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const m = line.match(/^(\d{1,2}:\d{2})\s*[—–-]\s*(.+)$/);
      return m ? { t: m[1], title: m[2] } : { t: "", title: line };
    });
}

export function VideoFiche({ media }: { media: GraphMedia }) {
  const chapters = useMemo(() => parseChapters(media.chapters), [media.chapters]);
  const [open, setOpen] = useState(true);
  const [active, setActive] = useState(0);

  if (media.kind === "file") {
    return (
      <article className="rounded-2xl bg-surface p-4 shadow-[var(--shadow-border)]">
        <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Fichier · Drive</p>
        <h3 className="mt-1 font-display text-xl">{media.title}</h3>
        {media.transcript ? <p className="mt-2 text-sm leading-relaxed text-muted">{media.transcript}</p> : null}
      </article>
    );
  }

  return (
    <article className="overflow-hidden rounded-2xl bg-surface shadow-[var(--shadow-border)]">
      <div className="relative aspect-video bg-bg">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_30%_40%,rgba(201,163,106,0.18),transparent_45%)]" />
        <div className="absolute inset-0 grid place-items-center">
          <span className="grid size-16 place-items-center rounded-full border border-primary/50 bg-primary/15">
            <Clapperboard className="size-7 text-primary" />
          </span>
        </div>
        <div className="absolute right-3 bottom-3 left-3 flex items-end justify-between text-xs text-fg/80">
          <span className="font-display text-lg">{media.title}</span>
          {media.duration ? (
            <span className="inline-flex items-center gap-1 rounded-full bg-bg/70 px-2 py-1">
              <Clock className="size-3" />
              {media.duration}
            </span>
          ) : null}
        </div>
      </div>
      <div className="p-4">
        <div className="flex flex-wrap items-center gap-2 text-xs text-muted">
          {media.genre ? <span className="rounded-full border border-border px-2 py-1">{media.genre}</span> : null}
          <button
            type="button"
            className="inline-flex h-11 items-center gap-1 rounded-full px-2 text-primary"
            onClick={() => setOpen((v) => !v)}
          >
            <ListOrdered className="size-4" />
            Chapitres
          </button>
        </div>
        {open && chapters.length > 0 ? (
          <ol className="mt-3 space-y-1">
            {chapters.map((c, i) => (
              <li key={`${c.t}-${c.title}`}>
                <button
                  type="button"
                  onClick={() => setActive(i)}
                  className={`flex w-full items-baseline gap-3 rounded-lg px-2 py-2 text-left text-sm ${
                    i === active ? "bg-surface-2 text-primary" : "text-fg/90 hover:bg-surface-2"
                  }`}
                >
                  <span className="w-12 shrink-0 font-mono text-xs text-muted tabular-nums">{c.t}</span>
                  <span>{c.title}</span>
                </button>
              </li>
            ))}
          </ol>
        ) : null}
        {media.transcript ? (
          <p className="mt-3 text-sm leading-relaxed text-muted">{media.transcript}</p>
        ) : null}
      </div>
    </article>
  );
}
