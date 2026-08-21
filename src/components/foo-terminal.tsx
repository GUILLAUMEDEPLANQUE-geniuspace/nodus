/**
 * Terminal caché (Google Foo.bar). Un défi, pas un formulaire.
 * Mot-clé : hire — révèle le canal recruteur.
 */
import { useState } from "react";

export function FooTerminal() {
  const [line, setLine] = useState("");
  const [log, setLog] = useState<string[]>(["> NODUS://orion/foo — tapez hire"]);
  const [open, setOpen] = useState(false);

  function run() {
    const cmd = line.trim().toLowerCase();
    if (cmd === "hire" || cmd === "foobar") {
      setOpen(true);
      setLog((l) => [...l, `> ${line}`, "Canal RH déverrouillé. Passez la quête Jury."]);
    } else {
      setLog((l) => [...l, `> ${line}`, "commande inconnue"]);
    }
    setLine("");
  }

  return (
    <div className="mt-4 rounded-2xl bg-bg p-4 font-mono text-xs text-primary">
      {log.map((l, i) => (
        <p key={`${l}-${i}`}>{l}</p>
      ))}
      <div className="mt-2 flex gap-2">
        <span>{">"}</span>
        <input
          value={line}
          onChange={(e) => setLine(e.target.value)}
          onKeyDown={(e) => {
            if (e.key === "Enter") run();
          }}
          className="flex-1 bg-transparent outline-none"
          aria-label="Terminal"
        />
      </div>
      {open ? <p className="mt-3 text-sm text-fg">Épreuve déverrouillée — allez à la Salle Jury.</p> : null}
    </div>
  );
}
