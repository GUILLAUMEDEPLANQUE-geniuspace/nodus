import { createFileRoute, Link } from "@tanstack/react-router";
import { ArrowRight } from "lucide-react";

export const Route = createFileRoute("/")({
  component: Home,
});

function Home() {
  return (
    <main>
      <section className="relative h-[92dvh] min-h-[560px] overflow-hidden">
        <img src="/realms/sea-hero.jpg" alt="" className="absolute inset-0 size-full object-cover" />
        <div className="absolute inset-0 bg-gradient-to-t from-bg via-bg/40 to-bg/10" />
        <div className="absolute inset-x-0 bottom-0 mx-auto max-w-6xl px-4 pb-12 sm:px-6">
          <p className="text-[11px] tracking-[0.28em] text-primary uppercase">Geniuspace — un lieu de vie</p>
          <h1 className="mt-3 max-w-3xl font-display text-5xl leading-[0.92] sm:text-7xl">
            Les fans bâtissent le monde.
            <br />
            La guilde l'habite.
          </h1>
          <p className="mt-5 max-w-xl text-base leading-relaxed text-fg/90">
            Wiki, forum, journal, canal, reliques, carte 3D — le même univers. Un recruteur n'entre pas dans un
            ciel d'étoiles : il ouvre une Maison, des offres, une épreuve.
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link
              to="/n/$slug"
              params={{ slug: "one-piece" }}
              search={{ view: "fiche" }}
              className="inline-flex h-12 items-center gap-2 rounded-full bg-primary px-5 text-sm font-medium text-primary-fg"
            >
              Vivre One Piece
              <ArrowRight className="size-4" />
            </Link>
            <Link
              to="/n/$slug"
              params={{ slug: "maison-orion" }}
              search={{ view: "fiche" }}
              className="inline-flex h-12 items-center rounded-full border border-fg/30 bg-bg/40 px-5 text-sm backdrop-blur-md"
            >
              Maison recruteur
            </Link>
          </div>
        </div>
      </section>

      <section className="mx-auto grid max-w-6xl gap-4 px-4 py-12 sm:grid-cols-3 sm:px-6">
        {[
          {
            slug: "one-piece",
            img: "/realms/sea-hero.jpg",
            title: "One Piece",
            hint: "Guilde, lore, carte 3D",
          },
          {
            slug: "stargate-sg1",
            img: "/realms/portal-hero.jpg",
            title: "Stargate SG-1",
            hint: "Série habitée",
          },
          {
            slug: "maison-orion",
            img: "/realms/studio-hero.jpg",
            title: "Maison Orion",
            hint: "Job board nouvelle gen",
          },
        ].map((g) => (
          <Link
            key={g.slug}
            to="/n/$slug"
            params={{ slug: g.slug }}
            search={{ view: "fiche" }}
            className="group overflow-hidden rounded-3xl bg-surface shadow-[var(--shadow-border)]"
          >
            <div className="aspect-[16/10] overflow-hidden">
              <img src={g.img} alt="" className="size-full object-cover transition duration-300 group-hover:scale-105" />
            </div>
            <div className="p-4">
              <h2 className="font-display text-2xl">{g.title}</h2>
              <p className="text-sm text-muted">{g.hint}</p>
            </div>
          </Link>
        ))}
      </section>
    </main>
  );
}
