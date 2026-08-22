/**
 * Quêtes persistées (ATS). Chaque choix écrit candidate_answers + step++.
 * Le CV n'est plus la porte. Prod : anti-cheat + timer.
 */
import { useState } from "react";
import type { Quest } from "@/lib/graph";
import { startOrAdvanceCandidate } from "@/lib/platform-api";

export function QuestPath({
  quests,
  slug,
  jobId,
}: {
  quests: Quest[];
  slug: string;
  jobId: string;
}) {
  const [i, setI] = useState(0);
  const [picks, setPicks] = useState<string[]>([]);
  const q = quests[i];
  if (!quests.length) return <p className="text-muted">Aucune quête. Le Studio peut en poser.</p>;
  if (!q) {
    return (
      <div className="rounded-3xl bg-surface p-6">
        <h2 className="font-display text-3xl">Jury</h2>
        <p className="mt-2 text-sm text-muted">Parcours terminé. Vos choix sont dans le vivier recruteur.</p>
        <ul className="mt-4 space-y-2 text-sm">
          {picks.map((p) => (
            <li key={p} className="rounded-xl bg-surface-2 p-3">
              {p}
            </li>
          ))}
        </ul>
      </div>
    );
  }
  return (
    <div>
      <p className="text-[11px] tracking-[0.16em] text-primary uppercase">
        Quête {i + 1} / {quests.length} · {q.skill}
      </p>
      <h2 className="mt-1 font-display text-3xl">{q.title}</h2>
      <p className="mt-3 max-w-xl text-base leading-relaxed">{q.prompt}</p>
      <p className="mt-2 text-sm text-muted">{q.body}</p>
      <div className="mt-6 grid gap-3 sm:grid-cols-2">
        {[q.optionA, q.optionB].map((opt) => (
          <button
            key={opt}
            type="button"
            onClick={() => {
              setPicks((cur) => [...cur, `${q.title} → ${opt}`]);
              setI((n) => n + 1);
              void startOrAdvanceCandidate({
                data: { slug, jobId, questId: q.id, choice: opt },
              }).catch(() => {});
            }}
            className="rounded-2xl border border-border bg-surface p-4 text-left text-sm hover:border-primary"
          >
            {opt}
          </button>
        ))}
      </div>
    </div>
  );
}
