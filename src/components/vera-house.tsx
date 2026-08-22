/**
 * VeraHouse — peau recruteur (Vera / CCK / épreuve 7 étapes / Drive).
 * Ne pas réutiliser LivingWorld ici : un ATS n'est pas une guilde manga.
 */
import { Link } from "@tanstack/react-router";
import { useState } from "react";
import { CckPanel } from "@/components/cck-panel";
import { DriveBrowser } from "@/components/drive-browser";
import { HoloForum } from "@/components/holo-forum";
import { PipelineBoard } from "@/components/pipeline-board";
import { QuestPath } from "@/components/quest-path";
import { SalonMap } from "@/components/salon-map";
import { SkillTree } from "@/components/skill-tree";
import { StudioPanel } from "@/components/studio-panel";
import { UniverseDock } from "@/components/universe-dock";
import { VideoStudio } from "@/components/video-studio";
import { KIND_LABEL, type NodeUniverse, type UniverseTab } from "@/lib/graph";
import { heroOf } from "@/lib/skins";

const VERA_TABS: UniverseTab[] = [
  { id: "maison", key: "maison", label: "Maison", icon: "building" },
  { id: "salon", key: "salon", label: "Salon", icon: "map" },
  { id: "arbre", key: "arbre", label: "Arbre", icon: "tree" },
  { id: "offres", key: "offres", label: "Offres", icon: "briefcase" },
  { id: "epreuve", key: "epreuve", label: "Quêtes", icon: "list" },
  { id: "drive", key: "drive", label: "Drive", icon: "folder" },
  { id: "academie", key: "academie", label: "Académie", icon: "book" },
  { id: "forum", key: "forum", label: "Forum", icon: "messages" },
  { id: "videos", key: "videos", label: "Vidéos", icon: "film" },
];

function uniqueVera(tabs: UniverseTab[]) {
  const seen = new Set<string>();
  return tabs.filter((t) => {
    const stem = t.key.toLowerCase().replace(/s$/, "");
    if (seen.has(stem)) return false;
    seen.add(stem);
    return true;
  });
}

export function VeraHouse({ universe }: { universe: NodeUniverse }) {
  const { node, children, threads, messages, files, folders, cck, wiki, tabs, staff, categories, replies, live, quests, rooms, products, media, videoAssets, videoNews, seo, atsSteps } =
    universe;
  const dockTabs = uniqueVera(tabs.length ? tabs : VERA_TABS);
  const [tab, setTab] = useState(node.kind === "job" ? "offres" : "maison");
  const [bubbles, setBubbles] = useState<{ id: string; author: string }[]>([]);
  const jobs = children.filter((c) => c.kind === "job");
  const hero = heroOf(node);
  const forum = threads.filter((t) => t.kind === "forum");

  function ping(author: string) {
    const id = crypto.randomUUID();
    setBubbles((cur) => [...cur, { id, author }]);
    window.setTimeout(() => setBubbles((cur) => cur.filter((b) => b.id !== id)), 3200);
  }

  return (
    <div className="pb-28">
      {tab === "forum" ? (
        <HoloForum
          slug={node.slug}
          threads={threads}
          replies={replies}
          live={live}
          products={products}
          onPosted={ping}
        />
      ) : tab === "videos" ? (
        <VideoStudio
          slug={node.slug}
          videos={media}
          assets={videoAssets}
          news={videoNews}
          products={products}
        />
      ) : (
        <>
      <section className="relative h-[52dvh] min-h-[360px] overflow-hidden">
        <img src={hero} alt="" className="absolute inset-0 size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-bg via-bg/50 to-transparent" />
        <div className="absolute inset-x-0 bottom-0 mx-auto max-w-6xl px-4 pb-8 sm:px-6">
          <p className="text-xs tracking-[0.2em] text-primary uppercase">Maison de recrutement</p>
          <h1 className="mt-2 font-display text-5xl sm:text-6xl">{node.title}</h1>
          <p className="mt-2 max-w-xl text-sm text-fg/90">{node.subtitle}</p>
        </div>
      </section>

      <div className="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        {tab === "maison" ? (
          <div className="grid gap-8 lg:grid-cols-[1fr_280px]">
            <div>
              <p className="max-w-2xl text-lg leading-relaxed">{node.summary}</p>
              <p className="mt-4 max-w-2xl text-sm text-muted">{node.body}</p>
              {wiki.map((w) => (
                <article key={w.id} className="mt-6 rounded-2xl bg-surface p-5 shadow-[var(--shadow-border)]">
                  <h3 className="font-display text-2xl">{w.title}</h3>
                  <p className="mt-2 text-sm text-muted">{w.body}</p>
                </article>
              ))}
            </div>
            <aside className="space-y-3">
              <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Canal maison</p>
              {messages.map((m) => (
                <p key={m.id} className="rounded-xl bg-surface p-3 text-sm">
                  <span className="text-primary">{m.author} · </span>
                  {m.body}
                </p>
              ))}
            </aside>
          </div>
        ) : null}

        {tab === "salon" ? <SalonMap rooms={rooms} /> : null}

        {tab === "arbre" ? <SkillTree jobs={jobs} root={node} /> : null}

        {tab === "offres" ? (
          <div className="space-y-8">
            <SkillTree jobs={jobs} root={node} />
            {(jobs.length ? jobs : [node]).map((j) => (
              <article key={j.id} className="rounded-3xl bg-surface p-6 shadow-[var(--shadow-border)]">
                <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{KIND_LABEL[j.kind]}</p>
                <h3 className="font-display text-3xl">{j.title}</h3>
                <p className="mt-2 text-sm text-muted">{j.summary}</p>
                <div className="mt-4">
                  <CckPanel fields={j.id === node.id ? cck : cck.filter((f) => f.targetKind === "node")} />
                </div>
                {j.slug !== node.slug ? (
                  <Link
                    to="/n/$slug"
                    params={{ slug: j.slug }}
                    search={{ view: "fiche" }}
                    className="mt-4 inline-flex h-11 items-center rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
                  >
                    Ouvrir l'épreuve
                  </Link>
                ) : null}
              </article>
            ))}
          </div>
        ) : null}

        {tab === "epreuve" ? (
          <QuestPath quests={quests} slug={node.slug} jobId={jobs[0]?.id ?? node.id} />
        ) : null}

        {tab === "drive" ? <DriveBrowser folders={folders} files={files} slug={node.slug} /> : null}

        {tab === "academie" ? (
          <div>
            <h2 className="font-display text-3xl">Académie</h2>
            <p className="mt-2 text-sm text-muted">Parcours salariés, inscriptions, même Node que le vivier.</p>
            <div className="mt-4 space-y-3">
              {forum.map((t) => (
                <article key={t.id} className="rounded-2xl bg-surface p-5">
                  <h3 className="font-display text-2xl">{t.title}</h3>
                  <p className="mt-2 text-sm text-muted">{t.body}</p>
                </article>
              ))}
            </div>
          </div>
        ) : null}

        {tab === "studio" ? (
          <div className="space-y-10">
            <PipelineBoard slug={node.slug} />
            <StudioPanel slug={node.slug} tabs={dockTabs} staff={staff} files={files} cck={cck} seo={seo} atsSteps={atsSteps} />
          </div>
        ) : null}
      </div>
        </>
      )}

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
