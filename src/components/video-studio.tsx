/**
 * Onglet Vidéos : liste + cockpit (pas une grille de miniatures YouTube).
 */
import { useState } from "react";
import type { GraphMedia, Neighbor, ShopProduct, VideoAsset, VideoNews } from "@/lib/graph";
import { HoloPlayer } from "@/components/holo-player";

export function VideoStudio({
  slug,
  videos,
  assets,
  news,
  products,
  neighbors,
}: {
  slug: string;
  videos: GraphMedia[];
  assets: VideoAsset[];
  news: VideoNews[];
  products: ShopProduct[];
  neighbors?: Neighbor[];
}) {
  const list = videos.filter((v) => v.kind === "video");
  const [id, setId] = useState(list[0]?.id ?? 0);
  const active = list.find((v) => v.id === id) ?? list[0];
  if (!list.length) {
    return (
      <div className="px-6 py-16">
        <h2 className="font-display text-3xl">Vidéos</h2>
        <p className="mt-2 text-sm text-muted">
          Déposez un rush dans le Drive : formation, jeu, boutique ou entretien — le cockpit s'adapte.
        </p>
      </div>
    );
  }
  return (
    <div className="flex h-[calc(100dvh-5.5rem)]">
      <ul className="hidden w-52 shrink-0 overflow-y-auto border-r border-border p-2 lg:block">
        {list.map((v) => (
          <li key={v.id}>
            <button
              type="button"
              onClick={() => setId(v.id)}
              className={`w-full rounded-xl px-3 py-3 text-left text-sm ${active?.id === v.id ? "bg-surface text-primary" : "text-muted"}`}
            >
              {v.title}
              <span className="mt-0.5 block text-[11px] text-muted">
                {v.mode || v.genre} · {v.accessKind === "free" ? "libre" : v.price || "verrouillé"}
              </span>
            </button>
          </li>
        ))}
      </ul>
      {active ? (
        <div className="min-w-0 flex-1">
          <HoloPlayer
            slug={slug}
            media={active}
            assets={assets.filter((a) => a.mediaId === active.id)}
            news={news.filter((n) => n.mediaId === active.id)}
            products={products}
            neighbors={neighbors}
            related={list.filter((v) => v.id !== active.id)}
          />
        </div>
      ) : null}
    </div>
  );
}
