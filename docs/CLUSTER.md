# Cluster d’entités

1 intention = 1 URL. Kakashi est une intention. Une salle boutique vide n’en est pas une.

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
