/**
 * Builder visuel CCK (JoomCCK-grade).
 * Palette de types → canvas de champs → preview live.
 * Un univers unique = des champs uniques, pas un template figé.
 */
import { useState } from "react";
import { CckPanel } from "@/components/cck-panel";
import type { CckField } from "@/lib/graph";
import { CCK_FIELD_TYPES, CCK_TARGETS, type CckFieldType, type CckTarget } from "@/lib/cck";
import { addCckField } from "@/lib/graph-api";

const TYPE_HINT: Record<CckFieldType, string> = {
  text: "Chaîne courte (SKU, titre, salaire)",
  html: "Corps riche (guide, blog)",
  choice: "Enum / tags",
  media: "Fichier Drive",
  relation: "Lien vers un autre Node",
  scale: "Nombre (stock, prime)",
};

export function CckBuilder({ slug, fields }: { slug: string; fields: CckField[] }) {
  const [list, setList] = useState(fields);
  const [label, setLabel] = useState("");
  const [value, setValue] = useState("");
  const [fieldType, setFieldType] = useState<CckFieldType>("text");
  const [targetKind, setTargetKind] = useState<CckTarget>("node");

  async function add() {
    if (!label.trim()) return;
    const key = label
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .slice(0, 40);
    try {
      const res = await addCckField({
        data: {
          slug,
          label: label.trim(),
          key,
          fieldType,
          targetKind,
          value: value.trim(),
        },
      });
      setList((cur) => [
        ...cur,
        {
          id: res.id,
          key,
          label: label.trim(),
          value: value.trim(),
          fieldType,
          targetKind,
          targetId: "",
        },
      ]);
      setLabel("");
      setValue("");
    } catch {
      /* auth */
    }
  }

  return (
    <section className="space-y-6">
      <div>
        <h3 className="font-display text-2xl">Builder CCK</h3>
        <p className="mt-1 text-sm text-muted">
          Cliquez un type, nommez le champ, posez-le. Boutique, blog, offre, perso : même moteur.
        </p>
      </div>
      <div className="grid gap-3 sm:grid-cols-3">
        {CCK_FIELD_TYPES.map((t) => (
          <button
            key={t}
            type="button"
            onClick={() => setFieldType(t)}
            className={`rounded-2xl border p-3 text-left ${
              fieldType === t ? "border-primary bg-surface" : "border-border bg-surface-2"
            }`}
          >
            <p className="text-sm text-fg">{t}</p>
            <p className="mt-1 text-xs text-muted">{TYPE_HINT[t]}</p>
          </button>
        ))}
      </div>
      <div className="flex flex-wrap gap-2">
        <input
          value={label}
          onChange={(e) => setLabel(e.target.value)}
          placeholder="Nom du champ"
          className="h-11 flex-1 rounded-full border border-border bg-surface px-4 text-sm"
        />
        <input
          value={value}
          onChange={(e) => setValue(e.target.value)}
          placeholder="Valeur"
          className="h-11 flex-1 rounded-full border border-border bg-surface px-4 text-sm"
        />
        <select
          value={targetKind}
          onChange={(e) => setTargetKind(e.target.value as CckTarget)}
          className="h-11 rounded-full border border-border bg-surface px-3 text-sm"
        >
          {CCK_TARGETS.map((t) => (
            <option key={t} value={t}>
              {t}
            </option>
          ))}
        </select>
        <button
          type="button"
          onClick={() => void add()}
          className="h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
        >
          Poser le champ
        </button>
      </div>
      <CckPanel title="Aperçu live" fields={list} />
    </section>
  );
}
