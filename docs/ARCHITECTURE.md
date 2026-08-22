# Architecture Geniuspace

> Une stack : **Laravel 11 + Blade + Alpine**, dans `geniuspace/`.
> Un **univers** (Node) = un lieu indexable (club, jobboard, galerie). Les **liens** relient parent → enfant.

## Source de vérité

| Couche | Où |
| --- | --- |
| HTTP, vues, SEO | `geniuspace/app/Http`, `geniuspace/resources/views` |
| Graphe | tables `nodes` + `edges` |
| Vera (offres, tests, fiches) | `geniuspace/app/Support/VeraCatalog.php` + JSON `Support/vera/` |
| Lumen (galerie) | seeder `DualWorldsSeeder` + vues living |
| Champs personnalisés | table `cck_fields`, builder dans le Studio |

Il n’y a plus d’app TanStack / React dans ce dépôt.

## Fichiers clés

| Fichier | Rôle |
| --- | --- |
| `geniuspace/routes/web.php` | Routes SSR |
| `geniuspace/app/Http/Controllers/UniverseController.php` | Univers living (Lumen, clubs) |
| `geniuspace/app/Http/Controllers/VeraController.php` | Jobboard Vera |
| `geniuspace/app/Support/VeraCatalog.php` | 35 offres, lexique, tests, entreprises |
| `geniuspace/app/Support/RoomCatalog.php` | Salles = pages indexables |
| `geniuspace/resources/views/layouts/vera.blade.php` | Peau papier Vera |
| `geniuspace/resources/views/layouts/app.blade.php` | Peau Geniuspace / Lumen |
| `geniuspace/database/migrations/` | Schéma PHP. Ne pas réécrire une migration déjà poussée : en ajouter une. |

## Peaux

- `skin = vera` → layout papier, jobboard
- sinon → lieu de vie (hero + dock)

Les onglets d’un univers viennent de la table des onglets (pas hardcodés dans le layout living).

## Auth

Lectures publiques (SEO). Écritures (forum, Drive, studio) : utilisateur connecté. Prod : `node_staff`.
