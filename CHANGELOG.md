# Changelog — Geniuspace

Format : ce qui est **dans le code**, pas la vision PDF.

## 2026-08-29 — Porte produit (une porte, puissance intacte)

- **Accueil.** Une phrase, un CTA Créer, cinq germes (atelier-anime, terrain, vault, maison-rh, manga-hub). Vera / Lumen = témoins. Marque surface : Nodus.
- **Catalogue.** `/create` ouvre sur les germes. 10 flagships + ~50 démarrages restent dans les onglets.
- **Hôte public.** `GET|POST /n/{slug}/ghost` passe par `GhostHost::publicSurface`. Reply, citations, actions, profil, mémoire publique. growth / belief / critic / simulations restent dans `Ghost::reply` (staff, lab, gym, cerveau).
- **Maturité.** `GET /n/{slug}/ghost/maturity` = staff. Le Core n’est pas éteint.
- **Tests.** `DoorTest`, `KernelHostTest`, `GhostTest::test_public_chat_strips_core_telemetry`. Invariants 15 + 5 inchangés.
- **Docs.** `docs/FOCUS.md`, `docs/KERNEL.md`.

## 2026-08-27 — Cerveau V8 (index, liens, oubli, refus)

- **GhostCortex.** BM25 + vecteur hashing-trick. Chaque hit a une couche `world` / `evidence` / `belief`.
- **GhostTribunal.** Réponse extraite avec citations, ou refus. La croyance n’est pas une preuve.
- **GhostSynapse.** Hebbian `w' = w + α(1−w)`.
- **GhostDecay.** Demi-vie. SERENDIPITY 14 j. Seuil 0.03.
- **GhostChunk.** 600 / 150, ID stable. Pas d’OCR.
- **GhostConsistency.** SUPPORT / CONTRADICTION / NEUTRAL / NEW. Jaccard + négation. Pas un LLM.
- **GhostDream.** Consolidation hors interaction. `applied = false`.
- **GhostGrowth.** Sans observation du monde, rien n’est mis à jour.
- **Staff.** `/n/{slug}/ghost/cerveau`. Question factuelle du chat public : tribunal, pas LLM.

## 2026-08-27 — Boucle cognitive v7

- **GhostCore.** OBSERVE → situation → mémoire → hypothèse → plan → critic → simuler → décider → autoriser → vérifier → réfléchir → apprendre.
- **Situation.** Contrainte dure, préférence souple, trade-off (200 € / 230 €). Le LLM propose un JSON, Nodus recale.
- **Cinq mémoires.** Sémantique, épisodique, procédurale, sociale, de travail.
- **Critic.** Attaque le plan. Replan. Pas un juge de vérité.
- **Simulateur.** A / B / C. `observed` reste null. Ce n’est pas le monde.
- **Croyance.** P + preuves + decay. Pas un score « knowledge = 87 ».
- **Self.** Matrice de capacités. Autonomie d’exécution plafonnée à 54.
- **Skill** `recover_failed_campaign`. Préconditions. Envoi = confirmation.
- **Learn.generalize.** Trois échecs du même type → règle, testée sur l’histoire.
