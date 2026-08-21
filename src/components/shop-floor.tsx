/** Boutique du Node — produits enfants, commission 6–7 % en modèle, panier plus tard. */
import { useState } from "react";
import { CckPanel } from "@/components/cck-panel";
import type { CckField, ShopProduct } from "@/lib/graph";
import { addProduct } from "@/lib/graph-api";

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
        { id: res.id, title: title.trim(), price: price.trim(), summary: "", kind: "objet" },
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
        Merch, prints, OSTs — collés au lore, pas une vitrine générique.
      </p>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {list.map((p) => (
          <article key={p.id} className="rounded-3xl bg-surface p-5 shadow-[var(--shadow-border)]">
            <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{p.kind}</p>
            <h3 className="font-display text-2xl">{p.title}</h3>
            <p className="mt-2 text-sm text-muted">{p.summary}</p>
            <div className="mt-3">
              <CckPanel fields={cck.filter((f) => f.targetKind === "product" && f.targetId === p.id)} />
            </div>
            <p className="mt-4 font-display text-xl text-primary">{p.price}</p>
            <button type="button" className="mt-3 h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg">
              Ajouter au panier
            </button>
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
