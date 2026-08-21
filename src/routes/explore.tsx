import { createFileRoute } from "@tanstack/react-router";
import { Constellation } from "@/components/constellation";
import { NodeCard } from "@/components/node-card";
import { KIND_LABEL, type NodeKind } from "@/lib/graph";
import { getExploreGraph } from "@/lib/graph-api";

export const Route = createFileRoute("/explore")({
  loader: async () => getExploreGraph(),
  component: Explore,
});

function Explore() {
  const { nodes, edges } = Route.useLoaderData();
  const kinds = [...new Set(nodes.map((n) => n.kind))] as NodeKind[];

  return (
    <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6">
      <p className="text-[11px] tracking-[0.22em] text-primary uppercase">Carte</p>
      <h1 className="mt-2 font-display text-4xl">Constellation complète</h1>
      <p className="mt-3 max-w-xl text-sm text-muted">
        {nodes.length} nœuds · {edges.length} liens. Cliquez une étoile pour ouvrir la fiche.
      </p>
      <div className="mt-8">
        <Constellation nodes={nodes} edges={edges} />
      </div>
      {kinds.map((kind) => (
        <section key={kind} className="mt-10">
          <h2 className="font-display text-2xl">{KIND_LABEL[kind]}</h2>
          <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            {nodes
              .filter((n) => n.kind === kind)
              .map((n) => (
                <NodeCard key={n.id} node={n} />
              ))}
          </div>
        </section>
      ))}
    </main>
  );
}
