/** 7 étapes ATS configurables. Owner/admin. */
import { useState } from "react";
import type { AtsStep } from "@/lib/graph";
import { saveAtsStep } from "@/lib/platform-api";

export function AtsStudio({ slug, steps }: { slug: string; steps: AtsStep[] }) {
  const [list, setList] = useState(
    steps.length
      ? steps
      : Array.from({ length: 7 }, (_, i) => ({ id: `n${i}`, step: i + 1, title: `Étape ${i + 1}`, body: "" })),
  );

  return (
    <section>
      <h3 className="font-display text-2xl">Parcours 7 étapes</h3>
      <p className="mb-3 text-sm text-muted">Le recruteur écrit le rituel. Le candidat le vit.</p>
      <ol className="space-y-2">
        {list.map((s, i) => (
          <li key={s.step} className="grid gap-2 rounded-2xl bg-surface p-3 sm:grid-cols-[2rem_1fr_1fr_auto]">
            <span className="font-mono text-primary">{s.step}</span>
            <input
              value={s.title}
              onChange={(e) =>
                setList((cur) => cur.map((x, j) => (j === i ? { ...x, title: e.target.value } : x)))
              }
              className="h-10 rounded-xl border border-border bg-bg px-3 text-sm"
            />
            <input
              value={s.body}
              onChange={(e) =>
                setList((cur) => cur.map((x, j) => (j === i ? { ...x, body: e.target.value } : x)))
              }
              className="h-10 rounded-xl border border-border bg-bg px-3 text-sm"
            />
            <button
              type="button"
              onClick={() => void saveAtsStep({ data: { slug, step: s.step, title: s.title, body: s.body } })}
              className="h-10 rounded-full border border-primary/40 px-3 text-xs text-primary"
            >
              Sauver
            </button>
          </li>
        ))}
      </ol>
    </section>
  );
}
