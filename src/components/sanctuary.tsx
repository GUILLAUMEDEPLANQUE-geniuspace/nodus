import { Link } from "@tanstack/react-router";
import { ArrowUpRight, ChevronUp } from "lucide-react";
import { useMemo, useState } from "react";
import { VideoFiche } from "@/components/video-fiche";
import { KIND_LABEL, type DriveFile, type GraphNode, type NodeUniverse } from "@/lib/graph";
import { initials } from "@/lib/utils";

type Layer = "all" | "souls" | "lore" | "relics" | "time";

type Body =
  | { id: string; layer: "souls"; title: string; kicker: string; text: string; slug: string }
  | { id: string; layer: "lore"; title: string; kicker: string; text: string }
  | { id: string; layer: "relics"; title: string; kicker: string; text: string; file: DriveFile }
  | { id: string; layer: "time"; title: string; kicker: string; text: string };

function realmOf(node: GraphNode) {
  if (
    node.slug === "one-piece" ||
    node.slug === "monkey-d-luffy" ||
    node.slug === "equipage-chapeau-de-paille" ||
    node.slug === "eiichiro-oda"
  )
    return "sanctuary-sea";
  if (
    node.slug.includes("stargate") ||
    node.slug === "sg1" ||
    node.slug === "jack-oneill" ||
    node.slug === "richard-dean-anderson"
  )
    return "sanctuary-gate";
  if (node.slug === "atelier-nocturne" || node.kind === "product") return "sanctuary-atelier";
  return "sanctuary-gold";
}

function gather(u: NodeUniverse): Body[] {
  const souls: Body[] = u.children.map((c) => ({
    id: `soul-${c.id}`,
    layer: "souls",
    title: c.title,
    kicker: KIND_LABEL[c.kind],
    text: c.summary || c.subtitle,
    slug: c.slug,
  }));
  const lore: Body[] = u.wiki.map((w) => ({
    id: `lore-${w.id}`,
    layer: "lore",
    title: w.title,
    kicker: "Lore",
    text: w.body,
  }));
  const relics: Body[] = u.files.map((f) => ({
    id: `relic-${f.id}`,
    layer: "relics",
    title: f.name,
    kicker: `Relique · v${f.version}`,
    text: f.summary || f.transcript,
    file: f,
  }));
  const time: Body[] = u.timeline.map((t) => ({
    id: `time-${t.id}`,
    layer: "time",
    title: t.title,
    kicker: t.yearLabel,
    text: t.body,
  }));
  return [...souls, ...lore, ...relics, ...time];
}

export function Sanctuary({ universe }: { universe: NodeUniverse }) {
  const { node, parents } = universe;
  const bodies = useMemo(() => gather(universe), [universe]);
  const [layer, setLayer] = useState<Layer>(universe.children.length ? "souls" : "all");
  const [focus, setFocus] = useState<string | null>(null);
  const visible = bodies.filter((b) => layer === "all" || b.layer === layer);
  const selected = bodies.find((b) => b.id === focus) ?? null;
  const n = Math.max(visible.length, 1);
  const paused = Boolean(selected);

  return (
    <div className={`${realmOf(node)} relative min-h-[100dvh] overflow-hidden bg-[var(--realm-deep,#07080c)]`}>
      <Starfield />
      <div
        className="pointer-events-none absolute inset-0"
        style={{
          background: `radial-gradient(ellipse at 50% 42%, var(--realm-glow) 0%, transparent 58%)`,
        }}
      />

      {parents[0] ? (
        <Link
          to="/n/$slug"
          params={{ slug: parents[0].slug }}
          search={{ view: "fiche" }}
          className="absolute top-20 left-4 z-20 flex items-center gap-1 text-xs tracking-[0.2em] text-primary/80 uppercase sm:left-6"
        >
          <ChevronUp className="size-4" />
          Remonter · {parents[0].title}
        </Link>
      ) : null}

      <div className="absolute top-28 right-4 left-4 z-10 max-w-md sm:left-6">
        <p className="text-[11px] tracking-[0.28em] text-primary uppercase">{KIND_LABEL[node.kind]}</p>
        <h1 className="mt-1 font-display text-4xl leading-none sm:text-6xl">{node.title}</h1>
        {node.subtitle ? <p className="mt-2 text-sm text-muted">{node.subtitle}</p> : null}
      </div>

      <div className="absolute inset-0 z-[5] flex items-center justify-center">
        <div className="core-breathe grid size-28 place-items-center rounded-full border border-primary/50 bg-bg/80 font-display text-2xl text-primary sm:size-36 sm:text-3xl">
          {initials(node.title)}
        </div>
      </div>

      <div className={`absolute inset-0 z-[6] ${paused ? "orbit-spin is-paused" : "orbit-spin"}`}>
        {visible.map((b, i) => {
          const angle = (i / n) * 360;
          const radius = 38 + (i % 2) * 4;
          const showLabel = selected?.id === b.id || visible.length <= 8;
          return (
            <button
              key={b.id}
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                setFocus((cur) => (cur === b.id ? null : b.id));
              }}
              className="absolute top-1/2 left-1/2 -mt-5 -ml-5"
              style={{
                transform: `rotate(${angle}deg) translateX(min(${radius}vw, 300px)) rotate(${-angle}deg)`,
              }}
            >
              <span
                className={`grid size-10 place-items-center rounded-full border text-[10px] tracking-wide ${
                  selected?.id === b.id
                    ? "border-primary bg-primary text-primary-fg"
                    : "border-primary/40 bg-bg/80 text-primary"
                }`}
              >
                {initials(b.title)}
              </span>
              {showLabel ? (
                <span className="absolute top-11 left-1/2 w-28 -translate-x-1/2 text-center text-[10px] leading-tight text-fg/85">
                  {b.title}
                </span>
              ) : null}
            </button>
          );
        })}
      </div>

      <div className="absolute bottom-0 left-0 z-30 flex w-full flex-col gap-3 p-3 sm:flex-row sm:items-end sm:p-5">
        <LayerBar layer={layer} setLayer={setLayer} counts={{
          souls: bodies.filter((b) => b.layer === "souls").length,
          lore: bodies.filter((b) => b.layer === "lore").length,
          relics: bodies.filter((b) => b.layer === "relics").length,
          time: bodies.filter((b) => b.layer === "time").length,
        }} />
        {selected ? (
          <HoloPanel body={selected} onClose={() => setFocus(null)} />
        ) : (
          <p className="max-w-md px-2 pb-2 text-sm leading-relaxed text-muted">
            {node.summary} Touchez une orbite — âme, lore, relique ou ère — l'univers ne change pas de page, il se
            prolonge.
          </p>
        )}
      </div>
    </div>
  );
}

function LayerBar({
  layer,
  setLayer,
  counts,
}: {
  layer: Layer;
  setLayer: (l: Layer) => void;
  counts: Record<Exclude<Layer, "all">, number>;
}) {
  const items: { id: Layer; label: string }[] = [
    { id: "all", label: "Constellation" },
    { id: "souls", label: `Âmes ${counts.souls}` },
    { id: "lore", label: `Lore ${counts.lore}` },
    { id: "relics", label: `Reliques ${counts.relics}` },
    { id: "time", label: `Temps ${counts.time}` },
  ];
  return (
    <div className="flex max-w-full gap-1 overflow-x-auto rounded-full border border-border bg-bg/70 p-1 backdrop-blur-md">
      {items.map((it) => (
        <button
          key={it.id}
          type="button"
          onClick={() => setLayer(it.id)}
          className={`h-10 shrink-0 rounded-full px-3 text-xs tracking-wide ${
            layer === it.id ? "bg-primary text-primary-fg" : "text-muted"
          }`}
        >
          {it.label}
        </button>
      ))}
    </div>
  );
}

function HoloPanel({ body, onClose }: { body: Body; onClose: () => void }) {
  return (
    <aside className="holo-in max-h-[46dvh] w-full overflow-y-auto rounded-2xl border border-primary/30 bg-bg/85 p-4 shadow-[0_0_80px_rgba(201,163,106,0.12)] backdrop-blur-md sm:max-w-lg">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-[11px] tracking-[0.18em] text-primary uppercase">{body.kicker}</p>
          <h2 className="font-display text-2xl">{body.title}</h2>
        </div>
        <button type="button" onClick={onClose} className="h-10 px-2 text-sm text-muted">
          Fermer
        </button>
      </div>
      <p className="mt-3 text-sm leading-relaxed text-fg/90">{body.text}</p>
      {body.layer === "souls" ? (
        <Link
          to="/n/$slug"
          params={{ slug: body.slug }}
          search={{ view: "fiche" }}
          className="mt-4 inline-flex h-11 items-center gap-2 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
        >
          Plonger dans cet enfant
          <ArrowUpRight className="size-4" />
        </Link>
      ) : null}
      {body.layer === "relics" && body.file.kind === "video" ? (
        <div className="mt-4">
          <VideoFiche
            media={{
              id: 0,
              kind: "video",
              title: body.file.name,
              url: "",
              duration: body.file.sizeLabel,
              genre: "Relique",
              chapters: body.file.chapters,
              transcript: body.file.transcript,
            }}
          />
        </div>
      ) : null}
    </aside>
  );
}

function Starfield() {
  const stars = useMemo(
    () =>
      Array.from({ length: 48 }, (_, i) => ({
        left: (i * 47) % 100,
        top: (i * 23) % 100,
        s: i % 5 === 0 ? 2 : 1,
        o: 0.15 + (i % 4) * 0.12,
      })),
    [],
  );
  return (
    <div className="star-drift pointer-events-none absolute inset-0">
      {stars.map((st, i) => (
        <span
          key={i}
          className="absolute rounded-full bg-fg"
          style={{
            left: `${st.left}%`,
            top: `${st.top}%`,
            width: st.s,
            height: st.s,
            opacity: st.o,
          }}
        />
      ))}
    </div>
  );
}
