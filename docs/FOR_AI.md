# Consignes pour les agents IA

Tu travailles sur **Geniuspace**. Stack unique : Laravel dans `geniuspace/`.

1. Lis `docs/ARCHITECTURE.md` et `docs/CCK.md`.
2. **Pas de React / TanStack / Vite** pour le produit. Blade + Alpine.
3. Un univers galerie et un univers recrutement **n’ont pas le même layout**.
4. Les onglets d’un club viennent de `node_tabs`.
5. Les champs métier = briques sur la fiche (table `cck_fields`). Pas de colonne SQL `salary` sur `nodes` pour un club générique. Vera lit son catalogue JSON **et** le pose en champs + arêtes (moteur).
6. Migrations : nouveau fichier `geniuspace/database/migrations/…`. Ne pas réécrire les anciennes.
7. Commenter **pourquoi**, pas chaque ligne.
8. Lectures publiques, écritures authentifiées.
9. Parent/enfant est le cœur. Ne pas aplatir le graphe en tags.
10. SEO : title / canonical / JSON-LD serveur. Pas de titre global « Geniuspace » sur une offre.
11. Recrutement = Vera (offres lues, test métier, délai public). Pas une grille Indeed.
12. Éditeur de champs : builder visuel dans le Studio (`partials/cck-builder`). Modèles de métier (Offre tech, industrie, personnage, produit). Pas de colonne SQL métier.
13. Forum = holo-forum Blade. Chaque sujet a `/n/{slug}/t/{id}`.
14. Vidéo : `<video>` réel, fichiers souverains, pas YouTube.
15. Marque = Geniuspace. Vera = jobboard. Lumen = galerie.
16. Interface : français d’abord. Les mots internes (PPQC, Pacte, Passport, CCK, Node, edge, graphe) s’expliquent ou se traduisent. URLs publiques Vera : `/n/vera/tarif`, `/n/vera/carnet`, `/n/vera/delais`. Carnet d’un lieu : `/n/{slug}/carnet`.
17. Toute nouvelle info métier = champ de fiche (`cck_fields`, `field_key` stable) **ou** arête typée. Une PR qui ajoute une colonne SQL métier (`salary`, `remote`, `fruit`) est refusée, sauf migration d’infra.
18. Toute nouvelle UI lit le moteur (`Engine::fields` / `neighbors` / `inherit` / `alignment`). Pas de `salary={55000}` magique.
19. Le jargon graphe / CCK / Node / edge / parent_of est **interdit en copy produit**. Autorisé : code, docs techniques, commentaires.
20. Chaque insight UI (« Alignement fort », « Aussi dans cet univers », « Cette maison répond en 4 jours ») doit être recalculable par une fonction pure sur nodes / edges / champs.
21. Versionner le schéma : `field_key` stables, labels traduisibles, `schema_version`. Ne pas casser `GET /v1/nodes/{slug}/fields`.
22. **Chrome = données.** Boutons, dock, paywall, calques, tokens : tables `node_theme` / `node_actions` / `node_scene_layers` / `node_tabs`. Pas de libellé métier en dur dans les vues. Handlers (panier, unlock) restent dans le code.
23. **Éditeur de monde** : `/n/{slug}/monde`, modes Structure / Design / Action / Motion. Blade, pas React. Presets `living` | `merch` | `vera` à la création.
24. Custom total sur identité, scène, navigation, actions, champs, contenus, droits. Contraint sur player, panier, grants, graphe.
25. Le flagship `/n/vera` reste le jobboard éditorial (slug exact). Une maison née du template Vera passe par l’éditeur de monde.
26. **Médias = portes, pas des fichiers.** Unlock = ligne `grants` **après** preuve ou achat. Un POST `/unlock` nu est 403. Jamais `granted=true` en JS. JSON-LD gated : pas de `contentUrl`. Fichiers lockés dans `storage/app/private`, servis par `/play` si grant ou staff.
27. **Carnet = déblocages + export JSON.** Relique d’un chapitre, brief d’épreuve, certificat d’achat : même coffre. Surface : « tenu », jamais « grant ». Omni = **claim** déclaré, pas une preuve vérifiée.
28. Un calque image / un chapitre `@slug` ouvre un lieu. Pas un timecode mort.
29. **Unlock = preuve/achat → grant, sinon 403.** Le teaser est un fichier public distinct. Jamais le MP4 privé en `preview=1`. Drop exige une porte à cet instant. Fichier Drive locké = `/play` signé 15 min, jamais d’URL permanente.
30. **Rideau.** `Spoiler` filtre aussi le Ghost (`context.rideau.caches`). Une fiche au-delà de l’arc n’existe pas encore. Geniuspedia ne sort pas du lieu.
31. **Dix flagships.** Coffre, Terrain, Atelier, Territoire, Maison, Scène, Arène, Labo, Plateau, Table. Spec : `Flagships.php` + `/flagships`. Surface ≠ jargon. Hôte = `Ghost::reply`. Passage = neighbors. Preuve = lore_proposals. **Flagship ≠ démarrage** : les ~50 templates d’en dessous sont des coquilles métier, pas des skins.
32. **Carnet = salle.** `RoomCatalog` clé `carnet`. `/n/{slug}/carnet` + export JSON. Le dock la pose. Le Ghost n’y 404 pas. Vera : `/n/vera/carnet` (alias `passport`).
33. **Négociation Ghost** : fourchette = `min_val` / `max_val` + `prix_plancher`. Accepté dans la fourchette → **ligne panier** au prix tenu (`held_cents`). Checkout encaisse **ce** montant (Stripe si `STRIPE_SECRET`, sinon ledger). Jamais sous le min.
34. **Geniuspedia** : fiches pack du Node (`Geniuspedia::cards`) dans `Ghost::context['fiches']`. URLs locales seulement. **Pas de crawl web.**
35. **`server/` n’est pas Laravel.** Chrome PWA Grok (`grok-pwa.ts`). Ignorer. Produit = `geniuspace/`.
36. **Omni-média.** Chapitre Labo / Mixer / Tactique → le player **bascule le cadre** (`Omni::beats`, overlay `omni-frame`). Tenir l’exo = grant `omni`. Prod = 03:15. Démo courte = 0:03 + remap si `duration < at`.
37. **Maison neuve.** `/n/vera` reste le flagship historique. `WorldTemplates::apply('maison-rh')` pose les 35 missions (`Maison::attachCatalog`). Pas un second Vera.
38. **Ghost = agent cognitif.** Le LLM n’est pas le cerveau. Pipeline : Understand → Memory → Plan → Observe (multi-outils) → Verify → Respond. Faits structurés, jamais un transcript comme vérité. ACT (achat, candidature, unlock, graphe) exige une confirmation humaine. `GhostVerifier` bloque les affirmations hors coffre. Skills dans `GhostSkills.php`. Chat public : `POST /n/{slug}/ghost`. Copilote (plan/apply/gym/editor/context) = staff.
39. **Paiement.** `Pay::cents` = prix tenu, jamais sous `Invariants::heldCents`. Stripe Checkout Session HTTP (pas de SDK). Retour `/panier/retour`.
40. **Ghost croissance.** `GhostLearn` / `GhostGym` / `GhostMaturity`. Une hallucination n’entre jamais en mémoire. Une skill nouvelle = candidat, pas prod. Gym : `/n/{slug}/ghost/gym` (staff). Autonomie plafonnée à `Invariants::AUTONOMY_CAP` (54). Pas de crawl, pas d’embeddings comme coffre.
41. **GhostAction.** Le LLM ne touche pas SQL. DSL (`field.add` after/before). Blocks + curseur Ghost (`editor_context`). PLAN ≠ APPLY (`/ghost/plan` puis `/ghost/apply`, tous deux staff admin). Undo = snapshot `ghost_actions`. `CckCatalog::capabilities` = vocabulaire. Business : READ / PREPARE / ACT. Volume ≥ 100 ou remise > 10 % = confirm. Refund / delete customer / payment = deny. Copilote Studio Blade.
42. **Invariants.** Quinze règles, pas un seizième concept. `docs/INVARIANTS.md` + `App\Support\Invariants`. Kernel : Engine ↛ Ghost/Grantor/Chrome. `Acl` n’auto-promouvoit plus. `/login/demo` et `/llm/tools` : local/tests. Tests : `InvariantTest`, `KernelBoundaryTest`. Une PR qui en casse une est refusée.
43. **Contrat Ghost.** Ghost propose une **transition d’état vérifiable**, pas « une action IA ». Pipeline : INTENT → PLAN → CONTRACT → AUTHORIZE → PREVIEW → APPLY → OBSERVE → VERIFY → STATE'. `GhostActionContract` + `ghost_transitions` (produced_by / verified_by). Deux réalités : Engine = vérité, `ghost_facts` = croyance visiteur — `GhostMemory::mixed()` false. Manifeste READ/PREPARE(=PROPOSE)/ACT/DENY : Ghost ne reçoit pas DENY. Claim → evidence → PASS/FAIL/UNKNOWN. Produit : OBSERVE/PREPARE/ACT inchangé. Pas de concept UI nouveau.
44. **Strategy Lab.** Ghost cherche dans l’espace des leviers (pas « trois idées LLM »). Génome + mutations + fitness (perf + novelty − cost − risk). Plateau → exploration. Challenger. Mémoire tenues/ratées. `strategy.deploy` = ACT + CONFIRM. Signature : autonome sur la stratégie, jamais sur l’autorité. Staff : `/n/{slug}/ghost/lab`. Pas un seizième invariant produit — c’est I-CONTRACT + I-NO-ACT.
45. **Découverte ≠ simulation.** `estimate()` = prior. `observe()` = monde (Engine, salon, preuves). `observed` est null tant qu’il n’y a pas de cohorte. Une tenue exige `evidence=world`. Hypothèse + contre-hypothèse, réfutation, surprise = \|obs−pred\|. BASE n’est pas une causalité découverte. `GhostHypothesis::ingestConcepts` = hook LLM, pas un juge. `GhostLearn::afterExperiment` relie échec → contrainte. Tables : `ghost_hypotheses`, `ghost_strategy_experiments`.


