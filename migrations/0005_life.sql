create table if not exists threads (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  kind       text not null default 'forum',
  title      text not null,
  author     text not null,
  body       text not null default '',
  replies    int not null default 0
);
create index if not exists threads_node_idx on threads (node_id);

create table if not exists guild_messages (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  author  text not null,
  body    text not null
);
create index if not exists guild_messages_node_idx on guild_messages (node_id);

create table if not exists cck_fields (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  field_key  text not null,
  label      text not null,
  value      text not null default '',
  sort_order int not null default 0
);
create index if not exists cck_fields_node_idx on cck_fields (node_id);

insert into threads (id, node_id, kind, title, author, body, replies) values
('th-op-1', 'onepiece', 'forum', 'Marineford était-il trop tôt ?', 'Nami',
 'On en discute depuis des années dans la guilde. L''arc est un deuil collectif — c''est pour ça que le Node le porte encore.', 128),
('th-op-2', 'onepiece', 'forum', 'Théorie Egghead · le siècle oublié', 'Robin',
 'Si Vegapunk dit vrai, le lore du Node doit relier les fruits, la Marie Joie et le journal de Joy Boy. Reliez vos preuves.', 86),
('th-op-3', 'onepiece', 'forum', 'Qui embarque ensuite ?', 'Usopp',
 'La guilde vote. Pas un sondage plat : chaque candidat devient un nœud-enfant si l''équipage l''adopte.', 54),
('th-op-4', 'onepiece', 'blog', 'Pourquoi Gear 5 n''est pas un power-up', 'Oda-fan',
 'C''est un retournement de lore. Le fruit n''était pas Gom Gom. Le wiki du Node a dû être réécrit — et c''est le propre d''un univers vivant.', 41),
('th-op-5', 'onepiece', 'blog', 'Carte annotée de la Grand Line', 'Cartographe',
 'J''ai déposé la v3 dans les Reliques. Posez vos îles, on les relie au graphe.', 19),
('th-sg-1', 'sg1', 'forum', 'Jack a-t-il vraiment lu Les Simpson à Abydos ?', 'Teal''c',
 'En effet. La guilde SG-1 collectionne les répliques. Venez les déposer en reliques.', 67),
('th-sg-2', 'sg1', 'blog', 'La porte comme graphe', 'Daniel',
 'Chaque adresse est un nœud. Chaque monde un enfant. On ne archive pas SG-1 : on l''habite.', 22),
('th-or-1', 'orion', 'forum', 'Questions candidats · Lead GD', 'Maison Orion',
 'Posez vos questions sur l''épreuve 7 étapes. Le Drive a les briefs. On répond ici, pas par mail perdu.', 14),
('th-or-2', 'orion', 'blog', 'Pourquoi on recrute comme on conçoit un jeu', 'Lead RH',
 'Une offre n''est pas une annonce. C''est un niveau : GDD, économie, live-ops, pitch. Vera dans le Node.', 8)
on conflict (id) do nothing;

insert into guild_messages (id, node_id, author, body) values
('gm-op-1', 'onepiece', 'Luffy', 'On mange d''abord. Le lore après.'),
('gm-op-2', 'onepiece', 'Zoro', 'J''étais pas perdu. C''est l''île qui a bougé.'),
('gm-op-3', 'onepiece', 'Nami', 'Babord, et rangez les reliques avant la tempête.'),
('gm-op-4', 'onepiece', 'Sanji', 'Le forum a encore brûlé le ragoût. Je poste la recette dans le Drive.'),
('gm-op-5', 'onepiece', 'Chopper', '12 fans en ligne. Infirmerie ouverte.'),
('gm-sg-1', 'sg1', 'O''Neill', 'Carter, si ça brille, on ne touche pas. Sauf Daniel.'),
('gm-sg-2', 'sg1', 'Carter', 'Fenêtre de vortex dans 4 minutes. Reliques prêtes.'),
('gm-sg-3', 'sg1', 'Teal''c', 'En effet.'),
('gm-or-1', 'orion', 'RH', '3 candidats ont rendu l''étape 4. Drive mis à jour.'),
('gm-or-2', 'orion', 'Lead GD', 'On garde le pitch spatial. On coupe le lore trop générique.')
on conflict (id) do nothing;

insert into cck_fields (id, node_id, field_key, label, value, sort_order) values
('cck-job-1', 'job-gd', 'salary', 'Rémunération', '65–80 k€ + interesting', 0),
('cck-job-2', 'job-gd', 'remote', 'Remote', 'Hybride · Paris / spatial async', 1),
('cck-job-3', 'job-gd', 'stack', 'Stack', 'GDD, économie live, Unreal, Figma', 2),
('cck-job-4', 'job-gd', 'epreuve', 'Épreuve', '7 étapes · brief → GDD → économie → live-ops → pitch → jury → offre', 3),
('cck-job-5', 'job-gd', 'contrat', 'Contrat', 'CDI · senior', 4),
('cck-luffy-1', 'luffy', 'fruit', 'Fruit', 'Hito Hito no Mi, modèle Nika', 0),
('cck-luffy-2', 'luffy', 'prime', 'Prime', '3 000 000 000', 1),
('cck-luffy-3', 'luffy', 'role', 'Rôle', 'Capitaine', 2),
('cck-zoro-1', 'zoro', 'role', 'Rôle', 'Combatant', 0),
('cck-zoro-2', 'zoro', 'armes', 'Armes', 'Trois sabres · Wado Ichimonji', 1),
('cck-nami-1', 'nami', 'role', 'Rôle', 'Navigatrice', 0),
('cck-nami-2', 'nami', 'climat', 'Climat', 'Bâton climatique', 1)
on conflict (id) do nothing;
