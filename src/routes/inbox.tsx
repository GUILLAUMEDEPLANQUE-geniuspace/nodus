/**
 * DM type Telegram — un canal par univers + 1-1.
 * Pas de WS encore : POST + reload. Prod = socket du Live forum.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { RedirectToSignIn } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { listInbox, sendDm } from "@/lib/platform-api";
import type { DmMessage } from "@/lib/platform";

export const Route = createFileRoute("/inbox")({ component: InboxPage });

function InboxPage() {
  const { user, isPending } = useCurrentUserState();
  const [threads, setThreads] = useState<{ id: string; title: string }[]>([]);
  const [messages, setMessages] = useState<DmMessage[]>([]);
  const [active, setActive] = useState<string | null>(null);
  const [body, setBody] = useState("");

  async function load() {
    const res = await listInbox();
    setThreads(res.threads);
    setMessages(res.messages);
    setActive((cur) => cur ?? res.threads[0]?.id ?? null);
  }

  useEffect(() => {
    if (user) void load().catch(() => {});
  }, [user]);

  if (isPending) return <div className="px-4 py-16 text-muted">Inbox…</div>;
  if (!user) return <RedirectToSignIn to="/login" />;

  const mine = messages.filter((m) => m.threadId === active);

  async function send() {
    if (!active || !body.trim()) return;
    await sendDm({ data: { threadId: active, body: body.trim() } });
    setBody("");
    await load();
  }

  return (
    <main className="mx-auto grid max-w-5xl gap-4 px-4 py-10 md:grid-cols-[220px_1fr]">
      <aside>
        <h1 className="font-display text-3xl">Messages</h1>
        <ul className="mt-4 space-y-1">
          {threads.map((t) => (
            <li key={t.id}>
              <button
                type="button"
                onClick={() => setActive(t.id)}
                className={`w-full rounded-xl px-3 py-2 text-left text-sm ${active === t.id ? "bg-surface text-primary" : "text-muted"}`}
              >
                {t.title}
              </button>
            </li>
          ))}
        </ul>
      </aside>
      <section className="rounded-3xl bg-surface p-4">
        <div className="min-h-64 space-y-2">
          {mine.map((m) => (
            <p key={m.id} className="rounded-2xl bg-bg px-3 py-2 text-sm">
              <span className="text-xs text-primary">{m.authorId.slice(0, 8)} · </span>
              {m.body}
            </p>
          ))}
        </div>
        <div className="mt-3 flex gap-2">
          <input
            value={body}
            onChange={(e) => setBody(e.target.value)}
            className="h-11 flex-1 rounded-full border border-border bg-bg px-4 text-sm"
            placeholder="Écrire…"
            onKeyDown={(e) => {
              if (e.key === "Enter") void send();
            }}
          />
          <button type="button" onClick={() => void send()} className="h-11 rounded-full bg-primary px-4 text-sm text-primary-fg">
            Envoyer
          </button>
        </div>
      </section>
    </main>
  );
}
