-- Moteur vidéo sécurisé : mode (formation / jeu / boutique / entretien),
-- teaser paywall, Drive séquencé, fichiers jamais exposés si locked.

alter table node_media add column if not exists mode text not null default 'lore';
alter table node_media add column if not exists access_kind text not null default 'free';
alter table node_media add column if not exists teaser_sec int not null default 0;
alter table node_media add column if not exists price text not null default '';
alter table node_media add column if not exists views int not null default 0;
alter table node_media add column if not exists rating text not null default '';

update node_media set mode = 'lore', access_kind = 'free', views = 4100, rating = '4.6'
 where title like '%volonté%';
update node_media set mode = 'formation', access_kind = 'freemium', teaser_sec = 8, price = '15 €',
       views = 14052, rating = '4.8', difficulty = 'Pour les experts', ribbon = 'Masterclass'
 where title like '%Nika%';
update node_media set mode = 'lore', access_kind = 'free', views = 8900, rating = '4.9'
 where title like '%Marineford%';
update node_media set mode = 'interview', access_kind = 'freemium', teaser_sec = 12, price = 'Épreuve',
       views = 640, rating = '—'
 where node_id = 'job-gd' or title like '%épreuve%';

insert into node_media (node_id, kind, title, url, duration, genre, chapters, transcript, sort_order,
                        season, language, difficulty, ribbon, mode, access_kind, teaser_sec, price, views, rating)
select 'orion', 'video', 'Épreuve · Culture fit (teaser)', '', '07:20', 'Recrutement',
 E'00:00 — La maison\n01:40 — Ce qu''on refuse\n04:00 — Cas (premium)\n06:00 — Debrief',
 'Teaser public. La suite et le dossier PDF sont verrouillés jusqu''à l''étape 2.',
 0, 'ATS', 'FR', 'Intermédiaire', 'Épreuve', 'interview', 'freemium', 12, 'Épreuve', 640, '—'
where not exists (select 1 from node_media where node_id = 'orion' and kind = 'video');

create table if not exists video_assets (
  id          text primary key,
  media_id    int not null references node_media(id) on delete cascade,
  chapter_sec int not null default 0,
  name        text not null,
  kind        text not null,
  locked      boolean not null default true,
  url         text not null default ''
);

create table if not exists video_grants (
  user_id  text not null,
  media_id int not null references node_media(id) on delete cascade,
  granted_at timestamptz not null default now(),
  primary key (user_id, media_id)
);

create table if not exists video_news (
  id       text primary key,
  media_id int not null references node_media(id) on delete cascade,
  kind     text not null,
  body     text not null
);

insert into video_assets (id, media_id, chapter_sec, name, kind, locked, url)
select 'va-free-pdf', id, 0, 'intro_gratuite.pdf', 'pdf', false, '/drive/intro_gratuite.pdf'
from node_media where title like '%Nika%' limit 1
on conflict (id) do nothing;

insert into video_assets (id, media_id, chapter_sec, name, kind, locked, url)
select 'va-nika-zip', id, 30, 'code_source.zip', 'zip', true, '/secured/code_source.zip'
from node_media where title like '%Nika%' limit 1
on conflict (id) do nothing;

insert into video_assets (id, media_id, chapter_sec, name, kind, locked, url)
select 'va-nika-obj', id, 30, 'nika_rig.obj', 'obj', true, '/secured/nika_rig.obj'
from node_media where title like '%Nika%' limit 1
on conflict (id) do nothing;

insert into video_assets (id, media_id, chapter_sec, name, kind, locked, url)
select 'va-or-pdf', id, 0, 'culture_fit.pdf', 'pdf', false, '/drive/culture_fit.pdf'
from node_media where node_id = 'orion' and kind = 'video' limit 1
on conflict (id) do nothing;

insert into video_assets (id, media_id, chapter_sec, name, kind, locked, url)
select 'va-or-dossier', id, 240, 'dossier_candidat.pdf', 'pdf', true, '/secured/dossier_candidat.pdf'
from node_media where node_id = 'orion' and kind = 'video' limit 1
on conflict (id) do nothing;

insert into video_news (id, media_id, kind, body)
select 'vn-1', id, 'promo', 'Masterclass Nika : extrait 8s, sources verrouillées jusqu''à l''achat.'
from node_media where title like '%Nika%' limit 1
on conflict (id) do nothing;

insert into video_news (id, media_id, kind, body)
select 'vn-2', id, 'news', 'Patch-note Drive : le rig 3D a été mis à jour.'
from node_media where title like '%Nika%' limit 1
on conflict (id) do nothing;

insert into video_news (id, media_id, kind, body)
select 'vn-or', id, 'news', 'Maison Orion : le teaser Culture fit est public. Le dossier reste derrière l''épreuve.'
from node_media where node_id = 'orion' and kind = 'video' limit 1
on conflict (id) do nothing;

insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values
('tab-or-videos', 'orion', 'videos', 'Vidéos', 'film', 5)
on conflict (id) do nothing;
