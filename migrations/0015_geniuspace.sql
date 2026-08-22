-- Geniuspace 1.0 — RWA, reco, ATS 7 étapes, modération, i18n fiches, SEM, reliques 3D.

alter table shop_products add column if not exists rwa boolean not null default false;
alter table shop_products add column if not exists energy int not null default 20;
alter table shop_products add column if not exists image_url text not null default '';
update shop_products set rwa = true, energy = 100, image_url = '/realms/actor-hero.jpg',
       kind = 'rwa', price = '5 000 €', title = 'Concept Art · L''Aube',
       summary = 'Œuvre physique unique certifiée RWA. Making-of dans le Drive.'
 where id = 'sp-at-1';
update shop_products set image_url = '/realms/sea-hero.jpg', energy = 30 where id = 'sp-op-1';
update shop_products set image_url = '/realms/luffy.jpg', energy = 25 where id = 'sp-op-2';
update shop_products set image_url = '/realms/nami.jpg', energy = 15 where id = 'sp-op-3';

create table if not exists crowd_goals (
  node_id text primary key references nodes(id) on delete cascade,
  target  int not null default 10000,
  current int not null default 0,
  reward  text not null default ''
);
insert into crowd_goals (node_id, target, current, reward) values
('atelier', 10000, 6500, 'Croquis secret pour tous les acheteurs'),
('onepiece', 8000, 2100, 'Carte annotée Grand Line pour la guilde')
on conflict do nothing;

create table if not exists ad_slots (
  id        text primary key,
  node_id   text not null references nodes(id) on delete cascade,
  title     text not null,
  href      text not null,
  image_url text not null default '',
  start_sec int not null default 8,
  end_sec   int not null default 18
);
insert into ad_slots (id, node_id, title, href, image_url, start_sec, end_sec) values
('ad-at-1', 'atelier', 'Œuvre unique · RWA', '/n/atelier-nocturne/p/sp-at-1', '/realms/actor-hero.jpg', 6, 16)
on conflict do nothing;

create table if not exists ats_steps (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  step    int not null,
  title   text not null,
  body    text not null default '',
  unique (node_id, step)
);
insert into ats_steps (id, node_id, step, title, body) values
('as-1', 'orion', 1, 'Culture fit', 'Situation lore. Deux choix.'),
('as-2', 'orion', 2, 'Dossier Drive', 'PDF + vidéo entretien.'),
('as-3', 'orion', 3, 'Cas pratique', 'Épreuve chrono.'),
('as-4', 'orion', 4, 'Salon spatial', 'Approchez un recruteur.'),
('as-5', 'orion', 5, 'Skill tree', 'Débloquez 3 nœuds.'),
('as-6', 'orion', 6, 'Jury', 'Lecture des réponses.'),
('as-7', 'orion', 7, 'Offre', 'Signature dans le Node.')
on conflict do nothing;

create table if not exists forum_bans (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  handle  text not null,
  reason  text not null default ''
);

create table if not exists forum_queue (
  id        text primary key,
  node_id   text not null references nodes(id) on delete cascade,
  thread_id text not null,
  author    text not null,
  body      text not null,
  status    text not null default 'pending'
);

create table if not exists node_i18n (
  node_id  text not null references nodes(id) on delete cascade,
  locale   text not null,
  title    text not null,
  summary  text not null default '',
  body     text not null default '',
  primary key (node_id, locale)
);
insert into node_i18n (node_id, locale, title, summary, body) values
('onepiece', 'en', 'One Piece', 'A living Grand Line: wiki, guild, relics, shoppable lore.', 'The Straw Hats universe, not a wiki dump.'),
('onepiece', 'ja', 'ワンピース', '生きているグランドライン。wiki・ギルド・遺物。', 'ファンが世界を建て、ギルドが住む。'),
('atelier', 'en', 'Northern Atelier', 'Unique RWA piece. Making-of in the Drive.', 'Certified unique work.'),
('orion', 'en', 'House Orion', 'Experiential hiring. Quests, not CVs.', 'Seven-step path.')
on conflict do nothing;

create table if not exists node_relics (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  title   text not null,
  x       real not null default 0,
  y       real not null default 0
);
insert into node_relics (id, node_id, title, x, y) values
('rl-1', 'onepiece', 'Sunny', -4, 2),
('rl-2', 'onepiece', 'Log Pose', 3, -1)
on conflict do nothing;

update node_media set url = '/media/teaser.mp4' where node_id in ('luffy','nami','zoro','sanji','chopper') and (url is null or url = '');
update node_media set url = '/media/orion.mp4' where node_id in ('orion','job-gd') and (url is null or url = '');
update node_media set url = '/media/atelier.mp4' where node_id = 'labradorite';
