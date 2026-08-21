/**
 * Guides type encyclopédie vivante (rapport, tags, sections).
 * Un wiki n'est pas un dump : c'est un article que la guilde tient à jour.
 */
import { useState } from "react";
import type { WikiPage } from "@/lib/graph";

export function WikiGuide({ pages }: { pages: WikiPage[] }) {
  const [id, setId] = useState(pages[0]?.id);
  const page = pages.find((p) => p.id === id) ?? pages[0];
  if (!pages.length) {
    return <p className="text-muted">Aucun guide. L'admin peut en ouvrir depuis le studio.</p>;
  }
  return (
    <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
      <nav>
        <h2 className="font-display text-3xl">Guides</h2>
        <p className="mt-1 mb-4 text-sm text-muted">Rapports, patchs, lore — tenus par la guilde.</p>
        {pages.map((p) => (
          <button
            key={p.id}
            type="button"
            onClick={() => setId(p.id)}
            className={`flex h-11 w-full items-center rounded-xl px-3 text-left text-sm ${
              page?.id === p.id ? "bg-surface text-primary" : "text-muted"
            }`}
          >
            {p.title}
          </button>
        ))}
      </nav>
      {page ? (
        <article className="rounded-3xl bg-surface p-6 shadow-[var(--shadow-border)] sm:p-8">
          <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Rapport de guilde</p>
          <h3 className="mt-2 font-display text-4xl">{page.title}</h3>
          <p className="mt-2 text-xs text-muted">Indexé · lié au graphe du Node</p>
          <p className="mt-5 text-base leading-relaxed text-fg/90">{page.body}</p>
          <div className="mt-6 flex flex-wrap gap-2">
            <span className="rounded-full border border-border px-3 py-1 text-xs text-muted">#lore</span>
            <span className="rounded-full border border-border px-3 py-1 text-xs text-muted">#guide</span>
          </div>
        </article>
      ) : null}
    </div>
  );
}
