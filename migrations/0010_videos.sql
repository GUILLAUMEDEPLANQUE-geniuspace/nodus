-- Fiches vidéo JoomCCK-grade + dédoublonnage d'onglets (guilde / guildes).
-- Videos du Node ET des enfants (Luffy, etc.) apparaissent dans l'onglet Vidéos.

alter table node_media add column if not exists season text not null default '';
alter table node_media add column if not exists episode text not null default '';
alter table node_media add column if not exists language text not null default '';
alter table node_media add column if not exists difficulty text not null default '';
alter table node_media add column if not exists ribbon text not null default '';

update node_media set
  language = 'VOSTFR',
  difficulty = 'Intermédiaire',
  ribbon = 'Analyse de Fond',
  season = 'Marineford'
 where title like '%Marineford%' or title like '%Jack%';

update node_media set language = 'VOSTFR', difficulty = 'Facile d''accès', ribbon = 'Lore', season = 'East Blue'
 where node_id = 'luffy';

update node_media set language = 'VF', difficulty = 'Intermédiaire', ribbon = 'Portrait'
 where node_id in ('rda', 'macgyver');

-- Fiche universe One Piece (sinon l'onglet Vidéos du monde était vide : les rushes sont sur les persos)
insert into node_media (node_id, kind, title, url, duration, genre, chapters, transcript, sort_order, season, episode, language, difficulty, ribbon)
select 'onepiece', 'video', 'Analyse · Marineford', '', '18:40', 'Analyse',
 E'00:00 — La place\n03:12 — Ace\n08:20 — L''équipage brisé\n14:00 — Ce que Gear 5 doit au deuil',
 'Marineford n''est pas un arc filler. Cette fiche relie le wiki, le forum Legacy et les Reliques. Chapitres cliquables, transcript indexé.',
 0, 'Marineford', '—', 'VOSTFR', 'Intermédiaire', 'Analyse de Fond'
where not exists (select 1 from node_media where node_id = 'onepiece' and kind = 'video');

insert into node_media (node_id, kind, title, url, duration, genre, chapters, transcript, sort_order, season, language, difficulty, ribbon)
select 'onepiece', 'video', 'Luffy · éveil Nika', '', '09:12', 'Lore',
 E'00:00 — Le tambour\n02:40 — Joy Boy\n06:00 — Ce que le Node en fait',
 'Fiche liée au nœud Luffy. Le graphe parent/enfant porte le SEO, pas un tag YouTube.',
 1, 'Egghead', 'VOSTFR', 'Un peu compliqué', 'Décryptage'
where not exists (select 1 from node_media where node_id = 'onepiece' and title like '%Nika%');

-- Onglet Vidéos dédié. Studio (engrenage) reste la config. Drive = reliques.
update node_tabs set label = 'Drive', icon = 'folder' where id = 'tab-op-media';
update node_tabs set label = 'Drive', icon = 'folder' where id = 'tab-sg-media';

insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values
('tab-op-videos', 'onepiece', 'videos', 'Vidéos', 'film', 6),
('tab-sg-videos', 'sg1', 'videos', 'Vidéos', 'film', 4)
on conflict (id) do nothing;

-- Dédoublonner guilde / guildes (même stem)
delete from node_tabs a
 using node_tabs b
 where a.node_id = b.node_id
   and a.id > b.id
   and regexp_replace(lower(a.tab_key), 's$', '') = regexp_replace(lower(b.tab_key), 's$', '');
