# Changelog — NODUS

Format : ce qui est **dans le code**, pas la vision PDF.

## 2.0.0 — 2026-08-22 — Laravel

Refonte Blade + Alpine (SSR). App dans `geniuspace/`.

- SEO : title / canonical / JSON-LD / sitemap générés serveur
- Vidéo : jetons HMAC, fichiers dans `storage/app/media` (prod = R2)
- Pas YouTube. Pas Bagisto. Shop Geniuspace + Command Center
- o2switch = PHP ; MP4 = R2 / VPS, jamais le mutualisé

## 1.0.0 — 2026-08-22 — Geniuspace

Rebrand. Command Center produit. Player HTML5 réel.

### Produit / RWA
- `/n/:slug/p/:id` = Command Center 3 colonnes (vidéo, galerie, chaudron)
- Drag → panier + jauge énergie + loot Drive
- Crowd-goal, toasts live, preview AR (CSS 3D)
- JSON-LD `VisualArtwork` si `rwa`
- Tokens or/encre (pas le cyan du mockup)

### Vidéo
- `<video>` + MP4 `/media/*.mp4` + `timeupdate` (paywall teaser, ads SEM)

### Reco / i18n / ATS / 3D
- `getMatches` : mêmes kinds que tes visites
- `node_i18n` FR/EN/JA sur les fiches
- 7 étapes ATS éditables (Studio)
- Reliques 3D (`node_relics`) sur la carte
- `semanticWeave` : Legacy → page wiki (maillage). Prod = LLM

## 0.9.1 — 2026-08-22

### SEO : public vs éditeur
- La **page** (`/n/:slug`, `/p/:id`, `/v/:id`, `/t/:id`) reste publique et indexable — c'est le moat
- L'**éditeur** title/description/keywords/noindex est owner/admin (`saveNodeSeo` + ACL)
- Boutique : lien « Fiche produit » (publique), plus le faux bouton « Fiche SEO »

## 0.9.0 — 2026-08-22

Couche plateforme. Lectures toujours publiques (SEO). Écritures auth + ACL.

### Drive
- `drive_blobs` : fichier < ~0,9 Mo stocké (base64). `locked` jamais dans `getNodeUniverse`
- `uploadDriveBlob` / `readDriveBlob` : staff ou grant
- Au-delà : métadonnée seule (prod = S3 + scan)

### Commerce
- Panier persisté, checkout démo `card` | `crypto`
- Commande `paid` → `video_grants` sur les VOD du Node
- Prod : webhook Stripe seul autorisé à passer `paid` (commenté dans `checkoutCart`)

### Profils
- Cover, bio, locale, univers suivis, visites
- Plus de liens en dur One Piece / Orion

### ACL
- `owner > admin > mod > member`
- Seed Node : fondateur = admin + dock + wiki + quête recruteur

### ATS
- `candidates` + `candidate_answers`
- Quêtes persistées (step 1–7)
- Vivier staff dans le Studio Maison

### Social
- Notifications (cloche)
- DM par canal d'univers (`/inbox`)
- Ranks forum (Mousse / Marin / Expert lore)

### i18n
- FR / EN / JA (chrome UI). Le contenu d'un Node reste la langue de l'auteur

### Reco
- `node_visits` à chaque ouverture de fiche

## 0.8.1 — 2026-08-22

### Commerce + partage
- Produit : prix, note, avis, stock, Offer JSON-LD
- URL `/n/:slug/p/:id`
- ShareBar (Web Share, copie, X) sur fiche produit et cockpit vidéo
- Vidéo boutique/formation : panneau prix + note + carte/crypto

## 0.8.0 — 2026-08-22

### Moteur vidéo sécurisé
- Modes : lore, formation, jeu, boutique, entretien — même cockpit, copy différente
- Paywall teaser (freemium). URLs locked **jamais** dans le HTML public
- Drive séquencé : PDF/zip/obj par chapitre, cadenas jusqu'au grant
- Ticker créateur, holo-drop, console CCK, connexions graphe
- `unlockVideo` (auth) → grant + URLs. Prod = Stripe/HLS jetons

## 0.7.0 — 2026-08-22

### Onglet Vidéos (fiches JoomCCK-grade)
- Onglet **Vidéos** dédié (plus caché dans le Drive / Studio)
- Console : genre, durée, saison, épisode, langue, difficulté, ruban
- Chapitres seek + transcript + JSON-LD `VideoObject` + `Clip`
- URL unique `/n/:slug/v/:id`
- Les vidéos des **enfants** (Luffy, O’Neill…) remontent dans l’univers parent

### Dock
- Dédoublonnage `guilde` / `guildes` (même stem)
- Reliques relabel **Drive** — l’engrenage reste le Studio de config

## 0.6.0 — 2026-08-21

### Holo-Forum (Scroll & Dive)
- Cartes pleine hauteur, snap vertical (pas une liste de textes)
- Dive : panneau Legacy **SEO** vs **Live** (Telegram)
- Tags produits / reliques dans la saisie
- Éclatement sémantique : « Détacher en nouveau sujet » → URL unique
- Route `/n/:slug/t/:tid` + JSON-LD `DiscussionForumPosting` + sitemap

### Thème dark / light
- `data-theme` + tokens or/encre (pas de 3e couleur)
- Toggle dans le shell, persisté `localStorage`

## 0.5.0 — 2026-08-21

### Builder CCK visuel
- Palette de types (`text`, `html`, `choice`, `media`, `relation`, `scale`)
- Cible `node | thread | product | media`
- Preview live (`CckBuilder` dans le Studio)
- API `addCckField`

### Vera expérientiel (pas un job board)
- **Salon spatial 2D** (Gather/Topia) : avatar, stands, visio de proximité
- **Terminal Foo** : tapez `hire` pour déverrouiller
- **Arbre de talents** : offres en constellation RPG
- **Quêtes** à la place du CV (situation + 2 choix)

### SEO masterclass par Node
- Title / description / canonical / OG uniques (`src/lib/seo.ts`)
- JSON-LD `@graph` : JobPosting, Product, Article, TVSeries, Person, Breadcrumb
- `/sitemap.xml` + `robots.txt`

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
