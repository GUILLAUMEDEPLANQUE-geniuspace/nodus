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

