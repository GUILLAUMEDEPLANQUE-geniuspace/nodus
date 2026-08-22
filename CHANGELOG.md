# Changelog — Geniuspace

Format : ce qui est **dans le code**, pas la vision PDF.

## 2026-08-22 — Flagships jouables + Ghost clos

- **8 flagships habillés** (Terrain → Plateau) : champs, objet, guide, média, HUD `flagships/play`. Plus seulement Coffre / Table / Vera.
- **Négociation Ghost** : offre dans la fourchette → ligne panier au prix tenu. Fourchette = `min_val`/`max_val` + plancher.
- **Carnet** = salle `RoomCatalog`. `/n/{slug}/carnet` dans le dock. Plus de 404 Ghost.
- **Geniuspedia** dans `Ghost::context` (fiches du lieu).
- **Voix** STT/TTS sur l’orbe (Web Speech, pas un SaaS).
- **`server/`** documenté : chrome Grok, hors stack Laravel.
- Bible : **Flagship vs démarrage**.

## 2026-08-22 — 10 flagships

Coffre, Terrain, Atelier, Territoire, Maison, Scène, Arène, Labo, Plateau, Table.
Chacun a un hôte (négocie / présélectionne), des passages (voisins, pas des liens bleus), une preuve stakée, un schema.org.
Le Coffre : canvas infini, cel City Hunter, JSON-LD Product. Bible : `/flagships`.

## 2026-08-22 — Ghost OS (agent ancré par lieu)

- **Un Ghost par Node.** Profil marchand (Lumen) / RH (Vera) / guide (living).
- **Répond depuis le coffre** : champs, voisins, produits, vidéos, grants, salles — pas le web entier.
- **Tools lecture** : list_products, neighbors, media, grants, rooms. Aucun grant écrit par le modèle.
- **API** `GET|POST /n/{slug}/ghost`, `GET /n/{slug}/ghost/context`.
- **UI** orbe fixe sur les pages de lieu (Alpine).
- **Ollama optionnel** via `GHOST_LLM_URL` / `OLLAMA_BASE_URL` — sinon mode grounded pur.
- **Journal** table `ghost_logs`. Tests `GhostTest`.

## 2026-08-22 — Grant réel, coffre, options d’achat

- **Plus de bandeau.** Sans preuve en base, le player sert le teaser public. L’URL signée du full → 403. `preview=1` ne livre plus le MP4 privé.
- **Drive locké.** Certificat, brief : `/play` 15 min, grant / rôle / achat. Le making-of n’ouvre pas le certificat.
- **Calque → preuve.** Clic sur un calque (Cristal, Karim) tamponne « a visité » dans le carnet.
- **Options d’achat.** Taille, gravure, dos +5 €, logo — champs « à la commande » sur le print Lumen. Pas un formulaire Shopify collé : mêmes champs que le reste du moteur.

## 2026-08-22 — Médias = preuves = lieux

- **Grant serveur.** Débloquer une vidéo écrit une preuve en base (user/session × média). Plus de `granted=true` en JS.
- **Coffre privé.** MP4 et fichiers mérités dans `storage/app/private`. URL signée seulement si teaser ou grant. JSON-LD gated sans `contentUrl`.
- **Carnet d’unlocks.** Films ouverts, briefs d’épreuve, reliques de chapitre, certificats d’achat. Export JSON. Ça voyage d’un lieu à l’autre.
- **Portes.** Un chapitre `@slug` ouvre une fiche. Un drop à 0:04 pose une relique. Un calque image clique vers un lieu.
- **Embed.** `/embed/{slug}` : teaser + une preuve (délai) + un CTA. Widget de lieu, pas un player.
- **RH démo.** `/n/vera/videos` — épreuve consignation Karim → brief + tampon carnet.
- **IP démo.** Making-of Cristal Lumen → éclat en drop, certificat à l’achat.

## 2026-08-22 — Éditeur de monde (A→Z)

- **Thème, scène, boutons, salles, presets.** Tables `node_theme`, `node_scene_layers`, `node_actions`, `node_tabs.enabled`.
- **Éditeur** `/n/{slug}/monde` : Structure (plan, fiches, Google, équipe) · Design (identité, calques) · Action (boutons, paywall) · Motion (fade/slide).
- **Presets** living / galerie / Vera posés à la création. Lumen dit « Acquérir l’œuvre ». Une maison née de Vera dit « Voir les missions ».
- **Plus de CTA en dur** sur le hero, la boutique, le player, le panier. Le moteur (panier, unlock) ne bouge pas.
- Flagship Vera inchangé. Club 205 toujours parti.

## 2026-08-22 — Moteur secret (graphe + champs)

- **Modèles de fiche** par métier : Offre tech, Offre industrie, Personnage, Produit, Maison. Un clic dans le Studio pose les détails.
- **Jargon retiré de l’UI** : plus de « CCK », « graphe », « parent_of », « Node » sur les pages publiques. Fil d’Ariane, « Fait partie de », « Aussi dans cet univers ».
- **Insights dérivés** : alignement fort/moyen/faible, héritage du délai de la maison, carnet = preuves liées, API `GET /v1/nodes/{slug}/fields`.
- **Règles d’équipe** gravées dans `docs/FOR_AI.md` : pas de colonne SQL métier, l’UI lit le moteur.

## 2026-08-22 — Une stack, du français, des tests

- **Laravel seul.** Prototype React / TanStack retiré. Source de vérité : `geniuspace/`.
- **Jargon vulgarisé.** Offres, tests métier, carnet, délai de réponse, profils oubliés. URLs publiques `/tarif`, `/carnet`, `/delais`. Les noms internes restent dans le lexique.
- **Éditeur de champs** visuel dans le Studio : palette, aperçu de fiche, édition, suppression.
- **Tests PHPUnit** : catalogue Vera (35 offres, salaire, honnêteté), pages SSR, Lumen, Club 205 parti, builder.

## 2026-08-22 — Vera cloné (repo vera)

`/n/vera` n’est plus un campus générique. Clone du jobboard [GUILLAUMEDEPLANQUE-geniuspace/vera](https://github.com/GUILLAUMEDEPLANQUE-geniuspace/vera) : 35 offres, salaire P25–P90, honnêteté, tests métier, tarif entreprise, lexique, profils oubliés, Europe, carnet, fiches, délais publics. Peau papier Instrument. Lumen inchangé.

## 2.0.0 — 2026-08-22 — Laravel
