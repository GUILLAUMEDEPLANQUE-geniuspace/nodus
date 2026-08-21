import { useState } from "react";
import type { WikiPage } from "@/lib/graph";

export function WikiPanel({ pages }: { pages: WikiPage[] }) {
  const [id, setId] = useState(pages[0]?.id);
  const page = pages.find((p) => p.id === id) ?? pages[0];

  if (!pages.length) {
    return (
      <div className="rounded-2xl bg-surface p-8 shadow-[var(--shadow-border)]">
        <p className="font-display text-2xl">Wiki à ouvrir</p>
        <p className="mt-2 text-sm text-muted">
          Un Node série ou franchise devient un site : lore, règles, factions. Voyez One Piece pour l'exemple.
        </p>
      </div>
    );
  }

  return (
    <div className="grid gap-4 lg:grid-cols-[220px_1fr]">
      <nav className="rounded-2xl bg-surface p-3 shadow-[var(--shadow-border)]">
        {pages.map((p) => (
          <button
            key={p.id}
            type="button"
            onClick={() => setId(p.id)}
            className={`flex h-11 w-full items-center rounded-xl px-3 text-left text-sm ${
              page?.id === p.id ? "bg-surface-2 text-primary" : "text-fg"
            }`}
          >
            {p.title}
          </button>
        ))}
      </nav>
      {page ? (
        <article className="rounded-2xl bg-surface p-6 shadow-[var(--shadow-border)]">
          <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Wiki</p>
          <h3 className="mt-1 font-display text-3xl">{page.title}</h3>
          <p className="mt-4 text-sm leading-relaxed text-fg/90">{page.body}</p>
        </article>
      ) : null}
    </div>
  );
}
