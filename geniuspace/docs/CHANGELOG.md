# Changelog Geniuspace

## 2026-08-22 — Levier leader (10)

- **URL de salles SSR** : `/n/{club}/forum`, `/videos`, … + canonical + JSON-LD ItemList. Le dock met l’URL à jour.
- **Maillage** : `Linker` — les titres des fiches du club deviennent des liens dans le forum Legacy.
- **Radar SEO** : `/n/{club}/radar` (owner) — description manquante, fiches orphelines, mots du forum sans fiche, salles sans SEO, annonces sans ville.
- **Packs vides** atelier : Club auto / manga / boutique expert / jobs = salles cochées, **contenu = 0**.
- **Comparateur** : `/n/{club}/vs/{a}/{b}` + ItemList.
- **Annonces Offer + geo** : colonnes `city`, `lat`, `lng` + JSON-LD `areaServed` / `GeoCoordinates`.
- **Holo-fiche chapitres** : parse `00:12 Titre` → schema.org `Clip` + seek player.
- **Graphe public** : `/g/{slug}` HTML + `/g/{slug}.json` (parents / rôles).
- **Digest Legacy** : `/n/{club}/digest` — meilleures réponses, publication article indexable.
- **Sous-domaine** : champ Studio `host` → `{host}.geniuspace.com` (middleware `ClubHost`). Aperçu `/w/{slug}` (marque blanche).
- **Sitemap club** : `/n/{club}/sitemap.xml` (salles, fiches, sujets, produits, vidéos).
- **Recherche interne** : `/n/{club}/q?q=` — score titre > corps, fiches + sujets + pièces + vidéos.

## 2026-08-22 — SSR réel (trou n°1)

- Salles : plus de `<template x-if>` / `x-show` pour le living. `/n/{club}/forum` ne sert **que** le forum (Blade `@if`). Dock = vrais `<a href>`.
- Fiches nested : `/n/{club}/f/{slug}` + JSON-LD Person/CreativeWork + graphe.
- Vidéos au slug : `/n/{club}/v/volonté-du-d` (id encore accepté).
- Maillage aussi sur le feed forum, pas seulement la fiche sujet.
- Digest : article + `Mail::raw` vers les owners (mailer `log` tant que SMTP n’est pas là).

## 2026-08-22 — Cinq moats

1. **Bounties SEO** — radar → quête guilde → `LlmJudge` → wiki + NodeCoins + titre.
2. **Curseur anti-spoiler** — `appear_order` + arcs. Le graphe et la boutique se recroquevillent.
3. **Citation @graphe** — mini-fiche in-chat, KOC 5 % si achat.
4. **Sac à dos cross-node** — inventaire profil (relique / titre / épreuve).
5. **Split paiement** — plusieurs créateurs, ledger (Stripe Connect en prod).

## 2026-08-22 — Pilote Club 205 + 10 leviers

- Un seul club mis en avant : **Club 205** (3 salles, 20 fiches vides, 5 bounties). Le reste = bientôt.
- Import Facebook/Discord (texte ou JSON) → sujets Legacy.
- GSC (balise) + ping sitemap + robots.txt. 10 fiches avec descriptions longue traîne.
- File publique `/bounties` (guides manquants FR).
- Curseur : auto = Phase 1 / 1.9 / kit rallye ; jobs = étapes ATS si pas d’arcs.
- Citation @produit = achat 1 tap + 5 % KOC.
- Sac → `/cv/{id}` JSON-LD + candidature `/n/{slug}/apply`.
- DNS : `/n/{slug}/dns` (CNAME + aperçu `/w/`).
- HLS documenté (`Hls.php`) — Bunny/R2 plus tard, pas sur mutu.
- API `/api/v1/g/{slug}` version 1.0 + `rel` + headers Link.

## 2026-08-22 — Club 205 univers complet

Le pilote n’est plus 3 salles vides. Garage ciné, 14 salles, graphe 1.9→pièces, holo-forum, pièces geo Reims, guides, meets, Drive, quêtes SEO, pulse sur l’accueil.

## 2026-08-22 — Leader : salles uniques + wiki pages

- Titre/desc SEO **par salle** (plus le même title sur tout le club).
- JSON-LD métier (DiscussionForumPosting, OfferCatalog, TechArticle…).
- Hero 78vh **seulement** sur Univers. Les autres salles ont un en-tête propre.
- Guides = pages `/n/{club}/guide/{slug}` + sitemap.
- Comparateur 1.6 vs 1.9. `llms.txt`. Réponses forum seedées.

## 2026-08-22 — Complet

Routes radar + panier + Moat. 20 fiches rédigées. Stories, avis, live, auteurs vidéo, i18n 1.9, recherche guides, footer, explore `?q=`.

## 2026-08-22 — Holo-forum (split + pièces jointes natives)

Le dock dit **Forum** (plus « Parler »). Split héros / pane claire. Réponse = texte + vidéo Drive + PDF locké + relique panier. Composer Relique / Vidéo / Fichier. Page `/t/{id}` identique, JSON-LD DiscussionForumPosting.

## 2026-08-22 — Magazine par univers (moule chef-de-secteur)

`/n/{club}/blog` + `/blog/{slug}`. Résumé, sommaire, définition, longue traîne, FAQPage, auteur, cluster, vidéo. Les membres publient. Club 205 : 3 articles.

## 2026-08-22 — 50 templates d’univers + magazine éditorial

Catalogue `/create` : manga, Vera, jeux, pays, formation, annonces… Chaque id = schema + salles + curseur + CCK vide. Magazine : TOC sticky, OG, speakable, barre de lecture, drop cap, responsive.

## 2026-08-22 — Innovations salles + générateur JSON-LD + thumbs templates

Chaque salle : wow + schema.org. `/n/{slug}/schema.json`, `/studio/{slug}/jsonld`. Miniatures `/tpl/{id}.svg`.

## 2026-08-22 — Vera + Lumen, Club 205 retiré

Templates : clic = sélection + barre + scroll formulaire. Univers complets `/n/vera` (recrutement) et `/n/lumen` (galerie hologramme).











