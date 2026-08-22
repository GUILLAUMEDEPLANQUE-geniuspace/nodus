-- NODUS 0.9 — couche plateforme.
-- Contrat : lectures publiques, écritures auth + ACL (owner / admin / mod).
-- Prod : remplacer checkout démo par Stripe webhook ; blobs par S3 + HLS.

-- Staff : lier un vrai user_id (le name reste l'affichage).
alter table node_staff add column if not exists user_id text not null default '';

-- Profil membre (Vikinger-grade, data-driven).
create table if not exists profiles (
  user_id      text primary key,
  display_name text not null default '',
  bio          text not null default '',
  cover_url    text not null default '',
  locale       text not null default 'fr'
);

create table if not exists profile_follows (
  user_id  text not null,
  node_id  text not null references nodes(id) on delete cascade,
  primary key (user_id, node_id)
);

-- Visites : perso / reco (pas un pixel pub).
create table if not exists node_visits (
  id         text primary key,
  user_id    text not null,
  node_id    text not null references nodes(id) on delete cascade,
  visited_at timestamptz not null default now()
);
create index if not exists node_visits_user_idx on node_visits (user_id, visited_at desc);

-- Panier + commandes. status: cart | paid | failed. grant vidéo au paid.
create table if not exists cart_items (
  id         text primary key,
  user_id    text not null,
  product_id text not null references shop_products(id) on delete cascade,
  qty        int not null default 1
);

create table if not exists orders (
  id          text primary key,
  user_id     text not null,
  total_label text not null,
  status      text not null default 'paid',
  provider    text not null default 'demo',
  created_at  timestamptz not null default now()
);

create table if not exists order_items (
  id         text primary key,
  order_id   text not null references orders(id) on delete cascade,
  product_id text not null,
  title      text not null,
  price      text not null
);

-- Blobs Drive. locked=true => jamais servi sans grant / staff.
create table if not exists drive_blobs (
  file_id   text primary key references drive_files(id) on delete cascade,
  mime      text not null,
  bytes_b64 text not null,
  locked    boolean not null default false
);

-- ATS : un candidat = un user dans un Node offre, step 1..7.
create table if not exists candidates (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  job_id     text not null,
  user_id    text not null,
  step       int not null default 1,
  status     text not null default 'en_cours',
  unique (job_id, user_id)
);

create table if not exists candidate_answers (
  id           text primary key,
  candidate_id text not null references candidates(id) on delete cascade,
  quest_id     text not null,
  choice       text not null,
  created_at   timestamptz not null default now()
);

-- Notifs + DM (Telegram-grade, scoped Node ou 1-1).
create table if not exists notifications (
  id         text primary key,
  user_id    text not null,
  kind       text not null,
  title      text not null,
  body       text not null,
  href       text not null default '/',
  read       boolean not null default false,
  created_at timestamptz not null default now()
);

create table if not exists dm_threads (
  id         text primary key,
  node_id    text,
  title      text not null
);

create table if not exists dm_members (
  thread_id text not null references dm_threads(id) on delete cascade,
  user_id   text not null,
  primary key (thread_id, user_id)
);

create table if not exists dm_messages (
  id         text primary key,
  thread_id  text not null references dm_threads(id) on delete cascade,
  author_id  text not null,
  body       text not null,
  created_at timestamptz not null default now()
);

-- Seed profils démo (auth off = dev-user).
insert into profiles (user_id, display_name, bio, cover_url, locale) values
('dev-user', 'Dev User', 'Architecte de Nodes. Guilde One Piece + Maison Orion.', '/realms/actor-hero.jpg', 'fr')
on conflict (user_id) do nothing;

insert into profile_follows (user_id, node_id) values
('dev-user', 'onepiece'),
('dev-user', 'orion')
on conflict do nothing;

insert into notifications (id, user_id, kind, title, body, href) values
('nt-1', 'dev-user', 'forum', 'Zoro a répondu dans Marineford', 'Legacy SEO mis à jour.', '/n/one-piece/t/th-op-1'),
('nt-2', 'dev-user', 'shop', 'Labradorite : 1 pièce restante', 'Stock unique.', '/n/atelier-du-nord/p/sp-at-1'),
('nt-3', 'dev-user', 'ats', 'Candidature Orion · étape 2', 'Le dossier s''ouvre après la quête Culture fit.', '/n/maison-orion')
on conflict (id) do nothing;

insert into candidates (id, node_id, job_id, user_id, step, status) values
('cand-1', 'orion', 'job-gd', 'dev-user', 2, 'en_cours')
on conflict (id) do nothing;

insert into dm_threads (id, node_id, title) values
('dm-op', 'onepiece', 'Quartier maître · One Piece')
on conflict (id) do nothing;

insert into dm_members (thread_id, user_id) values
('dm-op', 'dev-user')
on conflict do nothing;

insert into dm_messages (id, thread_id, author_id, body) values
('dmm-1', 'dm-op', 'Nami', 'Le Drive Lore a une nouvelle carte. Relisez avant Egghead.')
on conflict (id) do nothing;

-- Owner des univers seed = admin (user_id vide tant que pas d'auth réelle).
insert into node_staff (id, node_id, name, role, user_id) values
('st-op-dev', 'onepiece', 'Dev User', 'admin', 'dev-user'),
('st-or-dev', 'orion', 'Dev User', 'admin', 'dev-user')
on conflict (id) do nothing;
