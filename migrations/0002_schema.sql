-- NODUS knowledge graph: nodes, directed edges, media, tags
create table if not exists nodes (
  id          text primary key,
  slug        text not null unique,
  kind        text not null,
  title       text not null,
  subtitle    text not null default '',
  summary     text not null default '',
  body        text not null default '',
  year_start  int,
  year_end    int,
  featured    boolean not null default false,
  owner_id    text,
  created_at  timestamptz not null default now(),
  updated_at  timestamptz not null default now()
);

create index if not exists nodes_kind_idx on nodes (kind);
create index if not exists nodes_featured_idx on nodes (featured);
create index if not exists nodes_owner_idx on nodes (owner_id);

create table if not exists edges (
  id        serial primary key,
  from_id   text not null references nodes(id) on delete cascade,
  to_id     text not null references nodes(id) on delete cascade,
  kind      text not null,
  label     text not null default '',
  note      text not null default '',
  unique (from_id, to_id, kind)
);

create index if not exists edges_from_idx on edges (from_id);
create index if not exists edges_to_idx on edges (to_id);
create index if not exists edges_kind_idx on edges (kind);

create table if not exists node_media (
  id          serial primary key,
  node_id     text not null references nodes(id) on delete cascade,
  kind        text not null,
  title       text not null default '',
  url         text not null default '',
  duration    text not null default '',
  genre       text not null default '',
  chapters    text not null default '',
  transcript  text not null default '',
  sort_order  int not null default 0
);

create index if not exists node_media_node_idx on node_media (node_id);

create table if not exists node_tags (
  node_id text not null references nodes(id) on delete cascade,
  tag     text not null,
  primary key (node_id, tag)
);
