/**
 * Dock bas d'univers — suit le scroll (fixed).
 * Les onglets sont data-driven (`node_tabs`) : l'admin ajoute, renomme, pose une icône.
 * Ne pas hardcoder la liste ici.
 */
import {
  BookOpen,
  Briefcase,
  Building2,
  Clapperboard,
  Compass,
  FolderOpen,
  ListChecks,
  MessagesSquare,
  Newspaper,
  Plus,
  Radio,
  Settings2,
  Sparkles,
  Store,
  Users,
} from "lucide-react";
import type { UniverseTab } from "@/lib/graph";

const ICONS: Record<string, typeof Compass> = {
  compass: Compass,
  users: Users,
  messages: MessagesSquare,
  newspaper: Newspaper,
  radio: Radio,
  book: BookOpen,
  store: Store,
  film: Clapperboard,
  building: Building2,
  briefcase: Briefcase,
  list: ListChecks,
  folder: FolderOpen,
  sparkles: Sparkles,
};

export function UniverseDock({
  tabs,
  active,
  onSelect,
  onStudio,
  bubbles,
}: {
  tabs: UniverseTab[];
  active: string;
  onSelect: (key: string) => void;
  onStudio: () => void;
  bubbles: { id: string; author: string }[];
}) {
  return (
    <div className="pointer-events-none fixed inset-x-0 bottom-3 z-50 flex justify-center px-3">
      {bubbles.map((b) => (
        <span key={b.id} className="rise-bubble pointer-events-none absolute bottom-16 text-xs text-primary">
          {b.author} vient de poster
        </span>
      ))}
      <div className="pointer-events-auto flex max-w-full items-center gap-1 overflow-x-auto rounded-full border border-primary/30 bg-bg/80 p-1.5 shadow-[0_12px_40px_rgba(0,0,0,0.45)] backdrop-blur-md">
        {tabs.map((t) => {
          const Icon = ICONS[t.icon] ?? Sparkles;
          const on = active === t.key;
          return (
            <button
              key={t.id}
              type="button"
              onClick={() => onSelect(t.key)}
              className={`flex h-12 shrink-0 items-center gap-2 rounded-full px-3.5 text-sm ${
                on ? "bg-primary text-primary-fg" : "text-muted hover:text-fg"
              }`}
            >
              <Icon className="size-4" />
              <span className="hidden sm:inline">{t.label}</span>
            </button>
          );
        })}
        <button
          type="button"
          onClick={onStudio}
          className="grid size-12 shrink-0 place-items-center rounded-full text-primary"
          aria-label="Configurer l'univers"
        >
          <Settings2 className="size-4" />
        </button>
        <button
          type="button"
          onClick={onStudio}
          className="grid size-12 shrink-0 place-items-center rounded-full bg-primary text-primary-fg"
          aria-label="Ajouter un onglet"
        >
          <Plus className="size-4" />
        </button>
      </div>
    </div>
  );
}
