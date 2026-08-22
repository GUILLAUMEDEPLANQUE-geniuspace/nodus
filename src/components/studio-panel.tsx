/**
 * Studio du créateur : décorer (hero via Drive), onglets, staff (admin/mod).
 * Chaque univers (manga, recruteur, série, business) se configure ici — pas un thème figé.
 */
import { useState } from "react";
import { CckBuilder } from "@/components/cck-builder";
import { SeoStudio } from "@/components/seo-studio";
import type { CckField, DriveFile, NodeSeo, StaffMember, UniverseTab } from "@/lib/graph";
import { addStaffMember, addUniverseTab } from "@/lib/graph-api";

export function StudioPanel({
  slug,
  tabs,
  staff,
  files,
  cck = [],
  seo = null,
}: {
  slug: string;
  tabs: UniverseTab[];
  staff: StaffMember[];
  files: DriveFile[];
  cck?: CckField[];
  seo?: NodeSeo | null;
}) {
  const [label, setLabel] = useState("");
  const [name, setName] = useState("");
  const [role, setRole] = useState<"admin" | "mod" | "member">("mod");
  const [list, setList] = useState(tabs);
  const [crew, setCrew] = useState(staff);

  async function addTab() {
    if (!label.trim()) return;
    const key = label
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/[^a-z0-9]+/g, "-");
    try {
      const res = await addUniverseTab({ data: { slug, label: label.trim(), tabKey: key, icon: "sparkles" } });
      setList((cur) => [...cur, { id: res.id, key, label: label.trim(), icon: "sparkles" }]);
      setLabel("");
    } catch {
      /* auth */
    }
  }

  async function addStaff() {
    if (!name.trim()) return;
    try {
      const res = await addStaffMember({ data: { slug, name: name.trim(), role } });
      setCrew((cur) => [...cur, { id: res.id, name: name.trim(), role }]);
      setName("");
    } catch {
      /* auth */
    }
  }

  return (
    <div className="space-y-10">
      <div>
        <h2 className="font-display text-3xl">Studio</h2>
        <p className="mt-1 max-w-xl text-sm text-muted">
          Tout se décore. Onglets, icônes, admins, modos. Un recruteur n'a pas les mêmes salles qu'un
          fan de manga — c'est vous qui posez le plan.
        </p>
      </div>
      <section>
        <h3 className="font-display text-2xl">Onglets du dock</h3>
        <ul className="mt-3 space-y-2">
          {list.map((t) => (
            <li key={t.id} className="flex h-11 items-center justify-between rounded-xl bg-surface px-4 text-sm">
              <span>{t.label}</span>
              <span className="text-xs text-muted">{t.icon}</span>
            </li>
          ))}
        </ul>
        <div className="mt-3 flex gap-2">
          <input
            value={label}
            onChange={(e) => setLabel(e.target.value)}
            placeholder="Nouvel onglet (ex. Wiki îles)"
            className="h-11 flex-1 rounded-full border border-border bg-surface px-4 text-sm"
          />
          <button
            type="button"
            onClick={() => void addTab()}
            className="h-11 rounded-full bg-primary px-4 text-sm font-medium text-primary-fg"
          >
            Ajouter
          </button>
        </div>
      </section>
      <section>
        <h3 className="font-display text-2xl">Gardiens</h3>
        <p className="text-sm text-muted">Admin, modo, membre — ils font vivre le Node sans vous.</p>
        <ul className="mt-3 space-y-2">
          {crew.map((s) => (
            <li key={s.id} className="flex h-11 items-center justify-between rounded-xl bg-surface px-4 text-sm">
              <span>{s.name}</span>
              <span className="text-xs tracking-[0.14em] text-primary uppercase">{s.role}</span>
            </li>
          ))}
        </ul>
        <div className="mt-3 flex flex-wrap gap-2">
          <input
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Nom"
            className="h-11 rounded-full border border-border bg-surface px-4 text-sm"
          />
          <select
            value={role}
            onChange={(e) => setRole(e.target.value as "admin" | "mod" | "member")}
            className="h-11 rounded-full border border-border bg-surface px-3 text-sm"
          >
            <option value="admin">admin</option>
            <option value="mod">mod</option>
            <option value="member">membre</option>
          </select>
          <button
            type="button"
            onClick={() => void addStaff()}
            className="h-11 rounded-full border border-primary/40 px-4 text-sm text-primary"
          >
            Nommer
          </button>
        </div>
      </section>
      <section>
        <h3 className="font-display text-2xl">Décor depuis le Drive</h3>
        <p className="text-sm text-muted">Glissez une image dans le Drive, puis posez-la en hero de l'univers.</p>
        <ul className="mt-3 grid gap-2 sm:grid-cols-2">
          {files
            .filter((f) => f.kind === "image")
            .map((f) => (
              <li key={f.id} className="rounded-xl bg-surface px-4 py-3 text-sm">
                {f.name} · v{f.version}
              </li>
            ))}
          {files.filter((f) => f.kind === "image").length === 0 ? (
            <li className="text-sm text-muted">Aucune image encore — déposez-en dans Studio / Reliques.</li>
          ) : null}
        </ul>
      </section>
      <SeoStudio slug={slug} seo={seo} />
      <CckBuilder slug={slug} fields={cck} />
    </div>
  );
}
