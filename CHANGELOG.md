# Changelog — Geniuspace

Format : ce qui est **dans le code**, pas la vision PDF.

## 2026-08-27 — Coffre : seeder, profil, unlock, drop

- **Seeder.** Accolade en trop dans `DatabaseSeeder` : `php artisan db:seed` reparsait.
- **Profil.** `/profil` = coffre privé (auth). `/profil/{id}` = carte publique. DM / notifications / inventaire ne fuient plus.
- **I-GRANT.** `POST /unlock` n’écrit un grant que si preuve (quest) ou achat (shop). Plus d’unlock magique.
- **Drop.** Pas de porte à cet instant → 403. Plus de relique fabriquée. Plus d’auto-unlock via drop.
- **Omni.** Claim déclaré, pas une preuve tenue.
- **Magazine.** `GET /n/{slug}/blog/{aid}` (plus de 404 après création).
- **Bounties.** Claim atomique `WHERE status=open`. Forum : thread appartient au lieu.
- **Paiement.** `settle()` dans une transaction.
- **Docs.** Laravel 13 (plus 11).

## 2026-08-27 — Contrat Ghost (transitions vérifiables)

- **GhostActionContract.** INTENT → PLAN → CONTRACT → AUTHORIZE → PREVIEW → APPLY → OBSERVE → VERIFY. Produit : OBSERVE / PREPARE / ACT. Technique : OBSERVE / PROPOSE / AUTHORIZE / EXECUTE / VERIFY.
- **Deux réalités.** World truth = Engine. Agent belief = visitor. Une hallucination n’entre pas. `GhostMemory::mixed()` reste false.
- **Provenance.** `ghost_transitions` : STATE' ← produced_by ACTION ← verified_by VERIFICATION. PLAN n’écrit pas de transition.
- **Claims.** `GhostVerifier::rule` : 180 € + prix 180 → PASS. 999 € → FAIL. Aucune preuve → UNKNOWN.
- **Manifeste.** READ / PREPARE(=PROPOSE) / ACT / DENY. Ghost ne reçoit pas les ops DENY.
- **Tests.** `GhostContractTest`, `GhostProvenanceTest`. 15 invariants de sécu inchangés + 5 d’architecture.
- **Docs.** GHOST V4, KERNEL, INVARIANTS architecture, FOR_AI #43.

## 2026-08-23 — Invariants (stop concepts)


- **ACL.** `Acl::canWrite` n’écrit plus `node_staff`. Pas d’auto-owner. Forum, Drive, Magazine, Image, Create, weave, lore : authentifié. GET studio / monde / builder / gym / editor / plan : staff.
- **Routes dev.** `/login/demo` et `/llm/tools` : local / tests. Plus de bouton Créateur en prod.
- **Kernel.** `Engine::myCarnet` / `publicMedia` → `Grantor`. Engine n’appelle plus Grantor. `docs/KERNEL.md`. Ghost n’écrit pas un grant.
- **Coffre.** `/play` refuse `..`. `preview=1` refuse `private/`.
- **Prix.** `Pay::cents` passe par `Invariants::heldCents` (jamais sous le plancher).
- **Ghost.** `/ghost/context`, `/skills`, `/editor`, `/plan`, `/gym` = staff. Chat public. Autonomie = `Invariants::AUTONOMY_CAP`.
- **Docs.** `docs/INVARIANTS.md` (15 règles). FOR_AI numéroté 1–42, plus de doublons. `Ghost::speak` → `Ghost::reply`. URLs Vera `/n/vera/tarif` `/n/vera/carnet` `/n/vera/delais`.
- **Tests.** `InvariantTest` + `KernelBoundaryTest`. Suite PHPUnit.

## 2026-08-22 — Ghost Action (éditeur + business)

- **DSL.** `field.add` / `media.insert` / `playlist.insert` / `field.move`. Jamais `DB::insert` depuis le LLM. Position = `after` / `before`, pas `sort = 17`.
- **Blocks.** La fiche est une composition (texte, champ, image, playlist, vidéo). `read_editor` avant toute écriture.
- **PLAN ≠ APPLY.** Preview, Appliquer, Annuler. Table `ghost_actions` = transaction + snapshot.
- **Curseur Ghost.** `editor_context.cursor` : « ici » a une ancre. Sinon Ghost demande « Où ? ».
- **Médias / playlists.** `search_media` / `search_playlist`. Plusieurs hits → « Laquelle ? ». Jamais inventer.
- **CckCatalog::capabilities.** Contrat de capacité (insert/move/delete, placement field|block).
- **EditorManifest.** Ce que Ghost a le droit de faire sur cette page.
- **Business.** READ (143 acheteurs) / PREPARE (campagne 15 %) / ACT (envoi). Volume ≥ 100 ou remise > 10 % → confirm. Remboursement, suppression client, paiement → deny.
- **API.** `GET /n/{slug}/ghost/editor` · `POST /n/{slug}/ghost/plan` · `apply` · `undo`. Apply = staff admin.
- **Studio.** Copilote Blade + Alpine. Le LLM propose. Le moteur exécute.

## 2026-08-22 — Ghost croissance (V2)


- **Boucle.** Chaque tour laisse un fait, une expérience, parfois une erreur. Pas un dump de chat.
- **Cycle de vie.** unknown → observed → supported → verified → trusted. contradicted → stale → revoked. `GhostLearn::promote`.
- **Confiance.** Une hallucination n’entre pas en mémoire (`mayRemember`).
- **Erreurs / règles.** Tables `ghost_failures`, `ghost_rules`. Wikipedia → R-crawl. « je débloque » → R-act.
- **Skills qui grandissent.** Stats `ghost_skill_stats`. Candidat `verified_job_matching` après assez de match_job — **validation humaine** (`POST /n/{slug}/ghost/skills`).
- **Gym.** 12 épreuves, 8 niveaux. Page `/n/{slug}/ghost/gym`. JSON maturité `/n/{slug}/ghost/maturity`. Autonomie plafonnée à 54.
- **Pas de vector DB.** Le graphe + le pack du lieu. Pas de crawl.

## 2026-08-22 — Ghost cognitif (V1)

- **Orchestrateur.** `Ghost::reply` = mémoire → planner → multi-outils → compose → vérificateur. Plus `intent → 1 tool`.
- **Mémoire structurée.** Tables `ghost_facts` / `ghost_experiences` / `ghost_tool_stats`. Un fait = sujet, prédicat, objet, confiance, source, statut. Pas un dump de chat.
- **Skills.** find_product, negotiate, match_job, verify_claim, inspect_carnet, investigate_place, build_application, greet.
- **Raisonnement.** `compare_products`, `rank`, `match_user_job`, `search_graph`, `find_path`.
- **Plafond.** OBSERVE / SUGGEST / PREPARE / ACT. Achat, candidature, unlock, graphe : jamais seuls.
- **Vérificateur.** Chiffre hors coffre, jargon, « je débloque » → `verified-block`.
- JSON : `goal`, `skill`, `plan`, `verify`, `memory`, `permission`.

## 2026-08-22 — Omni, rideau, maison, Stripe tenu

- **Omni-média.** Labo / Scène / Arène : à 03:15 (0:03 sur teaser) le player **bascule le cadre** (éditeur, mixer stems, tableau tactique). Tenir l’exo = preuve `omni`. `Omni.php` + overlay `omni-frame`.
- **Rideau Atelier.** Le concierge filtre fiches / Geniuspedia au-delà du curseur. Yue (Finale) n’existe pas à Cour 1. Défaut curseur = 1 sur l’Atelier.
- **Maison neuve.** Vera reste `/n/vera`. Un `maison-rh` reçoit les 35 missions (`Maison::attachCatalog`).
- **Paiement au prix tenu.** `held_cents` → Stripe Checkout si `STRIPE_SECRET`, sinon ledger au même montant. Plus un panier cosmétique.
- **Geniuspedia.** Coffres du Node seulement. URL externe → URL du lieu. Pas de crawl.

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
