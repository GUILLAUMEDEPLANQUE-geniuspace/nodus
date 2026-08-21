import { Link } from "@tanstack/react-router";
import { KIND_LABEL, type GraphNode } from "@/lib/graph";
import { initials } from "@/lib/utils";

export function NodeCard({ node, note }: { node: GraphNode; note?: string }) {
  return (
    <Link
      to="/n/$slug"
      params={{ slug: node.slug }}
      search={{ view: "fiche" }}
      className="group flex gap-3 rounded-2xl bg-surface p-3 shadow-[var(--shadow-border)] transition-[box-shadow,transform] duration-150 hover:shadow-[var(--shadow-border-hover)]"
    >
      <Glyph title={node.title} />
      <div className="min-w-0">
        <p className="text-[11px] tracking-[0.16em] text-primary uppercase">
          {KIND_LABEL[node.kind]}
        </p>
        <h3 className="font-display text-lg leading-tight text-fg group-hover:text-primary">
          {node.title}
        </h3>
        {node.subtitle ? <p className="truncate text-sm text-muted">{node.subtitle}</p> : null}
        {note ? <p className="mt-1 text-xs text-primary/80">{note}</p> : null}
      </div>
    </Link>
  );
}

export function Glyph({ title, size = "md" }: { title: string; size?: "sm" | "md" | "lg" }) {
  const dim = size === "lg" ? "size-16 text-lg" : size === "sm" ? "size-9 text-xs" : "size-12 text-sm";
  return (
    <span
      className={`grid shrink-0 place-items-center rounded-full border border-primary/35 bg-bg font-display text-primary ${dim}`}
    >
      {initials(title)}
    </span>
  );
}
