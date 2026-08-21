/**
 * Profil membre — plus qu'un avatar : cover, univers, playlists, contributions.
 * Le créateur d'un Node manga / recruteur / business customise ensuite chaque univers.
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { RedirectToSignIn } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

export const Route = createFileRoute("/profil")({ component: ProfilPage });

function ProfilPage() {
  const { user, isPending } = useCurrentUserState();
  if (isPending) return <div className="px-4 py-16 text-muted">Ouverture du profil…</div>;
  if (!user) return <RedirectToSignIn to="/login" />;

  return (
    <main className="pb-16">
      <section className="relative h-[42dvh] min-h-[280px] overflow-hidden">
        <img src="/realms/actor-hero.jpg" alt="" className="absolute inset-0 size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-bg to-transparent" />
        <div className="absolute inset-x-0 bottom-0 mx-auto max-w-4xl px-4 pb-8">
          <p className="text-xs tracking-[0.2em] text-primary uppercase">Profil</p>
          <h1 className="font-display text-5xl">Votre constellation</h1>
          <p className="mt-2 text-sm text-muted">Un profil n'est pas une page : c'est la porte de vos univers.</p>
        </div>
      </section>
      <div className="mx-auto grid max-w-4xl gap-4 px-4 py-8 sm:grid-cols-2">
        {[
          { to: "/n/$slug" as const, slug: "one-piece", title: "One Piece", hint: "Fan · guilde" },
          { to: "/n/$slug" as const, slug: "maison-orion", title: "Maison Orion", hint: "Recruteur · Vera" },
        ].map((u) => (
          <Link
            key={u.slug}
            to={u.to}
            params={{ slug: u.slug }}
            search={{ view: "fiche" }}
            className="rounded-3xl bg-surface p-5 shadow-[var(--shadow-border)]"
          >
            <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{u.hint}</p>
            <h2 className="font-display text-2xl">{u.title}</h2>
          </Link>
        ))}
      </div>
    </main>
  );
}
