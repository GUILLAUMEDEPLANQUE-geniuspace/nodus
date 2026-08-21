/**
 * Holo-Forum — Scroll & Dive.
 * Feed visuel (cartes pleine hauteur) + panneau Legacy SEO / Live Telegram.
 * Google indexe le Legacy (DOM structuré). Le Live est l'éphémère.
 * Ne pas copier un skin TikTok rouge : tokens NODUS (or / encre).
 */
import { Link } from "@tanstack/react-router";
import { Flame, MessageCircle, Share2, Tag, Video, X } from "lucide-react";
import { useMemo, useState } from "react";
import type { ForumReply, LiveLine, ShopProduct, Thread } from "@/lib/graph";
import { postLive, postReply, postThread, promoteReply } from "@/lib/graph-api";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

const FALLBACK_COVERS = [
  "/realms/sea-hero.jpg",
  "/realms/portal-hero.jpg",
  "/realms/luffy.jpg",
  "/realms/studio-hero.jpg",
];

export function HoloForum({
  slug,
  threads,
  replies,
  live,
  products,
  onPosted,
  initialId,
}: {
  slug: string;
  threads: Thread[];
  replies: ForumReply[];
  live: LiveLine[];
  products: ShopProduct[];
  onPosted: (author: string) => void;
  initialId?: string;
}) {
  const topics = threads.filter((t) => t.kind === "forum");
  const [openId, setOpenId] = useState<string | null>(initialId ?? null);
  const [mode, setMode] = useState<"legacy" | "live">("legacy");
  const [draft, setDraft] = useState("");
  const [localTopics, setLocalTopics] = useState(topics);
  const [localReplies, setLocalReplies] = useState(replies);
  const [localLive, setLocalLive] = useState(live);
  const { user } = useCurrentUserState();
  const active = localTopics.find((t) => t.id === openId);
  const top = useMemo(
    () => localReplies.filter((r) => r.threadId === active?.id),
    [localReplies, active],
  );
  const chat = useMemo(
    () => localLive.filter((l) => l.threadId === active?.id),
    [localLive, active],
  );
  const split = Boolean(active);

  async function send() {
    if (!active || !draft.trim()) return;
    try {
      if (mode === "live") {
        const res = await postLive({ data: { threadId: active.id, body: draft.trim() } });
        setLocalLive((cur) => [
          ...cur,
          { id: res.id, threadId: active.id, author: res.author, body: draft.trim(), kind: "text" },
        ]);
        onPosted(res.author);
      } else {
        const res = await postReply({ data: { threadId: active.id, body: draft.trim() } });
        setLocalReplies((cur) => [
          ...cur,
          { id: res.id, threadId: active.id, author: res.author, body: draft.trim() },
        ]);
        onPosted(res.author);
      }
      setDraft("");
    } catch {
      /* auth */
    }
  }

  async function tagProduct(p: ShopProduct) {
    if (!active) return;
    setDraft(`Produit · ${p.title} · ${p.price}`);
  }

  async function splitOut(r: ForumReply) {
    try {
      const res = await promoteReply({
        data: { slug, title: r.body.slice(0, 80), body: r.body },
      });
      setLocalTopics((cur) => [
        {
          id: res.id,
          kind: "forum",
          title: r.body.slice(0, 80),
          author: r.author,
          body: r.body,
          replies: 0,
          cover: FALLBACK_COVERS[0],
          views: 1,
          fires: 0,
        },
        ...cur,
      ]);
      onPosted(r.author);
    } catch {
      /* auth */
    }
  }

  return (
    <div className="relative flex h-[calc(100dvh-5.5rem)] overflow-hidden bg-bg">
      <div
        className={`h-full overflow-y-auto scroll-smooth ${split ? "hidden md:block md:w-[40%] md:border-r md:border-border" : "w-full"}`}
        style={{ scrollSnapType: "y mandatory" }}
      >
        {localTopics.map((t, i) => (
          <article
            key={t.id}
            className="relative flex h-[calc(100dvh-5.5rem)] flex-col justify-end p-6 sm:p-10"
            style={{ scrollSnapAlign: "start" }}
          >
            <img
              src={t.cover || FALLBACK_COVERS[i % FALLBACK_COVERS.length]}
              alt=""
              className="absolute inset-0 size-full object-cover"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-bg via-bg/50 to-bg/10" />
            <div className="relative z-10 flex items-end justify-between gap-4">
              <div className="max-w-xl">
                <p className="text-[11px] tracking-[0.18em] text-primary uppercase">Sujet · {t.author}</p>
                <h2 className="mt-2 font-display text-4xl leading-[0.95] sm:text-5xl">{t.title}</h2>
                <p className="mt-3 text-sm leading-relaxed text-fg/85">{t.body}</p>
                <p className="mt-2 text-xs text-muted">
                  {t.views || 0} vues · {t.fires || 0} feux · {t.replies} réponses
                </p>
              </div>
              <div className="flex flex-col gap-3">
                <span className="grid size-14 place-items-center rounded-full border border-border bg-bg/50 backdrop-blur-md">
                  <Flame className="size-5 text-primary" />
                  <span className="text-[10px]">{t.fires || 0}</span>
                </span>
                <button
                  type="button"
                  onClick={() => setOpenId(t.id)}
                  className="grid size-14 place-items-center rounded-full bg-primary text-primary-fg"
                  aria-label="Discuter"
                >
                  <MessageCircle className="size-5" />
                  <span className="text-[10px]">{t.replies}</span>
                </button>
                <Link
                  to="/n/$slug/t/$tid"
                  params={{ slug, tid: t.id }}
                  search={{ view: "fiche" }}
                  className="grid size-14 place-items-center rounded-full border border-border bg-bg/50 text-fg backdrop-blur-md"
                  aria-label="URL SEO du sujet"
                >
                  <Share2 className="size-5" />
                </Link>
              </div>
            </div>
          </article>
        ))}
        <Composer slug={slug} onCreate={(th) => setLocalTopics((cur) => [th, ...cur])} />
      </div>

      {active ? (
        <aside className="absolute inset-x-0 bottom-0 z-30 flex h-[72%] flex-col border-t border-border bg-bg/90 backdrop-blur-md md:static md:h-full md:w-[60%] md:border-t-0">
          <header className="flex items-center justify-between border-b border-border px-4 py-3">
            <div className="flex gap-4">
              <button
                type="button"
                onClick={() => setMode("legacy")}
                className={`text-sm ${mode === "legacy" ? "text-primary" : "text-muted"}`}
              >
                Top (SEO)
              </button>
              <button
                type="button"
                onClick={() => setMode("live")}
                className={`text-sm ${mode === "live" ? "text-primary" : "text-muted"}`}
              >
                Live
              </button>
            </div>
            <button type="button" onClick={() => setOpenId(null)} className="grid size-10 place-items-center" aria-label="Fermer">
              <X className="size-4" />
            </button>
          </header>
          <div className="flex-1 space-y-4 overflow-y-auto p-4">
            {mode === "legacy" ? (
              top.length ? (
                top.map((r) => (
                  <article key={r.id} className="rounded-2xl bg-surface p-4 shadow-[var(--shadow-border)]">
                    <p className="text-xs tracking-[0.14em] text-primary uppercase">{r.author}</p>
                    <p className="mt-2 text-sm leading-relaxed">{r.body}</p>
                    <button
                      type="button"
                      onClick={() => void splitOut(r)}
                      className="mt-3 text-xs text-primary"
                    >
                      Détacher en nouveau sujet (SEO)
                    </button>
                  </article>
                ))
              ) : (
                <p className="text-sm text-muted">Pas encore de réponse indexable. Écrivez la première.</p>
              )
            ) : (
              chat.map((l) => (
                <div key={l.id} className="flex gap-2">
                  <span className="grid size-8 shrink-0 place-items-center rounded-full bg-surface-2 text-[10px] text-primary">
                    {l.author.slice(0, 2)}
                  </span>
                  <p className="rounded-2xl rounded-bl-sm bg-surface px-3 py-2 text-sm">
                    <span className="mr-2 text-xs text-primary">{l.author}</span>
                    {l.body}
                  </p>
                </div>
              ))
            )}
          </div>
          <div className="border-t border-border p-3">
            {products.length > 0 ? (
              <div className="mb-2 flex gap-2 overflow-x-auto">
                {products.slice(0, 4).map((p) => (
                  <button
                    key={p.id}
                    type="button"
                    onClick={() => void tagProduct(p)}
                    className="flex h-9 shrink-0 items-center gap-1 rounded-full border border-border px-3 text-xs text-muted"
                  >
                    <Tag className="size-3" />
                    {p.title}
                  </button>
                ))}
              </div>
            ) : null}
            <div className="flex gap-2">
              <button type="button" className="grid size-11 place-items-center text-muted" aria-label="Joindre une relique">
                <Video className="size-4" />
              </button>
              <input
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                placeholder={user ? (mode === "live" ? "Live…" : "Réponse Legacy (indexée)") : "Connectez-vous"}
                className="h-11 flex-1 rounded-full border border-border bg-surface px-4 text-sm"
                onKeyDown={(e) => {
                  if (e.key === "Enter") void send();
                }}
              />
              <button
                type="button"
                onClick={() => void send()}
                className="h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
              >
                Envoyer
              </button>
            </div>
          </div>
        </aside>
      ) : null}
    </div>
  );
}

function Composer({ slug, onCreate }: { slug: string; onCreate: (t: Thread) => void }) {
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  async function create() {
    if (!title.trim() || !body.trim()) return;
    try {
      const res = await postThread({ data: { slug, title: title.trim(), body: body.trim() } });
      onCreate({
        id: res.id,
        kind: "forum",
        title: title.trim(),
        author: res.author,
        body: body.trim(),
        replies: 0,
        cover: FALLBACK_COVERS[0],
        views: 1,
        fires: 0,
      });
      setTitle("");
      setBody("");
    } catch {
      /* auth */
    }
  }
  return (
    <div className="flex min-h-[40vh] flex-col justify-center bg-surface px-6 py-10" style={{ scrollSnapAlign: "start" }}>
      <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Nouveau sujet</p>
      <input
        value={title}
        onChange={(e) => setTitle(e.target.value)}
        placeholder="Titre du débat"
        className="mt-3 h-12 rounded-full border border-border bg-bg px-4 text-sm"
      />
      <textarea
        value={body}
        onChange={(e) => setBody(e.target.value)}
        placeholder="Accroche — ce texte est indexé."
        className="mt-2 min-h-24 rounded-2xl border border-border bg-bg p-3 text-sm"
      />
      <button
        type="button"
        onClick={() => void create()}
        className="mt-3 h-11 w-fit rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
      >
        Publier le sujet
      </button>
    </div>
  );
}
