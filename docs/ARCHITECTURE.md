# Architecture NODUS

> Pour les développeurs humains et les IA. Ne pas inventer un 9e produit : étendre le Node.

## Principe

Un **Node** = micro-univers indexable (personne, personnage, série, offre, produit…).
Les **edges** relient parent → enfant (`parent_of`, `portrays`, `features`, …).

Exemple canon :

```
richard-dean-anderson  --parent_of-->  jack-oneill
richard-dean-anderson  --parent_of-->  angus-macgyver
one-piece              --parent_of-->  monkey-d-luffy
maison-orion           --parent_of-->  lead-game-designer
```

## Fichiers clés

| Fichier | Rôle |
|---|---|
| `src/lib/graph.ts` | Types du graphe + CCK + habitat |
| `src/lib/graph-api.ts` | Server functions (lire/écrire PGLite) |
| `src/lib/cck.ts` | Contrat CCK — **lire avant d’ajouter un champ métier** |
| `src/lib/skins.ts` | Quelle peau (living / vera / cinema) selon le kind |
| `src/components/living-world.tsx` | Lieu de vie fans |
| `src/components/vera-house.tsx` | Job board recruteur |
| `src/components/cck-builder.tsx` | Builder visuel de champs |
| `src/components/salon-map.tsx` | Salon 2D recruteur (Gather-like) |
| `src/components/quest-path.tsx` | Quêtes à la place du CV |
| `src/components/skill-tree.tsx` | Arbre de talents / offres |
| `src/components/holo-player.tsx` | Cockpit VOD sécurisé (modes) |
| `src/lib/video-mode.ts` | Copy formation / jeu / shop / entretien |
| `src/lib/theme.tsx` | Dark / light |
| `migrations/*.sql` | Schéma + seed. Ne jamais editer une migration déjà appliquée : en ajouter une. |

## Peaux

`skinOf(node)` :

- `company` / `job` → VeraHouse
- sinon → LivingWorld (hero + dock)

Les onglets ne sont **pas** hardcodés dans le JSX principal. Ils viennent de `node_tabs`.
Fallbacks : `FALLBACK_TABS` / `VERA_TABS` si la table est vide.

## Auth

Écritures (forum, Drive, onglets, CCK, boutique) : `authMiddleware`.
En démo, tout membre connecté peut contribuer aux univers seed (owner_id null).
**Prod :** filtrer sur `node_staff.role ∈ (owner, admin, mod)`.

## 3D

`RealmCanvas` charge `three` en dynamic import (SSR-safe). Les sprites sont les portraits `/public/realms/*`.
