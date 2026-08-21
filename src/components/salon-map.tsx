/**
 * Salon de l'emploi spatial (Gather / Topia, version légère).
 * Grille 2D : on déplace un avatar, on s'approche d'un stand → visio / quête / terminal.
 * Pas de WebGL : tient sur mobile.
 */
import { useEffect, useState } from "react";
import type { SalonRoom } from "@/lib/graph";
import { FooTerminal } from "@/components/foo-terminal";

const COLS = 16;
const ROWS = 10;

export function SalonMap({ rooms }: { rooms: SalonRoom[] }) {
  const [pos, setPos] = useState({ x: 2, y: 8 });
  const near = rooms.find((r) => Math.abs(r.x - pos.x) + Math.abs(r.y - pos.y) <= 1);

  useEffect(() => {
    function onKey(e: KeyboardEvent) {
      const map: Record<string, { x: number; y: number }> = {
        ArrowUp: { x: 0, y: -1 },
        ArrowDown: { x: 0, y: 1 },
        ArrowLeft: { x: -1, y: 0 },
        ArrowRight: { x: 1, y: 0 },
      };
      const d = map[e.key];
      if (!d) return;
      e.preventDefault();
      setPos((p) => ({
        x: Math.min(COLS, Math.max(1, p.x + d.x)),
        y: Math.min(ROWS, Math.max(1, p.y + d.y)),
      }));
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, []);

  return (
    <div>
      <h2 className="font-display text-3xl">Salon spatial</h2>
      <p className="mt-1 mb-4 text-sm text-muted">
        Flèches pour marcher. Approchez un stand : la visio s'ouvre. Terminal caché = défi Foo.
      </p>
      <div
        className="salon-grid relative overflow-hidden rounded-3xl border border-primary/20"
        style={{
          display: "grid",
          gridTemplateColumns: `repeat(${COLS}, 1fr)`,
          gridTemplateRows: `repeat(${ROWS}, 28px)`,
        }}
      >
        {rooms.map((r) => (
          <button
            key={r.id}
            type="button"
            onClick={() => setPos({ x: r.x, y: r.y })}
            className="z-10 truncate rounded-sm bg-primary/80 px-1 text-[10px] text-primary-fg"
            style={{ gridColumn: r.x, gridRow: r.y }}
          >
            {r.title}
          </button>
        ))}
        <div
          className="z-20 size-4 rounded-full bg-primary shadow-[0_0_12px_var(--color-primary)]"
          style={{ gridColumn: pos.x, gridRow: pos.y }}
          aria-label="Vous"
        />
      </div>
      {near ? (
        <aside className="mt-4 rounded-3xl bg-surface p-5 shadow-[var(--shadow-border)]">
          <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{near.kind}</p>
          <h3 className="font-display text-2xl">{near.title}</h3>
          <p className="mt-2 text-sm text-muted">{near.body}</p>
          {near.kind === "stand" ? (
            <p className="mt-3 text-sm text-primary">Visio recruteur — vous êtes dans le rayon.</p>
          ) : null}
          {near.kind === "terminal" ? <FooTerminal /> : null}
        </aside>
      ) : (
        <p className="mt-3 text-sm text-muted">Marchez vers un stand.</p>
      )}
    </div>
  );
}
