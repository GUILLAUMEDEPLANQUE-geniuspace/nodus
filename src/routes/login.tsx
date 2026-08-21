import { createFileRoute, Link } from "@tanstack/react-router";
import { GROK_PROVIDERS, authEnabled, signIn } from "@/lib/auth/client";

export const Route = createFileRoute("/login")({ component: Login });

function Login() {
  return (
    <main className="mx-auto grid min-h-[70dvh] max-w-md place-items-center px-4">
      <div className="w-full rounded-3xl bg-surface p-8 shadow-[var(--shadow-border)]">
        <p className="text-[11px] tracking-[0.2em] text-primary uppercase">Accès</p>
        <h1 className="mt-2 font-display text-4xl">Entrer dans NODUS</h1>
        <p className="mt-3 text-sm leading-relaxed text-muted">
          Créez vos nœuds, reliez un personnage à son interprète, une offre à sa maison.
        </p>
        <div className="mt-6 space-y-3">
          {authEnabled ? (
            GROK_PROVIDERS.map((p) => (
              <button
                key={p.providerId}
                type="button"
                onClick={() => signIn(p.providerId, { callbackURL: "/create" })}
                className="h-12 w-full rounded-full border border-border text-sm hover:border-primary/50"
              >
                Continuer avec {p.label}
              </button>
            ))
          ) : (
            <p className="text-sm text-muted">Connexion indisponible.</p>
          )}
        </div>
        <Link to="/" className="mt-6 inline-block text-sm text-muted hover:text-primary">
          Retour à l'atlas
        </Link>
      </div>
    </main>
  );
}
