import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { useMemo, useState } from "react";
import { RedirectToSignIn } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { KIND_LABEL, NODE_KINDS, type NodeKind } from "@/lib/graph";
import { createNode, searchNodes } from "@/lib/graph-api";

export const Route = createFileRoute("/create")({ component: CreatePage });

const PROFILES: { id: string; label: string; hint: string }[] = [
  { id: "fan", label: "Fan / univers", hint: "Manga, série, lore — personnages enfants d'une œuvre." },
  { id: "recruiter", label: "Recruteur", hint: "Maison parente, offres enfants, épreuves." },
  { id: "creator", label: "Créateur", hint: "Atelier, produits, fiches vidéo." },
  { id: "other", label: "Autre", hint: "Personne, lieu, concept." },
];

function CreatePage() {
  const { user, isPending } = useCurrentUserState();
  if (isPending) {
    return <div className="mx-auto max-w-lg px-4 py-16 text-muted">Préparation du studio…</div>;
  }
  if (!user) return <RedirectToSignIn to="/login" />;
  return <Wizard />;
}

function Wizard() {
  const navigate = useNavigate();
  const [step, setStep] = useState(0);
  const [profile, setProfile] = useState("fan");
  const [kind, setKind] = useState<NodeKind>("character");
  const [title, setTitle] = useState("");
  const [subtitle, setSubtitle] = useState("");
  const [summary, setSummary] = useState("");
  const [parentQ, setParentQ] = useState("");
  const [parentSlug, setParentSlug] = useState<string | undefined>();
  const [parentTitle, setParentTitle] = useState("");
  const [hits, setHits] = useState<{ slug: string; title: string }[]>([]);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const suggestedKind = useMemo<NodeKind>(() => {
    if (profile === "recruiter") return "job";
    if (profile === "creator") return "product";
    if (profile === "fan") return "series";
    return "person";
  }, [profile]);

  async function lookupParent(q: string) {
    setParentQ(q);
    if (q.trim().length < 2) {
      setHits([]);
      return;
    }
    const res = await searchNodes({ data: { q } });
    setHits(res.slice(0, 6).map((n) => ({ slug: n.slug, title: n.title })));
  }

  async function submit() {
    setBusy(true);
    setError(null);
    try {
      const { slug } = await createNode({
        data: {
          kind,
          title,
          subtitle,
          summary,
          parentSlug,
          edgeKind: kind === "character" || kind === "job" || kind === "product" ? "parent_of" : "parent_of",
        },
      });
      await navigate({ to: "/n/$slug", params: { slug }, search: { view: "fiche" } });
    } catch (e) {
      setError(e instanceof Error ? e.message : "Impossible d'enregistrer");
      setBusy(false);
    }
  }

  return (
    <main className="mx-auto max-w-lg px-4 py-10">
      <p className="text-[11px] tracking-[0.22em] text-primary uppercase">Studio guidé</p>
      <h1 className="mt-2 font-display text-4xl">On vous prend par la main</h1>
      <p className="mt-2 text-sm text-muted">Étape {step + 1} / 3</p>
      <div className="mt-4 h-1 overflow-hidden rounded-full bg-surface-2">
        <div className="h-full bg-primary transition-all" style={{ width: `${((step + 1) / 3) * 100}%` }} />
      </div>

      {step === 0 ? (
        <div className="mt-8 space-y-3">
          {PROFILES.map((p) => (
            <button
              key={p.id}
              type="button"
              onClick={() => {
                setProfile(p.id);
                setKind(p.id === "recruiter" ? "job" : p.id === "creator" ? "product" : p.id === "fan" ? "series" : "character");
              }}
              className={`w-full rounded-2xl p-4 text-left shadow-[var(--shadow-border)] ${
                profile === p.id ? "bg-surface-2" : "bg-surface"
              }`}
            >
              <p className="font-display text-xl">{p.label}</p>
              <p className="mt-1 text-sm text-muted">{p.hint}</p>
            </button>
          ))}
          <button
            type="button"
            className="mt-4 h-12 w-full rounded-full bg-primary text-sm font-medium text-primary-fg"
            onClick={() => {
              setKind(suggestedKind);
              setStep(1);
            }}
          >
            Continuer
          </button>
        </div>
      ) : null}

      {step === 1 ? (
        <div className="mt-8 space-y-4">
          <label className="block text-sm">
            Type de nœud
            <select
              value={kind}
              onChange={(e) => setKind(e.target.value as NodeKind)}
              className="mt-1 h-12 w-full rounded-xl border border-border bg-surface px-3 text-fg"
            >
              {NODE_KINDS.map((k) => (
                <option key={k} value={k}>
                  {KIND_LABEL[k]}
                </option>
              ))}
            </select>
          </label>
          <label className="block text-sm">
            Titre
            <input
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="Jack O'Neill, Luffy, Lead designer…"
              className="mt-1 h-12 w-full rounded-xl border border-border bg-surface px-3"
            />
          </label>
          <label className="block text-sm">
            Sous-titre
            <input
              value={subtitle}
              onChange={(e) => setSubtitle(e.target.value)}
              placeholder="Colonel · SG-1"
              className="mt-1 h-12 w-full rounded-xl border border-border bg-surface px-3"
            />
          </label>
          <label className="block text-sm">
            Résumé
            <textarea
              value={summary}
              onChange={(e) => setSummary(e.target.value)}
              rows={4}
              className="mt-1 w-full rounded-xl border border-border bg-surface px-3 py-2"
            />
          </label>
          <div className="flex gap-2">
            <button type="button" className="h-12 flex-1 rounded-full border border-border" onClick={() => setStep(0)}>
              Retour
            </button>
            <button
              type="button"
              disabled={title.trim().length < 2}
              className="h-12 flex-1 rounded-full bg-primary text-primary-fg disabled:opacity-40"
              onClick={() => setStep(2)}
            >
              Relier un parent
            </button>
          </div>
        </div>
      ) : null}

      {step === 2 ? (
        <div className="mt-8 space-y-4">
          <p className="text-sm text-muted">
            Ce nœud sera l'enfant d'un parent existant — comme Jack sous Richard Dean Anderson.
          </p>
          <input
            value={parentQ}
            onChange={(e) => void lookupParent(e.target.value)}
            placeholder="Chercher Richard Dean Anderson, One Piece, Orion…"
            className="h-12 w-full rounded-xl border border-border bg-surface px-3"
          />
          {parentTitle ? (
            <p className="text-sm text-primary">Parent choisi : {parentTitle}</p>
          ) : null}
          <ul className="space-y-2">
            {hits.map((h) => (
              <li key={h.slug}>
                <button
                  type="button"
                  onClick={() => {
                    setParentSlug(h.slug);
                    setParentTitle(h.title);
                  }}
                  className="w-full rounded-xl bg-surface px-3 py-3 text-left text-sm hover:bg-surface-2"
                >
                  {h.title}
                </button>
              </li>
            ))}
          </ul>
          {error ? <p className="text-sm text-primary">{error}</p> : null}
          <div className="flex gap-2">
            <button type="button" className="h-12 flex-1 rounded-full border border-border" onClick={() => setStep(1)}>
              Retour
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => void submit()}
              className="h-12 flex-1 rounded-full bg-primary text-sm font-medium text-primary-fg disabled:opacity-40"
            >
              {busy ? "Publication…" : "Publier le nœud"}
            </button>
          </div>
          <Link to="/" className="block text-center text-sm text-muted">
            Annuler
          </Link>
        </div>
      ) : null}
    </main>
  );
}
