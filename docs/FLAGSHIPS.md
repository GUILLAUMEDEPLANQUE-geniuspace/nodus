# 10 flagships — bible produit (dev)

Surface ≠ moteur. L’utilisateur voit un lieu, un hôte, un passage, une preuve.
Le code voit un Node, des champs, des arêtes, un grant. **Jamais l’inverse dans l’UI.**

Mots interdits en public : Node, edge, CCK, graphe, parent_of, JoomCCK, Ghost Node, LLM.

| # | Id | Surface | Hôte | Passage | Schema.org | Démo |
|---|----|---------|------|---------|------------|------|
| 1 | `vault` | Le Coffre | L’hôte | Passage | Product | `/n/coffre-celeste/p/p-cel-1` |
| 2 | `terrain` | Le Terrain | Maître du donjon | Warp | VideoGame | `/n/terrain-midgar` |
| 3 | `atelier-anime` | L’Atelier | Le concierge | Déchirure | TVSeries | `/n/atelier-clamp` |
| 4 | `territoire` | Le Territoire | Le guide | Corridor | Country | `/n/territoire-japon` |
| 5 | `maison-rh` | La Maison | L’accueil | Vers la mission | Organization + JobPosting | `/n/vera` (flagship historique) |
| 6 | `scene` | La Scène | Le régisseur | Featuring | MusicAlbum | `/n/scene-neon` |
| 7 | `arene` | L’Arène | Le speaker | Sortie joueur | SportsTeam | `/n/arene-reims` |
| 8 | `labo` | Le Labo | Le tuteur | Vers la mission | Course | `/n/labo-next` |
| 9 | `plateau` | Le Plateau | L’AD | Rôle | Movie | `/n/plateau-nuit` |
| 10 | `table` | La Table | Le sommelier | Vers le producteur | Recipe | `/n/table-aop/p/p-aop-1` |

Code : `app/Support/Flagships.php` (spec), `Ghost.php`, `Lore.php`, `Chrome` presets, vues `resources/views/flagships/`.

Création : `/create` groupe Flagship. Spec lisible : `/flagships`.

## Flagship vs démarrage

| | Flagship (10) | Démarrage (~50) |
|---|---|---|
| Où | Groupe **Flagship** en tête de `/create` | Groupes Fandom / Jeux / Emploi / … |
| Contrat | Hôte + boucle + passage + preuve. Démo visitable. | Structure, salles, SEO. Le fan habille. |
| Code | `Flagships.php` + seeder + HUD `flagships/play` | `WorldTemplates.php` |
| Exemples | Coffre, Terrain, Vera, Table | Hub manga, Club auto, BTS |

Les 50 ne sont **pas** des skins des 10. Un hub manga n’est pas L’Atelier tant qu’il n’a pas le rideau anti-spoiler + concierge + cel.

Démo jouable (seed) : `/n/coffre-celeste`, `/n/terrain-midgar`, `/n/table-aop`, `/n/vera`, plus les 6 autres flagships habillés (hôte, objet, guide, carnet).

## 1. Le Coffre — boutique physique / reliques

**Innovation.** L’hôte est l’avatar permanent du vendeur. Canvas infini, scroll narratif, hotspots = champs, checkout intra-page. Négociation → **paiement au prix tenu** (Stripe en prod, ledger en démo).

**Mécanique.** Relique au centre. Un passage (voisin) aspire vers l’auteur / l’univers. L’hôte négocie dans le plancher (`prix_plancher`). Une mise corrige une métadonnée.

**Moat.** Shopify = SKU. Ici regarder / cliquer / débloquer / prouver / acquérir = même coffre. JSON-LD Product + isRelatedTo.

**SEO.** Product, Offer, isRelatedTo Person. Jamais de page panier orpheline.

## 2. Le Terrain — jeux vidéo

Patch = curseur d’arc. Build = épreuve. Loot = relique. VOD → pause → labo (arbre, route). Warp arme → boutique d’un autre créateur.

**SEO.** VideoGame + HowTo + VideoObject chapitré.

## 3. L’Atelier — anime / manga

Arc = rideau anti-spoiler. Perso = lieu. Cel = relique du Coffre. Le concierge **filtre les fiches** au-delà du curseur (contexte Ghost + Geniuspedia). Yue n’existe pas avant la Finale.

**SEO.** TVSeries + Person + Product (cel).

## 4. Le Territoire — pays

Atlas habité. Corridor = arête, pas un lien footer. Hreflang natif. Un lieu ouvre un producteur, une table, une offre.

**SEO.** Country + Place + Article + Offer.

## 5. La Maison — recrutement

L’accueil présélectionne (3 questions). Juste → preuve → épreuve. Le carnet voyage de maison en maison. **Vera reste le flagship historique** (`/n/vera`, 35 offres). Un template `maison-rh` neuf reçoit le même catalogue (arêtes `offers` vers les missions).

**SEO.** Organization + JobPosting, salaire en clair.

## 6. La Scène — musique

Canvas waveform. Scroll = timecode. **Omni** : à 03:15 (0:03 sur teaser court) le clip s’efface, le mixer stems prend le cadre. Stems lockés = fichiers signés. Drop = Event.

**SEO.** MusicAlbum + MusicRecording + Event + Product vinyl.

## 7. L’Arène — sport

HUD stade. Joueur = fiche. **Omni** : VOD → pause → tableau tactique cliquable (N°9, N°10). Match = SportsEvent URL.

**SEO.** SportsTeam + SportsEvent + Person.

## 8. Le Labo — formation

Cœur omni-média : à **03:15** la vidéo s’efface, l’éditeur / le simulateur prend le cadre (sur teaser court : chapitre `0:03 Labo`, remappé si le fichier est trop court). Exo tenu = grant. Passage vers une mission qui recrute la compétence.

**SEO.** Course + HowTo + VideoObject + Occupation.

## 9. Le Plateau — cinéma

Dailies signées par rôle. Call sheet = fiche. Acteur → tous ses rôles.

**SEO.** Movie + Person + VideoObject. Rushes jamais en clair.

## 10. La Table — gastronomie

Nappe canvas. Service = scroll. Plat → producteur → millésime relique. Le sommelier accorde et vend dans la fourchette.

**SEO.** Recipe + Place + Product AOP.

## Règles dev

1. Nouvelle info métier = champ de fiche, pas une colonne SQL.
2. Nouveau lien = arête, pas un `href` en dur.
3. Unlock = grant en base, sinon 403.
4. Hôte = `Ghost::speak` (lore + plancher). Pas un `alert()`.
5. Passage = `Engine::neighbors`, rendu wormhole.
6. Preuve de lore = `lore_proposals` + grant `stake`.
7. Canvas drag des calques = P2 polish, pas le contrat.
