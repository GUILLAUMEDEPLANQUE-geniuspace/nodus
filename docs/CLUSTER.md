# Cluster d’entités

1 intention = 1 URL. Kakashi est une intention. Un bien à Reims aussi. Une salle boutique vide n’en est pas une.

## Catalogue

`ElementCatalog` n’est pas un hub manga. 24 kinds, 11 familles (Fandom, Jeux, Collection, Emploi, Formation, Commerce, Pays, Business, Création, Finance, Flagship).

Le pack pose des **kinds**. L’URL naît à la publication + `EntityFloor`.

| Famille | Germes | Kinds typiques |
| --- | --- | --- |
| Fandom / Atelier | manga-hub, atelier-anime | personnage, organisation, lieu, jutsu, arc |
| Emploi / Maison | vera-tech, maison-rh | offre, personnage, competence |
| Formation / Labo | bts-com, chef-secteur, labo | cours, competence, playbook |
| Commerce / Coffre | esoterique, vault | relique, rituel, produit |
| Business | b2b-sales, cabinet, retail-gms | compte, playbook, livrable |
| Finance | crypto-onchain, immo-agence, banque-fintech, cabinet-droit | actif, protocole, bien, mandat, dossier, acte, jurisprudence |
| Collection | club-auto, montres | vehicule, produit |
| Pays / Territoire | japon, territoire | lieu, evenement |

Partage = `components`. Le lore d’un monde ne voyage pas.

## Naissance d’une URL

- Pack / germe → capacités (`node_tabs`) + kinds (`element_kinds`). Zéro fiche.
- Ingest → `EntityProposer` drafts, `applied = false`.
- Publication humaine + `EntityFloor` → indexable.
- `ClusterGraph` pose hub ↔ spoke ↔ sœurs depuis les arêtes Engine.

## Plancher

- Copie ≥ 80 caractères (body ou summary).
- Champ `statut` si présent : `published`.
- Jaccard avec une sœur du même kind < 0.72.
- Salle : présente mais `noindex` tant qu’elle n’a pas de contenu.

## SEO

`/n/{slug}/sitemap.xml` doit passer par `SeoCompiler::sitemapXml`.
`/n/{slug}/llms.txt` doit passer par `SeoCompiler::llmsTxt`.
`/n/{slug}/schema.json` fusionne `SeoCompiler::worldGraph` + RoomSchema.
`/n/{slug}/f/{fiche}/schema.json` = `SeoCompiler::entityGraph`.

GSC / netlinking : observations Grantor, pas d’écriture autonome.
