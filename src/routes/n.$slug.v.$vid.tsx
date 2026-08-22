/**
 * URL unique par fiche vidéo — moat SEO (VideoObject + Clip hasPart).
 */
import { createFileRoute, notFound } from "@tanstack/react-router";
import { HoloPlayer } from "@/components/holo-player";
import { parseChapters, videoObjectLd } from "@/lib/chapters";
import { getNodeUniverse } from "@/lib/graph-api";

export const Route = createFileRoute("/n/$slug/v/$vid")({
  validateSearch: (raw: Record<string, unknown>): { view: string } => ({
    view: typeof raw.view === "string" ? raw.view : "fiche",
  }),
  loader: async ({ params }) => {
    const universe = await getNodeUniverse({ data: { slug: params.slug } });
    if (!universe) throw notFound();
    const media = universe.media.find((m) => String(m.id) === params.vid);
    if (!media) throw notFound();
    return { universe, media };
  },
  head: ({ loaderData }) => {
    if (!loaderData) return {};
    const { universe, media } = loaderData;
    const title = `${media.title} — vidéo ${universe.node.title} | NODUS`;
    const description = (media.transcript || media.genre).slice(0, 160);
    return {
      meta: [
        { title },
        { name: "description", content: description },
        { name: "keywords", content: [media.title, media.genre, media.ribbon, universe.node.title].filter(Boolean).join(", ") },
        { property: "og:title", content: title },
        { property: "og:description", content: description },
        { property: "og:type", content: "video.other" },
        { name: "twitter:card", content: "summary_large_image" },
      ],
      links: [{ rel: "canonical", href: `/n/${universe.node.slug}/v/${media.id}` }],
    };
  },
  component: VideoPage,
});

function VideoPage() {
  const { universe, media } = Route.useLoaderData();
  const chapters = parseChapters(media.chapters);
  const json = videoObjectLd(media, chapters, `/n/${universe.node.slug}/v/${media.id}`);
  const related = universe.media.filter((m) => m.kind === "video" && m.id !== media.id);
  return (
    <main>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(json) }} />
      <HoloPlayer
        slug={universe.node.slug}
        media={media}
        assets={universe.videoAssets.filter((a) => a.mediaId === media.id)}
        news={universe.videoNews.filter((n) => n.mediaId === media.id)}
        products={universe.products}
        neighbors={universe.neighbors}
        related={related}
      />
    </main>
  );
}
