/**
 * Profil membre — cover, bio, univers suivis, visites, playlists porte.
 * Configurable : ce n'est plus deux liens en dur.
 */
import { createFileRoute, Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { RedirectToSignIn } from "@/lib/auth/gates";
import { useCurrentUserState } from "@/lib/auth/use-current-user";
import { getMyProfile, saveProfile } from "@/lib/platform-api";
import type { FollowedNode, Profile } from "@/lib/platform";

export const Route = createFileRoute("/profil")({ component: ProfilPage });

function ProfilPage() {
  const { user, isPending } = useCurrentUserState();
  const [profile, setProfile] = useState<Profile | null>(null);
  const [follows, setFollows] = useState<FollowedNode[]>([]);
  const [visits, setVisits] = useState<{ slug: string; title: string }[]>([]);
  const [bio, setBio] = useState("");
  const [name, setName] = useState("");

  useEffect(() => {
    if (!user) return;
    void getMyProfile()
      .then((res) => {
        setProfile(res.profile);
        setFollows(res.follows);
        setVisits(res.visits);
        setBio(res.profile.bio);
        setName(res.profile.displayName || user.displayName || "");
      })
      .catch(() => {});
  }, [user]);

  if (isPending) return <div className="px-4 py-16 text-muted">Ouverture du profil…</div>;
  if (!user) return <RedirectToSignIn to="/login" />;

  return (
    <main className="pb-16">
      <section className="relative h-[42dvh] min-h-[280px] overflow-hidden">
        <img src={profile?.coverUrl || "/realms/actor-hero.jpg"} alt="" className="absolute inset-0 size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-bg to-transparent" />
        <div className="absolute inset-x-0 bottom-0 mx-auto max-w-4xl px-4 pb-8">
          <p className="text-xs tracking-[0.2em] text-primary uppercase">Profil</p>
          <h1 className="font-display text-5xl">{name || "Votre constellation"}</h1>
          <p className="mt-2 text-sm text-muted">{bio || "Un profil n'est pas une page : c'est la porte de vos univers."}</p>
        </div>
      </section>
      <div className="mx-auto max-w-4xl px-4 py-8">
        <form
          className="mb-8 grid gap-3 sm:grid-cols-2"
          onSubmit={(e) => {
            e.preventDefault();
            void saveProfile({ data: { displayName: name, bio, locale: "fr" } });
          }}
        >
          <input value={name} onChange={(e) => setName(e.target.value)} className="h-11 rounded-full border border-border bg-surface px-4 text-sm" placeholder="Nom" />
          <input value={bio} onChange={(e) => setBio(e.target.value)} className="h-11 rounded-full border border-border bg-surface px-4 text-sm" placeholder="Bio" />
          <button type="submit" className="h-11 rounded-full bg-primary px-4 text-sm text-primary-fg sm:col-span-2">
            Enregistrer le profil
          </button>
        </form>
        <h2 className="font-display text-3xl">Univers suivis</h2>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          {follows.map((u) => (
            <Link
              key={u.id}
              to="/n/$slug"
              params={{ slug: u.slug }}
              search={{ view: "fiche" }}
              className="rounded-3xl bg-surface p-5 shadow-[var(--shadow-border)]"
            >
              <p className="text-[11px] tracking-[0.16em] text-primary uppercase">{u.kind}</p>
              <h3 className="font-display text-2xl">{u.title}</h3>
            </Link>
          ))}
        </div>
        {visits.length > 0 ? (
          <div className="mt-10">
            <h2 className="font-display text-2xl">Récemment visité</h2>
            <ul className="mt-3 space-y-1 text-sm text-muted">
              {visits.map((v) => (
                <li key={v.slug}>
                  <Link to="/n/$slug" params={{ slug: v.slug }} search={{ view: "fiche" }} className="text-primary">
                    {v.title}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        ) : null}
      </div>
    </main>
  );
}
