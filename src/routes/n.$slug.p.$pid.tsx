/**
 * Fiche produit = Command Center (vidéo + RWA + chaudron).
 * JSON-LD VisualArtwork si rwa.
 */
import { createFileRoute, notFound } from "@tanstack/react-router";
import { CommandCenter, ProductJsonLd } from "@/components/command-center";
import { getNodeUniverse } from "@/lib/graph-api";

export const Route = createFileRoute("/n/$slug/p/$pid")({
  validateSearch: (raw: Record<string, unknown>): { view: string } => ({
    view: typeof raw.view === "string" ? raw.view : "fiche",
  }),
  loader: async ({ params }) => {
    const universe = await getNodeUniverse({ data: { slug: params.slug } });
    if (!universe) throw notFound();
    const product = universe.products.find((p) => p.id === params.pid);
    if (!product) throw notFound();
    return { universe, product };
  },
  head: ({ loaderData }) => {
    if (!loaderData) return {};
    const { universe, product } = loaderData;
    const title = `${product.title} — ${product.price} · ${product.rating}/5 | ${universe.node.title}`;
    return {
      meta: [
        { title },
        { name: "description", content: product.summary || `${product.title} à ${product.price}` },
        { property: "og:type", content: "product" },
        { property: "og:title", content: title },
        { property: "product:price:amount", content: product.price.replace(/[^\d.,]/g, "") },
        { property: "product:price:currency", content: "EUR" },
      ],
      links: [{ rel: "canonical", href: `/n/${universe.node.slug}/p/${product.id}` }],
    };
  },
  component: ProductPage,
});

function ProductPage() {
  const { universe, product } = Route.useLoaderData();
  const products = universe.products.length ? universe.products : [product];
  return (
    <>
      <ProductJsonLd product={product} slug={universe.node.slug} />
      <CommandCenter
        slug={universe.node.slug}
        products={products}
        media={universe.media.find((m) => m.kind === "video")}
        ads={universe.ads}
        goal={universe.crowdGoal}
        neighbors={universe.neighbors}
        news={universe.videoNews}
      />
    </>
  );
}
