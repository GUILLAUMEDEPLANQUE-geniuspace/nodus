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
16. Interface : français d’abord. Les mots internes (PPQC, Pacte, Passport, CCK, Node, edge, graphe) s’expliquent ou se traduisent. URLs publiques : `/tarif`, `/carnet`, `/delais`.
17. Toute nouvelle info métier = champ de fiche (`cck_fields`, `field_key` stable) **ou** arête typée. Une PR qui ajoute une colonne SQL métier (`salary`, `remote`, `fruit`) est refusée, sauf migration d’infra.
18. Toute nouvelle UI lit le moteur (`Engine::fields` / `neighbors` / `inherit` / `alignment`). Pas de `salary={55000}` magique.
19. Le jargon graphe / CCK / Node / edge / parent_of est **interdit en copy produit**. Autorisé : code, docs techniques, commentaires.
20. Chaque insight UI (« Alignement fort », « Aussi dans cet univers », « Cette maison répond en 4 jours ») doit être recalculable par une fonction pure sur nodes / edges / champs.
21. Versionner le schéma : `field_key` stables, labels traduisibles, `schema_version`. Ne pas casser `GET /v1/nodes/{slug}/fields`.
22. **Chrome = données.** Boutons, dock, paywall, calques, tokens : tables `node_theme` / `node_actions` / `node_scene_layers` / `node_tabs`. Pas de libellé métier en dur dans les vues. Handlers (panier, unlock) restent dans le code.
23. **Éditeur de monde** : `/n/{slug}/monde`, modes Structure / Design / Action / Motion. Blade, pas React. Presets `living` | `merch` | `vera` à la création.
24. Custom total sur identité, scène, navigation, actions, champs, contenus, droits. Contraint sur player, panier, grants, graphe.
25. Le flagship `/n/vera` reste le jobboard éditorial (slug exact). Une maison née du template Vera passe par l’éditeur de monde.
26. **Médias = portes, pas des fichiers.** Unlock = ligne `grants` (user/session × média), jamais `granted=true` en JS. JSON-LD gated : pas de `contentUrl`. Fichiers lockés dans `storage/app/private`, servis par `/play` si grant ou staff.
27. **Carnet = déblocages + export JSON.** Relique d’un chapitre, brief d’épreuve, certificat d’achat : même coffre. Surface : « tenu », jamais « grant ».
28. Un calque image / un chapitre `@slug` ouvre un lieu. Pas un timecode mort.
29. **Unlock = grant en base, sinon 403.** Le teaser est un fichier public distinct. Jamais le MP4 privé en `preview=1`. Fichier Drive locké = `/play` signé 15 min, jamais d’URL permanente.
31. **Dix flagships.** Coffre, Terrain, Atelier, Territoire, Maison, Scène, Arène, Labo, Plateau, Table. Spec : `Flagships.php` + `/flagships`. Surface ≠ jargon. Hôte = Ghost::speak. Passage = neighbors. Preuve = lore_proposals.
