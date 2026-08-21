import type { GraphNode } from "@/lib/graph";
import { initials } from "@/lib/utils";

export function Constellation({
  nodes,
  edges,
}: {
  nodes: GraphNode[];
  edges: { from_id: string; to_id: string }[];
}) {
  const w = 800;
  const h = nodes.length > 14 ? 520 : 420;
  const cx = w / 2;
  const cy = h / 2;
  const n = Math.max(nodes.length, 1);
  const pos = new Map<string, { x: number; y: number }>();
  const showLabels = nodes.length <= 14;
  nodes.forEach((node, i) => {
    const a = (i / n) * Math.PI * 2 - Math.PI / 2;
    const r = (nodes.length > 14 ? 90 : 70) + (i % 3) * 55;
    pos.set(node.id, {
      x: Math.round(cx + Math.cos(a) * r * 1.55),
      y: Math.round(cy + Math.sin(a) * r * 0.92),
    });
  });

  return (
    <div className="overflow-hidden rounded-3xl border border-border bg-surface/60">
      <svg viewBox={`0 0 ${w} ${h}`} className="h-auto w-full" role="img" aria-label="Constellation des nœuds">
        <rect width={w} height={h} fill="#08090e" />
        {Array.from({ length: 40 }).map((_, i) => (
          <circle
            key={i}
            cx={(i * 97) % w}
            cy={(i * 53) % h}
            r={i % 5 === 0 ? 1.2 : 0.6}
            fill="#efe8d8"
            opacity={0.15 + (i % 4) * 0.08}
          />
        ))}
        {edges.map((e, i) => {
          const a = pos.get(e.from_id);
          const b = pos.get(e.to_id);
          if (!a || !b) return null;
          return (
            <line
              key={`${e.from_id}-${e.to_id}-${i}`}
              x1={a.x}
              y1={a.y}
              x2={b.x}
              y2={b.y}
              stroke="#c9a36a"
              strokeOpacity={0.35}
              strokeWidth={1}
            />
          );
        })}
        {nodes.map((node) => {
          const p = pos.get(node.id);
          if (!p) return null;
          return (
            <a key={node.id} href={`/n/${node.slug}`}>
              <g>
                <circle cx={p.x} cy={p.y} r={18} fill="#12141c" stroke="#c9a36a" strokeWidth={1.2} />
                <text
                  x={p.x}
                  y={p.y + 1}
                  textAnchor="middle"
                  dominantBaseline="middle"
                  fill="#c9a36a"
                  fontSize={9}
                  fontFamily="Outfit, sans-serif"
                >
                  {initials(node.title)}
                </text>
                {showLabels ? (
                  <text
                    x={p.x}
                    y={p.y + 32}
                    textAnchor="middle"
                    fill="#efe8d8"
                    fontSize={10}
                    fontFamily="Outfit, sans-serif"
                  >
                    {node.title.length > 18 ? `${node.title.slice(0, 16)}…` : node.title}
                  </text>
                ) : (
                  <title>{node.title}</title>
                )}
              </g>
            </a>
          );
        })}
      </svg>
    </div>
  );
}
