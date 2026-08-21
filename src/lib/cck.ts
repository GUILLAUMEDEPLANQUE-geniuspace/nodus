/**
 * CCK — Custom Content Kit (NODUS)
 *
 * POUR LES DEVS / IA
 * ------------------
 * Ce n'est PAS un CMS à plugins. Chaque Node (offre, perso, article, produit, vidéo)
 * porte une liste de champs typés, comme JoomCCK / Vera.
 *
 * Types de champs (fieldType) :
 *   text      — chaîne courte
 *   html      — corps riche (guides, blogs)
 *   choice    — enum / tags
 *   media     — attache un fichier Drive
 *   relation  — attache un autre Node / thread
 *   scale     — nombre (stock, prime, salaire min)
 *
 * Cibles (targetKind + targetId) :
 *   node     — la fiche elle-même (job, personnage)
 *   thread   — un article de journal / sujet forum
 *   product  — une ligne boutique
 *   media    — une relique / fiche vidéo
 *
 * Ajouter un champ = insert cck_fields, PAS une migration SQL par métier.
 * Les layouts (Vera, LivingWorld, ShopFloor) lisent les champs et les rendent.
 * Ne jamais hardcoder "salaire" dans le JSX d'une offre : passer par le CCK.
 */
export const CCK_FIELD_TYPES = ["text", "html", "choice", "media", "relation", "scale"] as const;
export type CckFieldType = (typeof CCK_FIELD_TYPES)[number];

export const CCK_TARGETS = ["node", "thread", "product", "media"] as const;
export type CckTarget = (typeof CCK_TARGETS)[number];
