/**
 * Fiche produit SEO — prix, note, stock, Offer + AggregateRating.
 */
import { createFileRoute, Link, notFound } from "@tanstack/react-router";
import { ShareBar, Stars } from "@/components/share-bar";
import { getNodeUniverse } from "@/lib/graph-api";
import { addToCart } from "@/lib/platform-api";
import { productOfferLd } from "@/lib/seo";

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
  const json = productOfferLd(product, universe.node);
  return (
    <main className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(json) }} />
      <Link to="/n/$slug" params={{ slug: universe.node.slug }} search={{ view: "fiche" }} className="text-xs text-primary">
        {universe.node.title}
      </Link>
      <p className="mt-4 text-[11px] tracking-[0.16em] text-primary uppercase">{product.kind}</p>
      <h1 className="font-display text-5xl">{product.title}</h1>
      <p className="mt-3 text-muted">{product.summary}</p>
      <p className="mt-6 font-display text-4xl text-primary">{product.price}</p>
      <Stars rating={product.rating} votes={product.votes} />
      <p className="mt-1 text-sm text-muted">{product.stock}</p>
      <div className="mt-6 flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => void addToCart({ data: { productId: product.id } })}
          className="h-11 rounded-full bg-primary px-5 text-sm font-medium text-primary-fg"
        >
          Ajouter au panier
        </button>
        <button type="button" className="h-11 rounded-full border border-border px-4 text-sm">
          Carte
        </button>
        <button type="button" className="h-11 rounded-full border border-border px-4 text-sm">
          Crypto
        </button>
      </div>
      <div className="mt-6">
        <ShareBar
          title={product.title}
          path={`/n/${universe.node.slug}/p/${product.id}`}
          text={`${product.price} · ${product.rating}/5`}
        />
      </div>
    </main>
  );
}
