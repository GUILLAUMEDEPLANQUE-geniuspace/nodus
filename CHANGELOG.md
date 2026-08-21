# Changelog — NODUS

Format : ce qui est **dans le code**, pas la vision PDF.

## 0.4.0 — 2026-08-21

### CCK étendu (blogs, boutique, vidéo, jobs)

- Table `cck_fields` : `field_type`, `target_kind`, `target_id`
- Types : `text | html | choice | media | relation | scale`
- Cibles : `node | thread | product | media`
- Seed One Piece boutique + journal + Marineford
- Composant unique `CckPanel` (ne plus hardcoder les champs métier)

### Habitat d’univers

- Dock bas sticky, onglets data-driven (`node_tabs`)
- Studio : ajouter onglets, nommer admin/mod
- Forum (catégories, sujets, réponses) + bulle qui monte à chaque post
- Guilde (canal type Telegram)
- Guides wiki, boutique, playlists, Drive glisser-déposer
- Peau Vera pour les maisons recruteur

### Graphe

- Nodes + edges parent/enfant (Anderson → O’Neill / MacGyver, One Piece → équipage)
- Fiches vidéo à chapitres / transcript

## 0.3.0 — 2026-08-21

- Sanctuaire spatial (retiré au profit du lieu de vie ciné — trop froid)
- Drive / wiki / chronologie en base

## 0.2.0 — 2026-08-21

- Graphe parent-enfant, constellation, wizard de création
- Seed Stargate / One Piece / Orion / Atelier

## 0.1.0 — 2026-08-21

- Scaffold TanStack Start, auth, PGLite
