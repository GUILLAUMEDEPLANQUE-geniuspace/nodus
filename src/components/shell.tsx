import { Link, useNavigate, useRouterState } from "@tanstack/react-router";
import { Compass, Plus, Search } from "lucide-react";
import { useState, type FormEvent } from "react";
import { SignedIn, SignedOut, UserButton } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { ThemeToggle } from "@/lib/theme";

export function Shell({ children }: { children: React.ReactNode }) {
  const navigate = useNavigate();
  const [q, setQ] = useState("");
  const [open, setOpen] = useState(false);
  const { user, isPending } = useCurrentUserState();
  const path = useRouterState({ select: (s) => s.location.pathname });
  const inUniverse = path.startsWith("/n/");

  function onSearch(e: FormEvent) {
    e.preventDefault();
    const query = q.trim();
    if (!query) return;
    setOpen(false);
    void navigate({ to: "/search", search: { q: query } });
  }

  return (
    <div className="min-h-dvh text-fg">
      <header
        className={`z-40 ${inUniverse ? "pointer-events-none absolute inset-x-0 top-0" : "sticky top-0 border-b border-border/80 bg-bg/80 backdrop-blur-md"}`}
      >
        <div className="pointer-events-auto mx-auto flex max-w-6xl items-center gap-3 px-4 py-3 sm:px-6">
          <Link to="/" className="flex shrink-0 items-center gap-2">
            <span className="grid size-8 place-items-center rounded-full border border-primary/40 bg-bg/80">
              <span className="size-2 rounded-full bg-primary shadow-[0_0_12px_var(--color-primary)]" />
            </span>
            <span className="font-display text-xl tracking-[0.18em] text-fg">NODUS</span>
          </Link>

          {!inUniverse ? (
            <form onSubmit={onSearch} className="relative mx-auto hidden min-w-0 flex-1 max-w-md md:block">
              <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" />
              <input
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="Jack O'Neill, MacGyver, Luffy…"
                className="h-11 w-full rounded-full border border-border bg-surface pr-4 pl-10 text-sm text-fg outline-none placeholder:text-muted focus:border-primary/50"
              />
            </form>
          ) : null}

          <nav className="ml-auto flex items-center gap-1 sm:gap-2">
            {inUniverse ? (
              <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className="inline-flex h-11 items-center rounded-full border border-border bg-bg/70 px-3 text-sm text-muted backdrop-blur-md"
              >
                Chercher
              </button>
            ) : null}
            <Link
              to="/profil"
              className="hidden h-11 items-center rounded-full px-3 text-sm text-muted hover:text-fg sm:inline-flex"
            >
              Profil
            </Link>
            <Link
              to="/explore"
              className="inline-flex h-11 items-center gap-2 rounded-full px-3 text-sm text-muted hover:text-fg"
            >
              <Compass className="size-4" />
              <span className="hidden sm:inline">Explorer</span>
            </Link>
            <Link
              to="/create"
              className="inline-flex h-11 items-center gap-2 rounded-full bg-primary px-3.5 text-sm font-medium text-primary-fg"
            >
              <Plus className="size-4" />
              <span className="hidden sm:inline">Créer</span>
            </Link>
            <ThemeToggle />
            <div className="ml-1 flex h-11 min-w-11 items-center justify-center">
              {isPending ? (
                <div className="size-8 animate-pulse rounded-full bg-surface-2" />
              ) : user ? (
                <SignedIn>
                  <UserButton />
                </SignedIn>
              ) : (
                <SignedOut>
                  <Link to="/login" className="text-sm text-muted hover:text-primary">
                    Entrer
                  </Link>
                </SignedOut>
              )}
            </div>
          </nav>
        </div>
        {(open || !inUniverse) && (
          <form onSubmit={onSearch} className={`pointer-events-auto px-4 pb-3 ${inUniverse ? "" : "md:hidden"}`}>
            <input
              value={q}
              onChange={(e) => setQ(e.target.value)}
              placeholder="Traverser vers un univers…"
              className="h-11 w-full rounded-full border border-border bg-bg/80 px-4 text-sm text-fg outline-none placeholder:text-muted backdrop-blur-md"
            />
          </form>
        )}
      </header>
      {children}
    </div>
  );
}
