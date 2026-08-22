/**
 * Onglet Vidéos d'un univers — pas un dump Drive.
 * Chaque fiche a sa console CCK, ses chapitres, son URL SEO.
 */
import { useState } from "react";
import type { GraphMedia } from "@/lib/graph";
import { VideoFiche } from "@/components/video-fiche";

export function VideoStudio({
  slug,
  videos,
}: {
  slug: string;
  videos: GraphMedia[];
}) {
  const list = videos.filter((v) => v.kind === "video");
  const [id, setId] = useState(list[0]?.id ?? 0);
  const active = list.find((v) => v.id === id) ?? list[0];
  if (!list.length) {
    return (
      <div className="px-6 py-16">
        <h2 className="font-display text-3xl">Vidéos</h2>
        <p className="mt-2 text-sm text-muted">
          Aucune fiche encore. Déposez un rush dans le Drive : il devient une fiche à chapitres, pas une pièce jointe.
        </p>
      </div>
    );
  }
  return (
    <div className="mx-auto grid max-w-6xl gap-6 px-4 py-6 lg:grid-cols-[240px_1fr] sm:px-6">
      <aside>
        <h2 className="font-display text-3xl">Vidéos</h2>
        <p className="mt-1 mb-4 text-sm text-muted">Fiches surpuissantes · JSON-LD VideoObject + Clip</p>
        <ul className="space-y-1">
          {list.map((v) => (
            <li key={v.id}>
              <button
                type="button"
                onClick={() => setId(v.id)}
                className={`w-full rounded-xl px-3 py-3 text-left text-sm ${active?.id === v.id ? "bg-surface text-primary" : "text-muted"}`}
              >
                {v.title}
                <span className="mt-0.5 block text-xs text-muted">
                  {v.duration} · {v.genre}
                </span>
              </button>
            </li>
          ))}
        </ul>
      </aside>
      {active ? <VideoFiche media={active} slug={slug} related={list.filter((v) => v.id !== active.id)} /> : null}
    </div>
  );
}
