import { createFileRoute, notFound } from "@tanstack/react-router";
import { LivingWorld } from "@/components/living-world";
import { NodeJsonLd } from "@/components/json-ld";
import { VeraHouse } from "@/components/vera-house";
import { getNodeUniverse } from "@/lib/graph-api";
import { skinOf } from "@/lib/skins";

export const Route = createFileRoute("/n/$slug")({
  validateSearch: (raw: Record<string, unknown>): { view: string } => ({
    view: typeof raw.view === "string" ? raw.view : "fiche",
  }),
  loader: async ({ params }) => {
    const universe = await getNodeUniverse({ data: { slug: params.slug } });
    if (!universe) throw notFound();
    return universe;
  },
  component: NodePage,
});

function NodePage() {
  const universe = Route.useLoaderData();
  const skin = skinOf(universe.node);
  return (
    <>
      <NodeJsonLd node={universe.node} childNodes={universe.children} media={universe.media} />
      {skin === "vera" ? <VeraHouse universe={universe} /> : <LivingWorld universe={universe} />}
    </>
  );
}
