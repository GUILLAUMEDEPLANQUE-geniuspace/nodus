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



