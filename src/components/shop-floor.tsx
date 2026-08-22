/** Boutique du Node — prix, note, stock, partage. JSON-LD Product sur /p/:id. */
import { Link } from "@tanstack/react-router";
import { useState } from "react";
import { CckPanel } from "@/components/cck-panel";
import { ShareBar, Stars } from "@/components/share-bar";
import type { CckField, ShopProduct } from "@/lib/graph";
import { addProduct } from "@/lib/graph-api";
import { addToCart } from "@/lib/platform-api";

export function ShopFloor({
  slug,
  products,
  cck = [],
}: {
  slug: string;
  products: ShopProduct[];
  cck?: CckField[];
}) {
  const [list, setList] = useState(products);
  const [title, setTitle] = useState("");
  const [price, setPrice] = useState("");

  async function add() {
    if (!title.trim() || !price.trim()) return;
    try {
      const res = await addProduct({ data: { slug, title: title.trim(), price: price.trim() } });
      setList((cur) => [
        ...cur,
        {
          id: res.id,
          title: title.trim(),
          price: price.trim(),
          summary: "",
          kind: "objet",
          rating: "0",
          votes: 0,
          stock: "en stock",
        },
      ]);
      setTitle("");
      setPrice("");
    } catch {
      /* auth */
    }
  }

  return (
    <div>
      <h2 className="font-display text-3xl">Boutique</h2>
      <p className="mt-1 mb-6 text-sm text-muted">
        Merch, prints, OSTs — collés au lore. Prix, note et lien de partage sur chaque fiche.
      </p>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {list.map((p) => (
          <article key={p.id} className="rounded-3xl bg-surface p-5 shadow-[var(--shadow-border)]">
            <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{p.kind}</p>
            <h3 className="font-display text-2xl">{p.title}</h3>
            <p className="mt-2 text-sm text-muted">{p.summary}</p>
            <p className="mt-4 font-display text-3xl text-primary">{p.price}</p>
            <Stars rating={p.rating} votes={p.votes} />
            <p className="mt-1 text-xs text-muted">{p.stock}</p>
            <div className="mt-3">
              <CckPanel fields={cck.filter((f) => f.targetKind === "product" && f.targetId === p.id)} />
            </div>
            <div className="mt-4 flex flex-wrap gap-2">
              <button
                type="button"
                onClick={() => void addToCart({ data: { productId: p.id } })}
                className="h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
              >
                Ajouter au panier
              </button>
              <Link
                to="/n/$slug/p/$pid"
                params={{ slug, pid: p.id }}
                search={{ view: "fiche" }}
                className="inline-flex h-11 items-center rounded-full border border-border px-3 text-sm"
              >
                Fiche produit
              </Link>
            </div>
            <div className="mt-3">
              <ShareBar title={p.title} path={`/n/${slug}/p/${p.id}`} text={`${p.price} · ${p.rating}/5`} />
            </div>
          </article>
        ))}
      </div>
      <div className="mt-6 flex flex-wrap gap-2">
        <input
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="Nouveau produit"
          className="h-11 rounded-full border border-border bg-surface px-4 text-sm"
        />
        <input
          value={price}
          onChange={(e) => setPrice(e.target.value)}
          placeholder="Prix"
          className="h-11 w-28 rounded-full border border-border bg-surface px-4 text-sm"
        />
        <button
          type="button"
          onClick={() => void add()}
          className="h-11 rounded-full border border-primary/40 px-4 text-sm text-primary"
        >
          Déposer en boutique
        </button>
      </div>
    </div>
  );
}
