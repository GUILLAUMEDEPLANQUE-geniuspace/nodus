# Architecture Geniuspace

> Une stack : **Laravel 11 + Blade + Alpine**, dans `geniuspace/`.
> Un **univers** = un lieu indexable (club, jobboard, galerie). Les **liens** relient parent → enfant.

## Moteur secret

Le graphe (`nodes` + `edges`) et les champs de fiche (`cck_fields`) sont **l’unique source de vérité métier**. Toute valeur produit (fiabilité, alignement, carnet, héritage, reco) en est dérivée. Aucune surface utilisateur n’expose Node, edge, CCK, parent_of ou « graphe » : elle affiche des lieux, des preuves et des décisions.

Code : `geniuspace/app/Support/Engine.php`, `Vocab.php`, `FieldTemplates.php`.

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

Lectures publiques (SEO). Écritures (forum, Drive, studio) : utilisateur connecté. Prod : `node_staff`.
