-- Holo-Forum : cartes visuelles, live Telegram, legacy SEO.
-- cover / vues / feux sur threads ; messages live séparés du Legacy (indexable).

alter table threads add column if not exists cover text not null default '';
alter table threads add column if not exists views int not null default 0;
alter table threads add column if not exists fires int not null default 0;

create table if not exists forum_live (
  id        text primary key,
  thread_id text not null references threads(id) on delete cascade,
  author    text not null,
  body      text not null,
  kind      text not null default 'text'
);

update threads set cover = '/realms/sea-hero.jpg', views = 14200, fires = 1200
 where id = 'th-op-1' and cover = '';
update threads set cover = '/realms/portal-hero.jpg', views = 8900, fires = 640
 where id = 'th-op-2' and cover = '';
update threads set cover = '/realms/luffy.jpg', views = 4100, fires = 210
 where id = 'th-op-3' and cover = '';

insert into forum_live (id, thread_id, author, body, kind) values
('fl-1', 'th-op-1', 'Nami', 'C''est fou ce chapitre, j''en reviens pas.', 'text'),
('fl-2', 'th-op-1', 'Brook', 'Ace. Relisez les Reliques. Yohoho.', 'text'),
('fl-3', 'th-op-2', 'Usopp', 'Preuves dans le Drive · Lore.', 'text')
on conflict (id) do nothing;
