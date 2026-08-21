-- Habitat d'univers : onglets configurables, staff, forum, boutique, playlists.
-- Les seed One Piece / Orion / SG-1 deviennent des lieux de vie complets.

create table if not exists node_theme (
  node_id  text primary key references nodes(id) on delete cascade,
  hero_url text not null default ''
);

create table if not exists node_tabs (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  tab_key    text not null,
  label      text not null,
  icon       text not null default 'sparkles',
  sort_order int not null default 0,
  enabled    boolean not null default true
);
create index if not exists node_tabs_node_idx on node_tabs (node_id);

create table if not exists node_staff (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  name    text not null,
  role    text not null
);

create table if not exists forum_categories (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  title   text not null,
  body    text not null default ''
);

create table if not exists forum_replies (
  id        text primary key,
  thread_id text not null references threads(id) on delete cascade,
  author    text not null,
  body      text not null
);

create table if not exists shop_products (
  id       text primary key,
  node_id  text not null references nodes(id) on delete cascade,
  title    text not null,
  price    text not null,
  summary  text not null default '',
  kind     text not null default 'objet'
);

create table if not exists playlists (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  title   text not null,
  author  text not null
);

create table if not exists playlist_items (
  id          text primary key,
  playlist_id text not null references playlists(id) on delete cascade,
  title       text not null,
  kind        text not null,
  duration    text not null default ''
);

-- Onglets One Piece (lieu de vie manga)
insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values
('tab-op-vivre', 'onepiece', 'vivre', 'Univers', 'compass', 0),
('tab-op-cast', 'onepiece', 'personnages', 'Personnages', 'users', 1),
('tab-op-forum', 'onepiece', 'forum', 'Forum', 'messages', 2),
('tab-op-journal', 'onepiece', 'journal', 'Journal', 'newspaper', 3),
('tab-op-guilde', 'onepiece', 'guilde', 'Guilde', 'radio', 4),
('tab-op-guides', 'onepiece', 'guides', 'Guides', 'book', 5),
('tab-op-shop', 'onepiece', 'boutique', 'Boutique', 'store', 6),
('tab-op-media', 'onepiece', 'reliques', 'Studio', 'film', 7)
on conflict (id) do nothing;

insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values
('tab-sg-vivre', 'sg1', 'vivre', 'Univers', 'compass', 0),
('tab-sg-cast', 'sg1', 'personnages', 'Équipe', 'users', 1),
('tab-sg-forum', 'sg1', 'forum', 'Forum', 'messages', 2),
('tab-sg-guilde', 'sg1', 'guilde', 'Radio', 'radio', 3),
('tab-sg-media', 'sg1', 'reliques', 'Studio', 'film', 4)
on conflict (id) do nothing;

insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values
('tab-or-maison', 'orion', 'maison', 'Maison', 'building', 0),
('tab-or-offres', 'orion', 'offres', 'Offres', 'briefcase', 1),
('tab-or-epreuve', 'orion', 'epreuve', 'Épreuve', 'list', 2),
('tab-or-drive', 'orion', 'drive', 'Drive', 'folder', 3),
('tab-or-acad', 'orion', 'academie', 'Académie', 'book', 4),
('tab-or-forum', 'orion', 'forum', 'Forum', 'messages', 5)
on conflict (id) do nothing;

insert into node_staff (id, node_id, name, role) values
('st-op-1', 'onepiece', 'Gardien du lore', 'admin'),
('st-op-2', 'onepiece', 'Nami', 'mod'),
('st-op-3', 'onepiece', 'Usopp', 'mod'),
('st-sg-1', 'sg1', 'Carter', 'admin'),
('st-or-1', 'orion', 'Lead RH', 'admin'),
('st-or-2', 'orion', 'Lead GD', 'mod')
on conflict (id) do nothing;

insert into forum_categories (id, node_id, title, body) values
('fc-op-th', 'onepiece', 'Théories', 'Egghead, siècle oublié, fruits — preuves dans le graphe.'),
('fc-op-eq', 'onepiece', 'Équipage', 'Qui embarque, qui reste à terre.'),
('fc-op-sp', 'onepiece', 'Spoilers', 'Canal modéré. Marquez vos arcs.'),
('fc-sg-1', 'sg1', 'Missions', 'Rapports d''après-porte.'),
('fc-or-1', 'orion', 'Candidats', 'Questions sur l''épreuve, pas de spoilers de brief.')
on conflict (id) do nothing;

insert into forum_replies (id, thread_id, author, body) values
('fr-1', 'th-op-1', 'Zoro', 'Trop tôt ? On était prêts. Ace ne l''était pas. Relisez Marineford dans les Reliques.'),
('fr-2', 'th-op-1', 'Robin', 'Le deuil est un nœud. Sans lui, Gear 5 n''a pas de poids.'),
('fr-3', 'th-op-2', 'Chopper', 'J''ai classé les preuves Vegapunk dans le Drive · Lore.')
on conflict (id) do nothing;

insert into shop_products (id, node_id, title, price, summary, kind) values
('sp-op-1', 'onepiece', 'Carte annotée Grand Line', '24 €', 'Tirage limité · relie le wiki et le Drive.', 'print'),
('sp-op-2', 'onepiece', 'Chapeau de paille · réplique', '39 €', 'Merch de guilde, pas un drop générique.', 'objet'),
('sp-op-3', 'onepiece', 'OSTs · playlist partagée', 'gratuit', 'Ajoutez-la à votre profil.', 'audio'),
('sp-at-1', 'atelier', 'Labradorite polaire', '180 €', 'Pièce unique, making-of dans le Drive.', 'objet')
on conflict (id) do nothing;

insert into playlists (id, node_id, title, author) values
('pl-op-1', 'onepiece', 'Arcs à relire avant Egghead', 'Nami'),
('pl-op-2', 'onepiece', 'OSTs de mer', 'Brook'),
('pl-sg-1', 'sg1', 'Briefings SG-1', 'Carter')
on conflict (id) do nothing;

insert into playlist_items (id, playlist_id, title, kind, duration) values
('pi-1', 'pl-op-1', 'Analyse · Marineford', 'video', '18:40'),
('pi-2', 'pl-op-1', 'Luffy · éveil Nika', 'video', '09:12'),
('pi-3', 'pl-op-2', 'Binks no Sake', 'audio', '03:12'),
('pi-4', 'pl-sg-1', 'Portrait Jack O''Neill', 'video', '12:40')
on conflict (id) do nothing;
