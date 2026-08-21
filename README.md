# NODUS

OS de micro-univers. Un Node n’est pas un profil : c’est un lieu de vie (manga, série, maison recruteur, atelier) avec graphe parent/enfant, Drive, CCK, forum, boutique, playlists.

Repo : [GUILLAUMEDEPLANQUE-geniuspace/nodus](https://github.com/GUILLAUMEDEPLANQUE-geniuspace/nodus)

## Pour les développeurs

Lire dans l’ordre :

1. [CHANGELOG.md](./CHANGELOG.md) — ce qui est réellement livré
2. [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) — graphe, peaux, dock
3. [docs/CCK.md](./docs/CCK.md) — champs custom (JoomCCK / Vera)
4. [docs/FOR_AI.md](./docs/FOR_AI.md) — consignes pour les agents IA

## Stack

- TanStack Start + React 19 + Tailwind v4
- PGLite (Postgres embarqué) via `migrations/*.sql`
- Auth Better Auth (activée)
- three.js pour la carte 3D des univers manga/série

## Lancer

```bash
npm install
npm run dev
```

Les migrations SQL dans `/migrations` s’appliquent toutes seules au boot PGLite.

## Peaux

| Kind Node | Peau | Exemple |
|---|---|---|
| series / character / franchise | `LivingWorld` — hero ciné, dock fans | One Piece, SG-1 |
| company / job | `VeraHouse` — job board | Maison Orion |
| person | LivingWorld ciné | Richard Dean Anderson |

Le **créateur** configure les onglets du dock (`node_tabs`), le staff (admin/mod), le CCK, le Drive.

## Ce que le CCK couvre aujourd’hui

Oui, le CCK est branché sur :

- **Offres** (salaire, remote, stack, épreuve, contrat, séniorité)
- **Personnages** (fruit, prime, rôle, armes)
- **Boutique** (SKU, stock, matière, relique Drive)
- **Journal / blog** (temps de lecture, sources, tags)
- **Fiches vidéo** (langue, arc, schema)

Ce n’est pas encore l’éditeur visuel JoomCCK complet (pas de builder de types dans l’UI admin). Les types de champs existent en base (`text`, `html`, `choice`, `media`, `relation`, `scale`). Voir `docs/CCK.md`.
