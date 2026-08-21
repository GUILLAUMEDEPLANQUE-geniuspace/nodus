/** Playlists perso partageables — vidéo + musique, au-dessus d'un dump YouTube. */
import { useState } from "react";
import type { Playlist } from "@/lib/graph";
import { addPlaylist } from "@/lib/graph-api";

export function PlaylistDeck({ slug, playlists }: { slug: string; playlists: Playlist[] }) {
  const [list, setList] = useState(playlists);
  const [title, setTitle] = useState("");
  const [open, setOpen] = useState(playlists[0]?.id ?? null);
  const active = list.find((p) => p.id === open) ?? list[0];

  async function create() {
    if (!title.trim()) return;
    try {
      const res = await addPlaylist({ data: { slug, title: title.trim() } });
      setList((cur) => [...cur, { id: res.id, title: title.trim(), author: "vous", items: [] }]);
      setTitle("");
    } catch {
      /* auth */
    }
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[240px_1fr]">
      <div>
        <h2 className="font-display text-3xl">Playlists</h2>
        <p className="mt-1 mb-4 text-sm text-muted">Partageables, liées au Node, pas un favori perdu.</p>
        <ul className="space-y-1">
          {list.map((p) => (
            <li key={p.id}>
              <button
                type="button"
                onClick={() => setOpen(p.id)}
                className={`w-full rounded-xl px-3 py-3 text-left text-sm ${open === p.id ? "bg-surface" : ""}`}
              >
                {p.title}
                <span className="mt-0.5 block text-xs text-muted">{p.author}</span>
              </button>
            </li>
          ))}
        </ul>
        <div className="mt-3 flex gap-2">
          <input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Nouvelle playlist"
            className="h-11 flex-1 rounded-full border border-border bg-surface px-4 text-sm"
          />
          <button
            type="button"
            onClick={() => void create()}
            className="h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
          >
            Créer
          </button>
        </div>
      </div>
      {active ? (
        <ol className="space-y-2 rounded-3xl bg-surface p-5">
          <h3 className="font-display text-2xl">{active.title}</h3>
          {active.items.map((it, i) => (
            <li key={it.id} className="flex items-center justify-between border-b border-border py-3 text-sm">
              <span>
                {i + 1}. {it.title}
              </span>
              <span className="text-muted">
                {it.kind} · {it.duration}
              </span>
            </li>
          ))}
          {active.items.length === 0 ? <p className="text-sm text-muted">Vide — glissez des reliques ici.</p> : null}
        </ol>
      ) : null}
    </div>
  );
}
