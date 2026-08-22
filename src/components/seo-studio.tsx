/**
 * Éditeur meta — owner / admin uniquement.
 * Google indexe le RÉSULTAT public, jamais cet écran.
 */
import { useEffect, useState } from "react";
import { getMyRole, saveNodeSeo } from "@/lib/platform-api";
import type { NodeSeo } from "@/lib/graph";

export function SeoStudio({ slug, seo }: { slug: string; seo: NodeSeo | null }) {
  const [allowed, setAllowed] = useState(false);
  const [title, setTitle] = useState(seo?.title ?? "");
  const [description, setDescription] = useState(seo?.description ?? "");
  const [keywords, setKeywords] = useState(seo?.keywords ?? "");
  const [noindex, setNoindex] = useState(Boolean(seo?.noindex));
  const [msg, setMsg] = useState("");

  useEffect(() => {
    void getMyRole({ data: { slug } })
      .then((r) => setAllowed(r.role === "owner" || r.role === "admin"))
      .catch(() => setAllowed(false));
  }, [slug]);

  if (!allowed) {
    return (
      <p className="rounded-2xl bg-surface p-4 text-sm text-muted">
        L'éditeur SEO est réservé au propriétaire et aux admins. La fiche publique, elle, reste visible de tous
        (et de Google) — sinon il n'y a pas de référencement.
      </p>
    );
  }

  return (
    <form
      className="space-y-3 rounded-3xl bg-surface p-5"
      onSubmit={(e) => {
        e.preventDefault();
        void saveNodeSeo({ data: { slug, title, description, keywords, noindex } })
          .then(() => setMsg("Meta enregistrées. La page publique les sert au crawl."))
          .catch((err) => setMsg(err instanceof Error ? err.message : "Droits insuffisants"));
      }}
    >
      <h3 className="font-display text-2xl">SEO du Node</h3>
      <p className="text-sm text-muted">70 car. title · 160 description. noindex = brouillon seulement.</p>
      <input
        value={title}
        onChange={(e) => setTitle(e.target.value)}
        maxLength={70}
        placeholder="Title"
        className="h-11 w-full rounded-full border border-border bg-bg px-4 text-sm"
      />
      <textarea
        value={description}
        onChange={(e) => setDescription(e.target.value)}
        maxLength={160}
        rows={3}
        placeholder="Description"
        className="w-full rounded-2xl border border-border bg-bg px-4 py-2 text-sm"
      />
      <input
        value={keywords}
        onChange={(e) => setKeywords(e.target.value)}
        placeholder="mots-clés, séparés, virgule"
        className="h-11 w-full rounded-full border border-border bg-bg px-4 text-sm"
      />
      <label className="flex items-center gap-2 text-sm">
        <input type="checkbox" checked={noindex} onChange={(e) => setNoindex(e.target.checked)} />
        noindex (ne pas crawler — brouillon)
      </label>
      <button type="submit" className="h-11 rounded-full bg-primary px-4 text-sm text-primary-fg">
        Publier les meta
      </button>
      {msg ? <p className="text-xs text-primary">{msg}</p> : null}
    </form>
  );
}
