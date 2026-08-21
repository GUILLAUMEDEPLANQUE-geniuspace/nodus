import { useMemo } from "react";
import type { NodeUniverse } from "@/lib/graph";
import { jsonLdGraph } from "@/lib/seo";

/** JSON-LD @graph unique par Node : JobPosting, Product, Article, TVSeries, Person. */
export function NodeJsonLd({ universe }: { universe: NodeUniverse }) {
  const payload = useMemo(() => jsonLdGraph(universe), [universe]);
  return (
    <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(payload) }} />
  );
}
