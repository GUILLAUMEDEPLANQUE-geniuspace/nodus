# Architecture Geniuspace

> Une stack : **Laravel 13 + Blade + Alpine**, dans `geniuspace/`.
> Un **univers** = un lieu indexable (club, jobboard, galerie). Les **liens** relient parent → enfant.

## Moteur secret

Le graphe (`nodes` + `edges`) et les champs de fiche (`cck_fields`) sont **l’unique source de vérité métier**. Toute valeur produit (fiabilité, alignement, carnet, héritage, reco) en est dérivée. Aucune surface utilisateur n’expose Node, edge, CCK, parent_of ou « graphe » : elle affiche des lieux, des preuves et des décisions.

Code : `geniuspace/app/Support/Engine.php`, `Vocab.php`, `FieldTemplates.php`, `Chrome.php`.
Dépendances : `docs/KERNEL.md`. Contrat : `docs/INVARIANTS.md`.

## Éditeur de monde

Un univers se compose A→Z sans code : thème (tokens, logo, fond vidéo), calques de scène, boutons (hero, boutique, player, panier), plan des salles, presets de template.

| Visible | Derrière |
| --- | --- |
| « Réserver le goodie » | `node_actions` scope `shop_card` |
| Couleur de la maison | `node_theme.primary` |
| Accroche sur le hero | `node_scene_layers` kind `text` |
| Salon masqué | `node_tabs.enabled = 0` |

Handlers figés : `addToCart`, unlock, grants, checkout. Custom = label, ordre, présence, style.

## Médias = preuves = lieux

Le player, le Drive et l’éditeur d’images ne sont pas trois outils. Un chapitre ouvre une fiche. Un teaser fini tamponne le carnet. Un drop pose une relique. Un fichier locké se mérite (épreuve, achat, rôle).

| Visible | Derrière |
| --- | --- |
| « Continuer l’épreuve » | preuve tenue **ou** achat → `grants`. Un POST `/unlock` nu est 403 |
| « Kit consignation » à 0:04 | `media_doors` kind `drop` → relique + carnet |
| Brief locké | `drive_files.lock_kind=quest` + coffre `private/` |
| Widget carrière | `/embed/{slug}` teaser + délai + CTA |

Code : `Grantor.php`, `SignedMedia.php`, `MediaController`, `GrantController`. HLS plus tard (`Hls.php`).

**Omni-média.** Un chapitre `3:15 Labo` (ou Mixer / Tactique) n’est pas un timecode mort : `Omni::beats` pause la VOD et le cadre devient l’éditeur / le mixer / le tableau. Tenir = grant `omni`. Sur un teaser court, 03:15 est remappé.

**Rideau.** `Spoiler` filtre aussi le Ghost (`context.rideau.caches`). Geniuspedia ne sort pas du Node.

**Paiement.** `Pay::cents` (prix tenu) → Stripe ou ledger. `Maison::attachCatalog` pose les 35 missions sur un `maison-rh` neuf. `/n/vera` inchangé.

UI : `/n/{slug}/monde` (Structure / Design / Action / Motion).

| Visible | Derrière |
| --- | --- |
| Maison Relève | Node `company` + arêtes vers les offres |
| Salaire 34–40 k€ | Champ `salaire` (échelle + unité) |
| Épreuve validée | Arête `validated` vers le carnet |
| Cette maison répond en 4 jours | Héritage du champ `delai_reponse` de la maison |
| Alignement fort | `Engine::alignment(carnet, offre)` |

## Source de vérité

| Couche | Où |
| --- | --- |
| HTTP, vues, SEO | `geniuspace/app/Http`, `geniuspace/resources/views` |
| Graphe | tables `nodes` + `edges` |
| Champs | table `cck_fields` (`field_key`, `unit`, min/max) |
| Carnet | salle `RoomCatalog` `carnet` → `/n/{slug}/carnet` |
| Vera (éditorial packs) | `geniuspace/app/Support/VeraCatalog.php` + JSON `Support/vera/` — posé dans le graphe au seed |
| Lumen (galerie) | seeder `DualWorldsSeeder` + vues living |

Il n’y a plus d’app TanStack / React dans ce dépôt.

## Fichiers clés

| Fichier | Rôle |
| --- | --- |
| `geniuspace/routes/web.php` | Routes SSR + `/v1/nodes/{slug}/fields` |
| `geniuspace/app/Support/Engine.php` | Voisins, fil d’Ariane, héritage, alignement, reco |
| `geniuspace/app/Support/FieldTemplates.php` | Modèles de métier |
| `geniuspace/app/Http/Controllers/VeraController.php` | Jobboard Vera |
| `geniuspace/app/Support/VeraCatalog.php` | 35 offres, lexique, tests, entreprises |
| `geniuspace/database/migrations/` | Schéma PHP. Ne pas réécrire une migration déjà poussée : en ajouter une. |

## Peaux

- `skin = vera` → layout papier, jobboard
- sinon → lieu de vie (hero + dock)

## Auth

Lectures publiques (SEO). Écritures (forum, Drive, studio, magazine, builder, create, lore) : utilisateur connecté **et** `node_staff` pour sculpter. GET `/studio`, `/monde`, `/builder`, `/ghost/gym`, `/ghost/editor`, `/ghost/plan` : staff. `Acl` n’auto-promouvoit plus. `/login/demo` : local seulement. Créer un lieu pose le compte comme owner.
