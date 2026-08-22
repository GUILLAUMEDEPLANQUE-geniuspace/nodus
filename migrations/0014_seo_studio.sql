-- Overrides SEO rédigés par owner/admin.
-- La PAGE publique reste indexable (Google). Seul l'ÉDITEUR est privé.
-- noindex = exception (brouillon). Ne jamais noindex un univers publié.

create table if not exists node_seo (
  node_id     text primary key references nodes(id) on delete cascade,
  title       text not null default '',
  description text not null default '',
  keywords    text not null default '',
  noindex     boolean not null default false
);
