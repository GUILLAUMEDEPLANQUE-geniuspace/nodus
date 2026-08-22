import { createFileRoute, notFound } from "@tanstack/react-router";
import { useEffect } from "react";
import { LivingWorld } from "@/components/living-world";
import { NodeJsonLd } from "@/components/json-ld";
import { VeraHouse } from "@/components/vera-house";
import { getNodeUniverse } from "@/lib/graph-api";
import { recordVisit } from "@/lib/platform-api";
import { seoForNode } from "@/lib/seo";
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
  head: ({ loaderData }) => {
    if (!loaderData) return {};
    const s = seoForNode(loaderData.node, loaderData.seo);
    return {
      meta: [
        { title: s.title },
        { name: "description", content: s.description },
        { name: "keywords", content: s.keywords },
        { name: "robots", content: s.noindex ? "noindex,nofollow" : "index,follow,max-image-preview:large" },
        { property: "og:title", content: s.title },
        { property: "og:description", content: s.description },
        { property: "og:type", content: s.ogType },
        { name: "twitter:card", content: "summary_large_image" },
        { name: "twitter:title", content: s.title },
      ],
      links: [{ rel: "canonical", href: s.canonical }],
    };
  },
  component: NodePage,
});

function NodePage() {
  const universe = Route.useLoaderData();
  const skin = skinOf(universe.node);
  useEffect(() => {
    void recordVisit({ data: { slug: universe.node.slug } }).catch(() => {});
  }, [universe.node.slug]);
  return (
    <>
      <NodeJsonLd universe={universe} />
      {skin === "vera" ? <VeraHouse universe={universe} /> : <LivingWorld universe={universe} />}
    </>
  );
}
