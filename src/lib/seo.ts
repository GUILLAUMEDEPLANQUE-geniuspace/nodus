/**
 * SEO / SEM / LLM — chaque Node a un titre, une description et un graphe JSON-LD uniques.
 * Ne jamais réutiliser le title global "NODUS" sur une fiche : Google et les LLM
 * doivent voir UN document par univers / offre / produit / perso.
 */
import type { CckField, GraphNode, NodeUniverse, ShopProduct, WikiPage } from "@/lib/graph";

const KIND_SEO: Record<string, (n: GraphNode) => { title: string; description: string; ogType: string }> = {
  series: (n) => ({
    title: `${n.title} — wiki, forum, personnages, guides | NODUS`,
    description: n.summary || `Univers ${n.title} : lore, guilde, reliques et fiches liées.`,
    ogType: "video.tv_show",
  }),
  franchise: (n) => ({
    title: `${n.title} — franchise, univers interconnectés | NODUS`,
    description: n.summary,
    ogType: "website",
  }),
  character: (n) => ({
    title: `${n.title} — fiche personnage, lore, univers | NODUS`,
    description: n.summary || `Fiche ${n.title} : relations graphe, CCK, reliques.`,
    ogType: "profile",
  }),
  person: (n) => ({
    title: `${n.title} — rôles, filmographie graphe | NODUS`,
    description: n.summary || `Tous les rôles de ${n.title} en parent/enfant.`,
    ogType: "profile",
  }),
  company: (n) => ({
    title: `${n.title} — maison, salon RPG, offres, épreuves | NODUS`,
    description: n.summary || `Recrutement expérientiel ${n.title} : quêtes, skill tree, salon.`,
    ogType: "website",
  }),
  job: (n) => ({
    title: `${n.title} — offre, épreuve, CCK | NODUS`,
    description: n.summary || `Offre ${n.title} : compétences CCK, quêtes, Drive.`,
    ogType: "article",
  }),
  product: (n) => ({
    title: `${n.title} — boutique du Node | NODUS`,
    description: n.summary,
    ogType: "product",
  }),
  group: (n) => ({
    title: `${n.title} — groupe, membres, univers | NODUS`,
    description: n.summary,
    ogType: "profile",
  }),
};

export function seoForNode(node: GraphNode) {
  const make = KIND_SEO[node.kind];
  const base = make
    ? make(node)
    : {
        title: `${node.title} — ${node.kind} | NODUS`,
        description: node.summary || node.subtitle,
        ogType: "website",
      };
  const keywords = [node.title, node.kind, node.subtitle, "NODUS", "univers", "graphe"]
    .filter(Boolean)
    .join(", ");
  return {
    ...base,
    keywords,
    canonical: `/n/${node.slug}`,
  };
}

/** @graph JSON-LD : JobPosting, Product, Article, TVSeries, Person, Breadcrumb. */
export function jsonLdGraph(universe: NodeUniverse) {
  const { node, children, parents, media, products, wiki, cck, threads } = universe;
  const url = `/n/${node.slug}`;
  const graph: Record<string, unknown>[] = [
    {
      "@type": "BreadcrumbList",
      itemListElement: [
        { "@type": "ListItem", position: 1, name: "NODUS", item: "/" },
        ...(parents[0]
          ? [{ "@type": "ListItem", position: 2, name: parents[0].title, item: `/n/${parents[0].slug}` }]
          : []),
        { "@type": "ListItem", position: parents[0] ? 3 : 2, name: node.title, item: url },
      ],
    },
  ];

  if (node.kind === "person") {
    graph.push({
      "@type": "Person",
      name: node.title,
      description: node.summary,
      url,
      performerIn: children.map((c) => ({
        "@type": "PerformanceRole",
        characterName: c.title,
        url: `/n/${c.slug}`,
      })),
    });
  } else if (node.kind === "character") {
    graph.push({
      "@type": "Person",
      name: node.title,
      description: node.summary,
      url,
      additionalType: "FictionalCharacter",
    });
  } else if (node.kind === "series" || node.kind === "franchise") {
    graph.push({
      "@type": "TVSeries",
      name: node.title,
      description: node.summary,
      url,
      character: children
        .filter((c) => c.kind === "character")
        .map((c) => ({ "@type": "Person", name: c.title, url: `/n/${c.slug}` })),
    });
  } else if (node.kind === "company") {
    graph.push({
      "@type": "Organization",
      name: node.title,
      description: node.summary,
      url,
      jobPosting: children
        .filter((c) => c.kind === "job")
        .map((j) => jobPosting(j, node, cck)),
    });
  } else if (node.kind === "job") {
    graph.push(jobPosting(node, parents[0], cck));
  } else {
    graph.push({
      "@type": "CreativeWork",
      name: node.title,
      description: node.summary,
      url,
    });
  }

  for (const p of products) graph.push(productLd(p, node));
  for (const w of wiki) graph.push(articleLd(w, node));
  for (const t of threads.filter((x) => x.kind === "forum")) {
    graph.push({
      "@type": "DiscussionForumPosting",
      headline: t.title,
      articleBody: t.body,
      author: { "@type": "Person", name: t.author },
      url: `/n/${node.slug}/t/${t.id}`,
      isPartOf: url,
    });
  }
  for (const t of threads.filter((x) => x.kind === "blog")) {
    graph.push({
      "@type": "BlogPosting",
      headline: t.title,
      author: t.author,
      articleBody: t.body,
      isPartOf: url,
    });
  }
  const video = media.find((m) => m.kind === "video");
  if (video) {
    graph.push({
      "@type": "VideoObject",
      name: video.title,
      description: video.transcript || node.summary,
      duration: video.duration,
      isPartOf: url,
    });
  }

  return { "@context": "https://schema.org", "@graph": graph };
}

function jobPosting(job: GraphNode, org: GraphNode | undefined, cck: CckField[]) {
  const val = (key: string) => cck.find((f) => f.key === key)?.value;
  return {
    "@type": "JobPosting",
    title: job.title,
    description: job.summary,
    url: `/n/${job.slug}`,
    hiringOrganization: org ? { "@type": "Organization", name: org.title } : undefined,
    skills: val("stack"),
    employmentType: val("contrat"),
    jobLocationType: val("remote")?.toLowerCase().includes("remote") ? "TELECOMMUTE" : undefined,
  };
}

function productLd(p: ShopProduct, node: GraphNode) {
  return {
    "@type": "Product",
    name: p.title,
    description: p.summary,
    brand: node.title,
    offers: { "@type": "Offer", price: p.price.replace(/[^\d.,]/g, "") || "0", priceCurrency: "EUR" },
    isPartOf: `/n/${node.slug}`,
  };
}

function articleLd(w: WikiPage, node: GraphNode) {
  return {
    "@type": "Article",
    headline: w.title,
    articleBody: w.body,
    isPartOf: `/n/${node.slug}`,
    about: node.title,
  };
}
