-- Builder CCK visuel + salon RPG Vera + quêtes (pas un CV).
-- Les salles du salon sont une carte 2D légère (Gather/Topia), pas un moteur 3D.

create table if not exists quests (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  title   text not null,
  skill   text not null default '',
  prompt  text not null default '',
  option_a text not null default '',
  option_b text not null default '',
  body    text not null default '',
  sort_order int not null default 0
);

create table if not exists salon_rooms (
  id      text primary key,
  node_id text not null references nodes(id) on delete cascade,
  title   text not null,
  kind    text not null default 'stand',
  body    text not null default '',
  grid_x  int not null default 1,
  grid_y  int not null default 1
);

insert into quests (id, node_id, title, skill, prompt, option_a, option_b, body, sort_order) values
('q-or-1', 'orion', 'Le brief qui ment', 'Game Design',
 'Le live a crashé. Le product veut un patch cosmétique. Le GDD dit l''inverse. Que faites-vous ?',
 'Tenir le GDD, chiffrer le vrai patch, écrire le risque.',
 'Livrer le cosmétique, noter la dette, sauver le stand-up.',
 'Micro-épreuve : jugement, pas un CV.', 0),
('q-or-2', 'orion', 'Économie live', 'Économie',
 'Un skin à 40 € casse le funnel F2P. Marketing pousse. Vous ?',
 'Simuler 3 saisons, montrer le churn, proposer un battle-pass honnête.',
 'Ship le skin, compenser par un event XP.',
 'La maison recrute des gens qui savent dire non avec un graphe.', 1),
('q-or-3', 'orion', 'Le pitch jury', 'Narrative',
 '5 minutes, un slide, un chiffre. Que montrez-vous ?',
 'La boucle joueur + le risque économique + le live-ops J+30.',
 'La vision, le moodboard, l''équipe rêvée.',
 'Le jury Orion note la clarté, pas le charisme.', 2)
on conflict (id) do nothing;

insert into salon_rooms (id, node_id, title, kind, body, grid_x, grid_y) values
('sr-or-1', 'orion', 'Accueil', 'lobby', 'Badge, règles du salon, premier PNJ RH.', 2, 7),
('sr-or-2', 'orion', 'Stand Game Design', 'stand', 'Le Lead GD. Approchez : la visio s''ouvre.', 5, 3),
('sr-or-3', 'orion', 'Terminal Foo', 'terminal', 'Défi caché. Tapez hire si vous lisez le lore.', 11, 2),
('sr-or-4', 'orion', 'Salle Jury', 'puzzle', 'Trois quêtes, un verdict. Pas un formulaire.', 8, 6),
('sr-or-5', 'orion', 'Campus live-ops', 'stand', 'Onboarding spatial — Virbela, version légère.', 13, 8)
on conflict (id) do nothing;

insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values
('tab-or-salon', 'orion', 'salon', 'Salon', 'map', 1),
('tab-or-arbre', 'orion', 'arbre', 'Arbre', 'tree', 2)
on conflict (id) do nothing;

-- CCK types « job » supplémentaires pour le builder (déjà 0007). Rien à dupliquer.
