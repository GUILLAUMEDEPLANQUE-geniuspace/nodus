/**
 * LivingWorld — peau « fans ».
 * Hero ciné + salles (forum, guilde, guides, boutique, studio) + dock bas.
 * Les onglets viennent de `universe.tabs` (table node_tabs), pas d'un menu figé.
 */
import { Link } from "@tanstack/react-router";
import { useEffect, useMemo, useRef, useState } from "react";
import { CckPanel } from "@/components/cck-panel";
import { DriveBrowser } from "@/components/drive-browser";
import { ForumBoard } from "@/components/forum-board";
import { PlaylistDeck } from "@/components/playlist-deck";
import { RealmCanvas } from "@/components/realm-canvas";
import { ShopFloor } from "@/components/shop-floor";
import { StudioPanel } from "@/components/studio-panel";
import { UniverseDock } from "@/components/universe-dock";
import { VideoFiche } from "@/components/video-fiche";
import { WikiGuide } from "@/components/wiki-guide";
import { KIND_LABEL, type GraphNode, type NodeUniverse, type UniverseTab } from "@/lib/graph";
import { heroOf, portraitOf } from "@/lib/skins";
import { postGuildMessage } from "@/lib/graph-api";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

const FALLBACK_TABS: UniverseTab[] = [
  { id: "vivre", key: "vivre", label: "Univers", icon: "compass" },
  { id: "personnages", key: "personnages", label: "Personnages", icon: "users" },
  { id: "forum", key: "forum", label: "Forum", icon: "messages" },
  { id: "journal", key: "journal", label: "Journal", icon: "newspaper" },
  { id: "guilde", key: "guilde", label: "Guilde", icon: "radio" },
  { id: "guides", key: "guides", label: "Guides", icon: "book" },
  { id: "boutique", key: "boutique", label: "Boutique", icon: "store" },
  { id: "reliques", key: "reliques", label: "Studio", icon: "film" },
];

export function LivingWorld({ universe }: { universe: NodeUniverse }) {
  const {
    node,
    children,
    parents,
    wiki,
    threads,
    messages,
    files,
    folders,
    media,
    cck,
    tabs,
    staff,
    categories,
    replies,
    products,
    playlists,
    heroUrl,
  } = universe;
  const dockTabs = tabs.length ? tabs : FALLBACK_TABS;
  const [tab, setTab] = useState(dockTabs[0]?.key ?? "vivre");
  const [bubbles, setBubbles] = useState<{ id: string; author: string }[]>([]);
  const bodyRef = useRef<HTMLDivElement>(null);
  useEffect(() => {
    if (tab !== "vivre") bodyRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  }, [tab]);
  const hero = heroUrl || heroOf(node);
  const souls = children.filter((c) => c.kind === "character" || c.kind === "group" || c.kind === "person");
  const markers = useMemo(
    () =>
      souls
        .map((c) => {
          const image = portraitOf(c.slug);
          return image ? { title: c.title, image } : null;
        })
        .filter(Boolean) as { title: string; image: string }[],
    [souls],
  );
  const forum = threads.filter((t) => t.kind === "forum");
  const journal = threads.filter((t) => t.kind === "blog");
  const videos = media.filter((m) => m.kind === "video");

  function ping(author: string) {
    const id = crypto.randomUUID();
    setBubbles((cur) => [...cur, { id, author }]);
    window.setTimeout(() => setBubbles((cur) => cur.filter((b) => b.id !== id)), 3200);
  }

  return (
    <div className="pb-28">
      <section className="relative h-[78dvh] min-h-[480px] overflow-hidden">
        <img src={hero} alt="" className="absolute inset-0 size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-bg via-bg/40 to-bg/15" />
        <div className="absolute inset-x-0 bottom-0 z-10 mx-auto max-w-6xl px-4 pb-20 sm:px-6">
          {parents[0] ? (
            <Link
              to="/n/$slug"
              params={{ slug: parents[0].slug }}
              search={{ view: "fiche" }}
              className="text-xs tracking-[0.2em] text-primary uppercase"
            >
              Univers parent · {parents[0].title}
            </Link>
          ) : (
            <p className="text-xs tracking-[0.2em] text-primary uppercase">Lieu de vie · {KIND_LABEL[node.kind]}</p>
          )}
          <h1 className="mt-2 max-w-3xl font-display text-5xl leading-[0.92] sm:text-7xl">{node.title}</h1>
          <p className="mt-3 max-w-xl text-base text-fg/90">{node.subtitle || node.summary}</p>
          <div className="mt-5 flex flex-wrap gap-3 pb-3">
            <button
              type="button"
              onClick={() => setTab("personnages")}
              className="h-12 rounded-full bg-primary px-5 text-sm font-medium text-primary-fg"
            >
              Rejoindre l'équipage
            </button>
            <button
              type="button"
              onClick={() => setTab("guilde")}
              className="h-12 rounded-full border border-fg/30 bg-bg/40 px-5 text-sm backdrop-blur-md"
            >
              Entrer dans la guilde
            </button>
          </div>
        </div>
      </section>

      <div ref={bodyRef} className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        {tab === "vivre" ? (
          <div className="space-y-12">
            <p className="max-w-2xl text-lg leading-relaxed text-fg/90">{node.summary}</p>
            {node.body ? <p className="max-w-2xl text-sm leading-relaxed text-muted">{node.body}</p> : null}
            {cck.filter((f) => f.targetKind === "node" || f.targetKind === "").length > 0 ? (
              <CckPanel
                title="Champs CCK"
                fields={cck.filter((f) => f.targetKind === "node" || f.targetKind === "")}
              />
            ) : null}
            {markers.length > 0 ? (
              <div>
                <h2 className="font-display text-3xl">Explorer le monde</h2>
                <p className="mt-1 mb-4 text-sm text-muted">
                  Une carte 3D habitée — les îles sont vos personnages. Ce n'est plus une grille.
                </p>
                <RealmCanvas markers={markers} ocean />
              </div>
            ) : null}
            {souls.length > 0 ? <Roster souls={souls} /> : null}
          </div>
        ) : null}

        {tab === "personnages" ? <Roster souls={souls.length ? souls : children} /> : null}

        {tab === "forum" ? (
          <ForumBoard
            slug={node.slug}
            threads={forum}
            categories={categories}
            replies={replies}
            onPosted={ping}
          />
        ) : null}

        {tab === "journal" ? (
          <Feed
            title="Journal"
            hint="Articles de fond — evergreen du Node. Chaque article a ses champs CCK."
            items={journal}
            cck={cck}
          />
        ) : null}

        {tab === "guilde" ? <Guild slug={node.slug} seed={messages} onPosted={ping} /> : null}

        {tab === "guides" ? <WikiGuide pages={wiki} /> : null}

        {tab === "boutique" ? <ShopFloor slug={node.slug} products={products} cck={cck} /> : null}

        {tab === "reliques" ? (
          <div className="space-y-10">
            <PlaylistDeck slug={node.slug} playlists={playlists} />
            {videos.length > 0 ? (
              <div className="grid gap-4 lg:grid-cols-2">
                {videos.map((m) => (
                  <VideoFiche key={m.id} media={m} />
                ))}
              </div>
            ) : null}
            <DriveBrowser folders={folders} files={files} slug={node.slug} />
          </div>
        ) : null}

        {tab === "studio" ? (
          <StudioPanel slug={node.slug} tabs={dockTabs} staff={staff} files={files} />
        ) : null}
      </div>

      <UniverseDock
        tabs={dockTabs}
        active={tab}
        onSelect={setTab}
        onStudio={() => setTab("studio")}
        bubbles={bubbles}
      />
    </div>
  );
}

function Roster({ souls }: { souls: GraphNode[] }) {
  if (!souls.length) return <p className="text-muted">Pas encore d'âmes dans cet univers.</p>;
  return (
    <div>
      <h2 className="font-display text-3xl">Ceux qui habitent ici</h2>
      <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {souls.map((c) => {
          const img = portraitOf(c.slug);
          return (
            <Link
              key={c.id}
              to="/n/$slug"
              params={{ slug: c.slug }}
              search={{ view: "fiche" }}
              className="group overflow-hidden rounded-3xl bg-surface shadow-[var(--shadow-border)]"
            >
              <div className="aspect-[3/4] overflow-hidden bg-surface-2">
                {img ? (
                  <img src={img} alt="" className="size-full object-cover transition duration-300 group-hover:scale-105" />
                ) : (
                  <div className="grid size-full place-items-center font-display text-4xl text-primary">{c.title[0]}</div>
                )}
              </div>
              <div className="p-4">
                <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{KIND_LABEL[c.kind]}</p>
                <h3 className="font-display text-2xl">{c.title}</h3>
                <p className="mt-1 line-clamp-2 text-sm text-muted">{c.subtitle || c.summary}</p>
              </div>
            </Link>
          );
        })}
      </div>
    </div>
  );
}

function Feed({
  title,
  hint,
  items,
  cck = [],
}: {
  title: string;
  hint: string;
  items: NodeUniverse["threads"];
  cck?: NodeUniverse["cck"];
}) {
  const [open, setOpen] = useState<string | null>(items[0]?.id ?? null);
  const active = items.find((t) => t.id === open) ?? items[0];
  if (!items.length) return <p className="text-muted">La guilde n'a pas encore écrit ici.</p>;
  return (
    <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
      <div>
        <h2 className="font-display text-3xl">{title}</h2>
        <p className="mt-1 mb-4 text-sm text-muted">{hint}</p>
        <ul className="space-y-1">
          {items.map((t) => (
            <li key={t.id}>
              <button
                type="button"
                onClick={() => setOpen(t.id)}
                className={`w-full rounded-xl px-3 py-3 text-left ${open === t.id ? "bg-surface" : ""}`}
              >
                <span className="block text-sm text-fg">{t.title}</span>
                <span className="text-xs text-muted">
                  {t.author} · {t.replies} réponses
                </span>
              </button>
            </li>
          ))}
        </ul>
      </div>
      {active ? (
        <article className="rounded-3xl bg-surface p-6 shadow-[var(--shadow-border)]">
          <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{active.author}</p>
          <h3 className="mt-1 font-display text-3xl">{active.title}</h3>
          <p className="mt-4 text-base leading-relaxed text-fg/90">{active.body}</p>
          <div className="mt-4">
            <CckPanel fields={cck.filter((f) => f.targetKind === "thread" && f.targetId === active.id)} />
          </div>
        </article>
      ) : null}
    </div>
  );
}

function Guild({
  slug,
  seed,
  onPosted,
}: {
  slug: string;
  seed: NodeUniverse["messages"];
  onPosted: (author: string) => void;
}) {
  const { user } = useCurrentUserState();
  const [lines, setLines] = useState(seed);
  const [text, setText] = useState("");
  const [busy, setBusy] = useState(false);

  async function send() {
    if (!text.trim() || busy) return;
    setBusy(true);
    try {
      const res = await postGuildMessage({ data: { slug, body: text.trim() } });
      setLines((cur) => [...cur, { id: res.id, author: res.author, body: text.trim() }]);
      onPosted(res.author);
      setText("");
    } catch {
      setLines((cur) => [...cur, { id: crypto.randomUUID(), author: "vous", body: text.trim() }]);
      onPosted("vous");
      setText("");
    }
    setBusy(false);
  }

  return (
    <div className="mx-auto max-w-xl">
      <h2 className="font-display text-3xl">Guilde</h2>
      <p className="mt-1 mb-4 text-sm text-muted">Comme un canal Telegram, collé à l'univers — pas une app à côté.</p>
      <div className="space-y-3 rounded-3xl bg-surface p-4 shadow-[var(--shadow-border)]">
        {lines.map((m) => (
          <div key={m.id} className="flex gap-3">
            <span className="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-surface-2 text-xs text-primary">
              {m.author.slice(0, 2)}
            </span>
            <div>
              <p className="text-xs text-primary">{m.author}</p>
              <p className="text-sm text-fg">{m.body}</p>
            </div>
          </div>
        ))}
      </div>
      <div className="mt-3 flex gap-2">
        <input
          value={text}
          onChange={(e) => setText(e.target.value)}
          placeholder={user ? "Écrire dans la guilde…" : "Connectez-vous pour écrire"}
          className="h-12 flex-1 rounded-full border border-border bg-surface px-4 text-sm"
          onKeyDown={(e) => {
            if (e.key === "Enter") void send();
          }}
        />
        <button
          type="button"
          onClick={() => void send()}
          className="h-12 rounded-full bg-primary px-5 text-sm font-medium text-primary-fg"
        >
          Envoyer
        </button>
      </div>
    </div>
  );
}
