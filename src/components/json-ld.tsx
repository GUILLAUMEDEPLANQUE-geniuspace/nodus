import { useMemo } from "react";
import type { GraphMedia, GraphNode } from "@/lib/graph";

export function NodeJsonLd({
  node,
  childNodes,
  media,
}: {
  node: GraphNode;
  childNodes: GraphNode[];
  media: GraphMedia[];
}) {
  const payload = useMemo(() => {
    const isPerson = node.kind === "person";
    const isCharacter = node.kind === "character";
    const isSeries = node.kind === "series" || node.kind === "franchise";
    const base = {
      "@context": "https://schema.org",
      name: node.title,
      description: node.summary,
      url: `/n/${node.slug}`,
    };
    if (isPerson) {
      return {
        ...base,
        "@type": "Person",
        jobTitle: node.subtitle,
        performerIn: childNodes.map((c) => ({
          "@type": "PerformanceRole",
          characterName: c.title,
          url: `/n/${c.slug}`,
        })),
      };
    }
    if (isCharacter) {
      return { ...base, "@type": "Person", disambiguatingDescription: "Fictional character" };
    }
    if (isSeries) {
      return {
        ...base,
        "@type": "TVSeries",
        character: childNodes.filter((c) => c.kind === "character").map((c) => ({ "@type": "Person", name: c.title })),
      };
    }
    const video = media.find((m) => m.kind === "video");
    if (video) {
      return {
        "@context": "https://schema.org",
        "@type": "VideoObject",
        name: video.title,
        description: video.transcript,
        duration: video.duration,
      };
    }
    return { ...base, "@type": "Thing" };
  }, [node, childNodes, media]);

  return (
    <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(payload) }} />
  );
}
