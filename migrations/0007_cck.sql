-- CCK (Custom Content Kit) — parité JoomCCK / Vera.
-- Un champ n'est pas collé à un type SQL : il décrit blog, boutique, job, perso, vidéo.
-- field_type : text | html | choice | media | relation | scale
-- target_kind : node | thread | product | media

alter table cck_fields add column if not exists field_type text not null default 'text';
alter table cck_fields add column if not exists target_kind text not null default 'node';
alter table cck_fields add column if not exists target_id text not null default '';

-- Boutique One Piece
insert into cck_fields (id, node_id, field_key, label, value, sort_order, field_type, target_kind, target_id) values
('cck-sp1-sku', 'onepiece', 'sku', 'SKU', 'OP-MAP-003', 0, 'text', 'product', 'sp-op-1'),
('cck-sp1-stock', 'onepiece', 'stock', 'Stock', '40', 1, 'scale', 'product', 'sp-op-1'),
('cck-sp1-media', 'onepiece', 'relique', 'Relique Drive', 'Carte du monde · Grand Line', 2, 'media', 'product', 'sp-op-1'),
('cck-sp2-sku', 'onepiece', 'sku', 'SKU', 'OP-HAT-001', 0, 'text', 'product', 'sp-op-2'),
('cck-sp2-mat', 'onepiece', 'matiere', 'Matière', 'Paille tressée', 1, 'choice', 'product', 'sp-op-2'),
-- Journal / blog
('cck-bl-time', 'onepiece', 'lecture', 'Temps de lecture', '6 min', 0, 'text', 'thread', 'th-op-4'),
('cck-bl-src', 'onepiece', 'sources', 'Sources', 'Drive · Lore + fiche Gear 5', 1, 'relation', 'thread', 'th-op-4'),
('cck-bl-tags', 'onepiece', 'tags', 'Tags', 'lore, gear5, nika', 2, 'choice', 'thread', 'th-op-4'),
('cck-bl2-time', 'onepiece', 'lecture', 'Temps de lecture', '4 min', 0, 'text', 'thread', 'th-op-5'),
-- Fiches vidéo / studio
('cck-vid-lang', 'onepiece', 'langue', 'Langue', 'FR', 0, 'choice', 'media', 'df-op-marineford'),
('cck-vid-saison', 'onepiece', 'saison', 'Arc', 'Marineford', 1, 'text', 'media', 'df-op-marineford'),
('cck-vid-seo', 'onepiece', 'schema', 'Schema', 'VideoObject + Clip + transcript', 2, 'text', 'media', 'df-op-marineford'),
-- Job (complète le CCK Vera)
('cck-job-6', 'job-gd', 'seniority', 'Séniorité', 'Senior', 5, 'choice', 'node', 'job-gd'),
('cck-job-7', 'job-gd', 'visa', 'Visa', 'Non sponsorisé', 6, 'choice', 'node', 'job-gd')
on conflict (id) do nothing;
