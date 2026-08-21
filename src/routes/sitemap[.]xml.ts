/**
 * Sitemap XML — une URL par Node.
 * C'est le socle SEO : Google et les LLM crawlent ici, pas la home.
 */
import { createFileRoute } from "@tanstack/react-router";
import { listForumTopics, listNodeSlugs } from "@/lib/graph-api";

export const Route = createFileRoute("/sitemap.xml")({
  server: {
    handlers: {
      GET: async () => {
        const nodes = await listNodeSlugs();
        const topics = await listForumTopics();
        const urls = [
          ...nodes.map(
            (n) =>
              `  <url><loc>/n/${n.slug}</loc><changefreq>weekly</changefreq><priority>${n.kind === "series" || n.kind === "company" ? "0.9" : "0.7"}</priority></url>`,
          ),
          ...topics.map(
            (t) =>
              `  <url><loc>/n/${t.slug}/t/${t.id}</loc><changefreq>daily</changefreq><priority>0.8</priority></url>`,
          ),
        ].join("\n");
        const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
${urls}
</urlset>`;
        return new Response(xml, {
          headers: { "Content-Type": "application/xml; charset=utf-8" },
        });
      },
    },
  },
});
