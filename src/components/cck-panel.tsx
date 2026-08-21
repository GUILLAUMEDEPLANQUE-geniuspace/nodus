/**
 * Rendu générique d'une grappe CCK.
 * Utilisé par boutique, journal, offres, fiches perso — un seul composant.
 */
import type { CckField } from "@/lib/graph";

export function CckPanel({ fields, title }: { fields: CckField[]; title?: string }) {
  if (!fields.length) return null;
  return (
    <section>
      {title ? <p className="mb-2 text-[11px] tracking-[0.16em] text-primary uppercase">{title}</p> : null}
      <dl className="grid gap-3 sm:grid-cols-2">
        {fields.map((f) => (
          <div key={f.id} className="rounded-xl bg-surface-2 p-3">
            <dt className="text-[11px] tracking-[0.14em] text-primary uppercase">
              {f.label}
              <span className="ml-2 text-muted">{f.fieldType}</span>
            </dt>
            <dd className="mt-1 text-sm">{f.value}</dd>
          </div>
        ))}
      </dl>
    </section>
  );
}
