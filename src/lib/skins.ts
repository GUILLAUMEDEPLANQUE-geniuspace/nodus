import type { GraphNode } from "@/lib/graph";

export type Skin = "living" | "vera" | "cinema";

export function skinOf(node: GraphNode): Skin {
  if (node.kind === "company" || node.kind === "job") return "vera";
  if (node.kind === "person") return "cinema";
  return "living";
}

const PORTRAITS: Record<string, string> = {
  "monkey-d-luffy": "/realms/luffy.jpg",
  "roronoa-zoro": "/realms/zoro.jpg",
  nami: "/realms/nami.jpg",
  sanji: "/realms/sanji.jpg",
  "tony-tony-chopper": "/realms/chopper.jpg",
};

export function portraitOf(slug: string) {
  return PORTRAITS[slug] ?? null;
}

export function heroOf(node: GraphNode) {
  if (
    node.slug === "one-piece" ||
    node.slug === "monkey-d-luffy" ||
    node.slug === "equipage-chapeau-de-paille" ||
    node.slug === "eiichiro-oda"
  )
    return "/realms/sea-hero.jpg";
  if (node.kind === "company" || node.kind === "job") return "/realms/studio-hero.jpg";
  if (node.kind === "person" && node.slug === "richard-dean-anderson") return "/realms/actor-hero.jpg";
  if (
    node.slug.includes("stargate") ||
    node.slug === "jack-oneill" ||
    node.slug === "angus-macgyver" ||
    node.kind === "person"
  )
    return "/realms/portal-hero.jpg";
  return "/realms/sea-hero.jpg";
}
