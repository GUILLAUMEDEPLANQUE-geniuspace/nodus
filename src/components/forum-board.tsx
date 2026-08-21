/**
 * Forum d'univers (wpForo-grade, collé au Node).
 * Catégories + sujets + réponses. Poster émet une bulle depuis le dock.
 */
import { useState } from "react";
import type { ForumCategory, ForumReply, Thread } from "@/lib/graph";
import { postReply, postThread } from "@/lib/graph-api";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

export function ForumBoard({
  slug,
  threads,
  categories,
  replies,
  onPosted,
}: {
  slug: string;
  threads: Thread[];
  categories: ForumCategory[];
  replies: ForumReply[];
  onPosted: (author: string) => void;
}) {
  const { user } = useCurrentUserState();
  const [openId, setOpenId] = useState(threads[0]?.id ?? null);
  const [title, setTitle] = useState("");
  const [body, setBody] = useState("");
  const [reply, setReply] = useState("");
  const [local, setLocal] = useState(threads);
  const [localReplies, setLocalReplies] = useState(replies);
  const active = local.find((t) => t.id === openId) ?? local[0];
  const activeReplies = localReplies.filter((r) => r.threadId === active?.id);

  async function create() {
    if (!title.trim() || !body.trim()) return;
    try {
      const res = await postThread({ data: { slug, title: title.trim(), body: body.trim() } });
      setLocal((cur) => [
        { id: res.id, kind: "forum", title: title.trim(), author: res.author, body: body.trim(), replies: 0, cover: "", views: 1, fires: 0 },
        ...cur,
      ]);
      setOpenId(res.id);
      onPosted(res.author);
      setTitle("");
      setBody("");
    } catch {
      /* auth gate */
    }
  }

  async function sendReply() {
    if (!active || !reply.trim()) return;
    try {
      const res = await postReply({ data: { threadId: active.id, body: reply.trim() } });
      setLocalReplies((cur) => [...cur, { id: res.id, threadId: active.id, author: res.author, body: reply.trim() }]);
      onPosted(res.author);
      setReply("");
    } catch {
      /* auth gate */
    }
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[260px_1fr]">
      <aside>
        <h2 className="font-display text-3xl">Forum</h2>
        <p className="mt-1 mb-4 text-sm text-muted">Catégories, modos, réponses — le Node se discute ici.</p>
        {categories.map((c) => (
          <div key={c.id} className="mb-3 rounded-2xl bg-surface p-3">
            <p className="text-sm text-fg">{c.title}</p>
            <p className="text-xs text-muted">{c.body}</p>
          </div>
        ))}
      </aside>
      <div className="space-y-4">
        <ul className="divide-y divide-border overflow-hidden rounded-2xl bg-surface">
          {local.map((t) => (
            <li key={t.id}>
              <button
                type="button"
                onClick={() => setOpenId(t.id)}
                className={`flex w-full flex-col px-4 py-3 text-left ${openId === t.id ? "bg-surface-2" : ""}`}
              >
                <span className="text-sm text-fg">{t.title}</span>
                <span className="text-xs text-muted">
                  {t.author} · {t.replies} réponses
                </span>
              </button>
            </li>
          ))}
        </ul>
        {active ? (
          <article className="rounded-3xl bg-surface p-5 shadow-[var(--shadow-border)]">
            <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{active.author}</p>
            <h3 className="font-display text-2xl">{active.title}</h3>
            <p className="mt-3 text-sm leading-relaxed">{active.body}</p>
            <div className="mt-5 space-y-3 border-t border-border pt-4">
              {activeReplies.map((r) => (
                <p key={r.id} className="text-sm">
                  <span className="text-primary">{r.author} · </span>
                  {r.body}
                </p>
              ))}
            </div>
            <div className="mt-4 flex gap-2">
              <input
                value={reply}
                onChange={(e) => setReply(e.target.value)}
                placeholder={user ? "Répondre…" : "Connectez-vous pour répondre"}
                className="h-11 flex-1 rounded-full border border-border bg-bg px-4 text-sm"
              />
              <button
                type="button"
                onClick={() => void sendReply()}
                className="h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
              >
                Répondre
              </button>
            </div>
          </article>
        ) : null}
        <div className="rounded-3xl border border-dashed border-primary/30 p-4">
          <p className="text-sm text-muted">Nouveau sujet</p>
          <input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Titre"
            className="mt-2 h-11 w-full rounded-full border border-border bg-surface px-4 text-sm"
          />
          <textarea
            value={body}
            onChange={(e) => setBody(e.target.value)}
            placeholder="Votre preuve, théorie, rapport…"
            className="mt-2 min-h-24 w-full rounded-2xl border border-border bg-surface p-3 text-sm"
          />
          <button
            type="button"
            onClick={() => void create()}
            className="mt-2 h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
          >
            Publier
          </button>
        </div>
      </div>
    </div>
  );
}
