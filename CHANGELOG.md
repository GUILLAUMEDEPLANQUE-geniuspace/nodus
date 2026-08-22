# Changelog — Geniuspace

Format : ce qui est **dans le code**, pas la vision PDF.

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
