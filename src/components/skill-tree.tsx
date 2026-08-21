/**
 * Arbre de compétences RPG : les offres ne sont plus une liste.
 * Chaque nœud = un job du graphe. On débloque selon le profil (ici : lecture).
 */
import { Link } from "@tanstack/react-router";
import type { GraphNode } from "@/lib/graph";

export function SkillTree({ jobs, root }: { jobs: GraphNode[]; root: GraphNode }) {
  const nodes = jobs.length ? jobs : [root];
  return (
    <div>
      <h2 className="font-display text-3xl">Arbre de talents</h2>
      <p className="mt-1 mb-6 text-sm text-muted">
        Constellation d'offres. Cliquez un talent pour ouvrir l'épreuve — pas un job board.
      </p>
      <svg viewBox="0 0 640 280" className="w-full text-primary" aria-label="Arbre de compétences">
        <line x1="320" y1="40" x2="160" y2="160" stroke="currentColor" strokeOpacity="0.35" />
        <line x1="320" y1="40" x2="320" y2="160" stroke="currentColor" strokeOpacity="0.35" />
        <line x1="320" y1="40" x2="480" y2="160" stroke="currentColor" strokeOpacity="0.35" />
        <circle cx="320" cy="40" r="10" fill="currentColor" />
        <text x="320" y="28" textAnchor="middle" fill="currentColor" fontSize="11">
          {root.title}
        </text>
        {nodes.slice(0, 3).map((j, i) => {
          const x = 160 + i * 160;
          return (
            <g key={j.id}>
              <circle cx={x} cy="170" r="8" fill="currentColor" />
              <text x={x} y="200" textAnchor="middle" fill="currentColor" fontSize="12">
                {j.title.slice(0, 28)}
              </text>
            </g>
          );
        })}
      </svg>
      <ul className="mt-4 grid gap-3 sm:grid-cols-2">
        {nodes.map((j) => (
          <li key={j.id}>
            <Link
              to="/n/$slug"
              params={{ slug: j.slug }}
              search={{ view: "fiche" }}
              className="block rounded-2xl bg-surface p-4 shadow-[var(--shadow-border)]"
            >
              <p className="text-[11px] tracking-[0.16em] text-primary uppercase">Talent</p>
              <p className="font-display text-xl">{j.title}</p>
              <p className="mt-1 line-clamp-2 text-sm text-muted">{j.summary}</p>
            </Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
