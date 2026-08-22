/** Peau du cockpit selon le type de Node vidéo. Tokens identiques, copy différente. */
export type VideoMode = "lore" | "formation" | "game" | "shop" | "interview";

export function videoModeOf(mode?: string, genre?: string): VideoMode {
  if (mode === "formation" || mode === "game" || mode === "shop" || mode === "interview" || mode === "lore") {
    return mode;
  }
  const g = (genre ?? "").toLowerCase();
  if (g.includes("recrut")) return "interview";
  if (g.includes("making") || g.includes("produit")) return "shop";
  if (g.includes("master") || g.includes("tuto")) return "formation";
  return "lore";
}

export const MODE_COPY: Record<
  VideoMode,
  { shop: string; paywallTitle: string; paywallBody: string; unlock: string; ticker: string }
> = {
  lore: {
    shop: "Ajouter aux reliques",
    paywallTitle: "",
    paywallBody: "",
    unlock: "",
    ticker: "Lore",
  },
  formation: {
    shop: "Débloquer la masterclass",
    paywallTitle: "Fin de l'extrait",
    paywallBody: "La suite, le zip source et le rig 3D sont derrière l'accès formation.",
    unlock: "Débloquer",
    ticker: "Formation",
  },
  game: {
    shop: "Season pass",
    paywallTitle: "Contenu de saison",
    paywallBody: "Le patch-note et les drops restent dans le Drive séquencé après déblocage.",
    unlock: "Activer le pass",
    ticker: "Jeu",
  },
  shop: {
    shop: "Acheter ce Node",
    paywallTitle: "Extrait boutique",
    paywallBody: "La fiche produit complète et le making-of HD sont liés à l'achat.",
    unlock: "Acheter",
    ticker: "Boutique",
  },
  interview: {
    shop: "Dossier candidat",
    paywallTitle: "Suite de l'épreuve",
    paywallBody: "Le cas pratique et le PDF dossier s'ouvrent à l'étape 2 — pas un CV envoyé à un robot.",
    unlock: "Continuer l'épreuve",
    ticker: "Entretien",
  },
};
