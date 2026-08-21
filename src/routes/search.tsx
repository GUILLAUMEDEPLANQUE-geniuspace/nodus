import { createFileRoute } from "@tanstack/react-router";
import { NodeCard } from "@/components/node-card";
import { searchNodes } from "@/lib/graph-api";

type Search = { q: string };

export const Route = createFileRoute("/search")({
  validateSearch: (raw: Record<string, unknown>): Search => ({
    q: typeof raw.q === "string" ? raw.q : "",
  }),
  loaderDeps: ({ search }) => ({ q: search.q }),
  loader: async ({ deps }) => {
    if (!deps.q.trim()) return { results: [] };
    return { results: await searchNodes({ data: { q: deps.q } }) };
  },
  component: SearchPage,
});

function SearchPage() {
  const { q } = Route.useSearch();
  const { results } = Route.useLoaderData();

  return (
    <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
      <p className="text-[11px] tracking-[0.22em] text-primary uppercase">Recherche</p>
      <h1 className="mt-2 font-display text-4xl">{q || "Chercher un nœud"}</h1>
      <p className="mt-2 text-sm text-muted">{results.length} résultat{results.length > 1 ? "s" : ""}</p>
      <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {results.map((n) => (
          <NodeCard key={n.id} node={n} />
        ))}
      </div>
      {q && results.length === 0 ? (
        <p className="mt-8 text-muted">Aucun nœud pour « {q} ». Essayez O'Neill, Luffy, Orion…</p>
      ) : null}
    </main>
  );
}
