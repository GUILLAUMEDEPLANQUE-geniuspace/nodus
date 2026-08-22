/** Vivier recruteur — steps persistés. Visible staff only. */
import { useEffect, useState } from "react";
import { listCandidates } from "@/lib/platform-api";
import type { Candidate } from "@/lib/platform";

export function PipelineBoard({ slug }: { slug: string }) {
  const [rows, setRows] = useState<Candidate[]>([]);
  const [err, setErr] = useState("");
  useEffect(() => {
    void listCandidates({ data: { slug } })
      .then(setRows)
      .catch((e) => setErr(e instanceof Error ? e.message : "Staff requis"));
  }, [slug]);
  return (
    <div>
      <h2 className="font-display text-3xl">Vivier</h2>
      <p className="mt-1 mb-4 text-sm text-muted">Étapes 1–7. Pas une liste LinkedIn.</p>
      {err ? <p className="text-sm text-muted">{err}</p> : null}
      <ul className="space-y-2">
        {rows.map((c) => (
          <li key={c.id} className="flex justify-between rounded-2xl bg-surface px-4 py-3 text-sm">
            <span>{c.userId.slice(0, 8)}</span>
            <span className="text-primary">
              Étape {c.step}/7 · {c.status}
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
