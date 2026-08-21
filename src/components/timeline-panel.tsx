import type { TimelineEvent } from "@/lib/graph";

export function TimelinePanel({ events }: { events: TimelineEvent[] }) {
  if (!events.length) {
    return (
      <div className="rounded-2xl bg-surface p-8 shadow-[var(--shadow-border)]">
        <p className="font-display text-2xl">Pas encore de chronologie</p>
        <p className="mt-2 text-sm text-muted">Les univers (manga, séries) portent leurs arcs ici, liés aux enfants.</p>
      </div>
    );
  }

  return (
    <ol className="relative space-y-6 border-l border-primary/30 pl-6">
      {events.map((e) => (
        <li key={e.id} className="relative">
          <span className="absolute top-1.5 -left-[31px] size-3 rounded-full bg-primary" />
          <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{e.yearLabel}</p>
          <h3 className="font-display text-2xl text-fg">{e.title}</h3>
          <p className="mt-1 text-sm leading-relaxed text-muted">{e.body}</p>
        </li>
      ))}
    </ol>
  );
}
