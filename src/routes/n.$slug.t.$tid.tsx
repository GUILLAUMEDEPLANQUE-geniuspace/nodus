/**
 * URL unique par sujet — moat SEO.
 * /n/one-piece/t/th-op-1 indexe le Legacy, pas le Live.
 */
import { createFileRoute, notFound } from "@tanstack/react-router";
import { HoloForum } from "@/components/holo-forum";
import { getNodeUniverse } from "@/lib/graph-api";

export const Route = createFileRoute("/n/$slug/t/$tid")({
  validateSearch: (raw: Record<string, unknown>): { view: string } => ({
    view: typeof raw.view === "string" ? raw.view : "fiche",
  }),
  loader: async ({ params }) => {
    const universe = await getNodeUniverse({ data: { slug: params.slug } });
    if (!universe) throw notFound();
    const thread = universe.threads.find((t) => t.id === params.tid);
    if (!thread) throw notFound();
    return { universe, thread };
  },
  head: ({ loaderData }) => {
    if (!loaderData) return {};
    const { universe, thread } = loaderData;
    const title = `${thread.title} — forum ${universe.node.title} | NODUS`;
    const description = thread.body.slice(0, 160);
    return {
      meta: [
        { title },
        { name: "description", content: description },
        { name: "robots", content: "index,follow" },
        { property: "og:title", content: title },
        { property: "og:description", content: description },
        { property: "og:type", content: "article" },
      ],
      links: [{ rel: "canonical", href: `/n/${universe.node.slug}/t/${thread.id}` }],
    };
  },
  component: TopicPage,
});

function TopicPage() {
  const { universe, thread } = Route.useLoaderData();
  const json = {
    "@context": "https://schema.org",
    "@type": "DiscussionForumPosting",
    headline: thread.title,
    articleBody: thread.body,
    author: { "@type": "Person", name: thread.author },
    isPartOf: { "@type": "WebPage", url: `/n/${universe.node.slug}` },
    comment: universe.replies
      .filter((r) => r.threadId === thread.id)
      .map((r) => ({ "@type": "Comment", text: r.body, author: r.author })),
  };
  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(json) }} />
      <HoloForum
        slug={universe.node.slug}
        threads={universe.threads}
        replies={universe.replies}
        live={universe.live}
        products={universe.products}
        onPosted={() => {}}
        initialId={thread.id}
      />
    </>
  );
}
