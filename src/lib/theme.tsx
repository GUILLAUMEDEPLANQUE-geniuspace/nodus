/**
 * Dark / light — toujours les deux.
 * Tokens uniquement (or / encre). Pas de 3e teinte.
 */
import { Moon, Sun } from "lucide-react";
import { useEffect, useState } from "react";

const KEY = "nodus-theme";

export function applyTheme(theme: "dark" | "light") {
  document.documentElement.setAttribute("data-theme", theme);
  try {
    localStorage.setItem(KEY, theme);
  } catch {
    /* private mode */
  }
}

export function ThemeToggle() {
  const [theme, setTheme] = useState<"dark" | "light">("dark");
  useEffect(() => {
    const saved = localStorage.getItem(KEY);
    const next = saved === "light" ? "light" : "dark";
    setTheme(next);
    applyTheme(next);
  }, []);
  return (
    <button
      type="button"
      onClick={() => {
        const next = theme === "dark" ? "light" : "dark";
        setTheme(next);
        applyTheme(next);
      }}
      className="grid size-11 place-items-center rounded-full text-muted hover:text-fg"
      aria-label={theme === "dark" ? "Passer en clair" : "Passer en sombre"}
    >
      {theme === "dark" ? <Sun className="size-4" /> : <Moon className="size-4" />}
    </button>
  );
}
