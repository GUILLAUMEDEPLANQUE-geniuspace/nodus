create table if not exists drive_folders (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  parent_id  text,
  name       text not null,
  sort_order int not null default 0
);
create index if not exists drive_folders_node_idx on drive_folders (node_id);

create table if not exists drive_files (
  id          text primary key,
  node_id     text not null references nodes(id) on delete cascade,
  folder_id   text,
  name        text not null,
  kind        text not null,
  size_label  text not null default '',
  version     int not null default 1,
  summary     text not null default '',
  chapters    text not null default '',
  transcript  text not null default '',
  sort_order  int not null default 0
);
create index if not exists drive_files_node_idx on drive_files (node_id);
create index if not exists drive_files_folder_idx on drive_files (folder_id);

create table if not exists wiki_pages (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  title      text not null,
  body       text not null default '',
  sort_order int not null default 0
);
create index if not exists wiki_pages_node_idx on wiki_pages (node_id);

create table if not exists timeline_events (
  id         text primary key,
  node_id    text not null references nodes(id) on delete cascade,
  year_label text not null,
  title      text not null,
  body       text not null default '',
  sort_order int not null default 0
);
create index if not exists timeline_events_node_idx on timeline_events (node_id);

-- Extra enfants manga
insert into nodes (id, slug, kind, title, subtitle, summary, body, year_start, featured)
values
('sanji', 'sanji', 'character', 'Sanji', 'Cuisinier · jambe noire',
 'Cuisinier des Mugiwara. Enfant de l''équipage et de One Piece.',
 'Sanji relie cuisine, combat et allégeance. Dans le graphe, il est un nœud-enfant comme Zoro et Nami.',
 1997, false),
('chopper', 'tony-tony-chopper', 'character', 'Tony Tony Chopper', 'Médecin · rennes',
 'Médecin de bord. Enfant de l''équipage.',
 'Chopper prouve qu''un wiki manga n''est pas une liste : chaque membre a une fiche, un parent, un Drive d''assets.',
 2000, false)
on conflict (id) do nothing;

insert into edges (from_id, to_id, kind, label) values
('onepiece', 'sanji', 'parent_of', ''),
('onepiece', 'chopper', 'parent_of', ''),
('onepiece', 'sanji', 'features', ''),
('onepiece', 'chopper', 'features', ''),
('mugiwara', 'sanji', 'has_member', 'Cuisinier'),
('mugiwara', 'chopper', 'has_member', 'Médecin'),
('mugiwara', 'sanji', 'parent_of', ''),
('mugiwara', 'chopper', 'parent_of', ''),
('luffy', 'sanji', 'related', 'Équipage'),
('luffy', 'chopper', 'related', 'Équipage')
on conflict (from_id, to_id, kind) do nothing;

insert into wiki_pages (id, node_id, title, body, sort_order) values
('w-op-monde', 'onepiece', 'Le monde',
 'Quatre mers, Red Line, Grand Line, Calm Belt. One Piece n''est pas un article : c''est un Node-parent dont chaque île, chaque équipage et chaque fruit peut devenir un enfant. Ce wiki vit dans le Node, pas à côté.',
 0),
('w-op-fruits', 'onepiece', 'Fruits du démon',
 'Paramecia, Zoan, Logia. Luffy (Gom Gom / Hito Hito Nika) est le cas d''école : le pouvoir est une propriété du personnage-enfant, pas un tag. Reliez un fruit à son porteur par une arête.',
 1),
('w-op-marine', 'onepiece', 'Marine & Yonko',
 'L''équilibre des trois grandes puissances. Un univers officiel aurait ces pages : ici elles sont des fiches wiki du Node One Piece, liées au graphe des personnages.',
 2),
('w-op-equipage', 'onepiece', 'Chapeau de paille',
 'Luffy, Zoro, Nami, Sanji, Chopper — et le groupe Mugiwara comme nœud parent. Ouvrez un membre : vous remontez à l''œuvre, à Oda, à l''équipage.',
 3),
('w-sg1-lore', 'sg1', 'Bible SG-1',
 'Porte des étoiles, Goa''uld, Tok''ra, Jaffa. La série est un Node-parent : personnages, saisons et Drive de production sont des enfants et des dossiers.',
 0),
('w-orion-process', 'orion', 'Processus de recrutement',
 'Maison Orion publie des offres-enfants. Chaque candidat traverse un parcours (Drive : briefs, épreuves, GDD). Le Node recruteur n''est pas une page carrière : c''est un ATS dans le graphe.',
 0)
on conflict (id) do nothing;

insert into timeline_events (id, node_id, year_label, title, body, sort_order) values
('t-op-97', 'onepiece', '1997', 'East Blue', 'Luffy prend la mer. Le Node-univers s''ouvre : premier enfant, premier équipier.', 0),
('t-op-alabasta', 'onepiece', 'Alabasta', 'Royaume de la sécheresse', 'Arc politique. Nami, Vivi, Crocodile — le wiki s''épaissit.', 1),
('t-op-enies', 'onepiece', 'Enies Lobby', 'Je te ramènerai', 'Déclaration à Robin. L''équipage devient une famille — le groupe Mugiwara se solidifie dans le graphe.', 2),
('t-op-marineford', 'onepiece', 'Marineford', 'Guerre au sommet', 'Ace, Barbe Blanche, la Marine. Fiche vidéo d''analyse liée dans le Drive.', 3),
('t-op-wano', 'onepiece', 'Wano', 'Pays des samouraïs', 'Kaido, Zoro, Luffy Gear 5. Le lore rejoint le fruit mythique.', 4),
('t-op-egg', 'onepiece', 'Egghead', 'Siècle oublié', 'Vegapunk, vérité du monde. Le wiki n''est jamais fini : le Node grossit.', 5),
('t-sg1-97', 'sg1', '1997', 'Saison 1', 'Formation du SG-1. Enfants : O''Neill, Carter, Jackson, Teal''c.', 0),
('t-sg1-07', 'sg1', '2007', 'Fin de série', 'Dix saisons. Le parent Anderson agrège encore le rôle.', 1)
on conflict (id) do nothing;

insert into drive_folders (id, node_id, name, sort_order) values
('f-op-lore', 'onepiece', 'Lore', 0),
('f-op-art', 'onepiece', 'Art & cartes', 1),
('f-op-video', 'onepiece', 'Vidéos', 2),
('f-op-shop', 'onepiece', 'Merch', 3),
('f-sg-prod', 'sg1', 'Production', 0),
('f-sg-video', 'sg1', 'Fiches vidéo', 1),
('f-or-briefs', 'orion', 'Briefs & épreuves', 0),
('f-or-vivier', 'orion', 'Vivier', 1),
('f-at-pieces', 'atelier', 'Pièces', 0),
('f-rda-roles', 'rda', 'Rôles', 0)
on conflict (id) do nothing;

insert into drive_files (id, node_id, folder_id, name, kind, size_label, version, summary, chapters, transcript, sort_order) values
('df-op-map', 'onepiece', 'f-op-art', 'Carte du monde · Grand Line', 'image', '4.2 Mo', 3,
 'Carte annotée : quatre mers, Red Line, îles clés. Version 3 — Egghead.', '', '', 0),
('df-op-fruits', 'onepiece', 'f-op-lore', 'Compendium des fruits du démon', 'pdf', '1.1 Mo', 2,
 'Classement Paramecia / Zoan / Logia, porteurs, réveil. Attachable à n''importe quelle fiche personnage.', '', '', 1),
('df-op-marineford', 'onepiece', 'f-op-video', 'Analyse · Marineford', 'video', '18:40', 4,
 'Fiche vidéo : pas une pièce jointe. Chapitres, transcript, lien vers l''événement chronologie.',
 E'00:00 — Les camps\n03:10 — Ace\n08:00 — Barbe Blanche\n14:20 — Luffy\n17:00 — Après-guerre',
 'Marineford comme nœud narratif : la vidéo est un objet du Drive, liée à la chronologie et à Luffy.', 0),
('df-op-gear5', 'onepiece', 'f-op-video', 'Luffy · éveil Nika', 'video', '09:12', 2,
 'Portrait de l''éveil. Relie le personnage-enfant Luffy au wiki Fruits.',
 E'00:00 — Zoan mythique\n03:40 — Wano\n07:00 — Implications lore',
 'Le Drive porte les rushes ; la fiche personnage porte le graphe.', 1),
('df-op-bible', 'onepiece', 'f-op-lore', 'Bible personnages · Mugiwara', 'doc', '280 Ko', 6,
 'Fiches Luffy, Zoro, Nami, Sanji, Chopper — identifiants Node, relations parent.', '', '', 2),
('df-sg-bible', 'sg1', 'f-sg-prod', 'Bible de série · SG-1', 'doc', '640 Ko', 11,
 'Règles de la porte, arcs Goa''uld, ton. Document de production dans le Drive du Node.', '', '', 0),
('df-sg-jack', 'sg1', 'f-sg-video', 'Portrait Jack O''Neill', 'video', '12:40', 3,
 'Fiche vidéo du commandant, aussi attachée au personnage-enfant.',
 E'00:00 — La porte\n01:12 — Charlie\n04:05 — SG-1\n08:20 — Humour\n11:00 — Anderson',
 'Cross-link Drive série ↔ fiche personnage.', 0),
('df-or-epreuve', 'orion', 'f-or-briefs', 'Épreuve 7 étapes · Game Design', 'pdf', '420 Ko', 5,
 'GDD, économie, live-ops, pitch. Le Drive recruteur est le vivier et le process.', '', 'Simulation métier complète pour l''offre Lead Game Designer.', 0),
('df-or-gdd', 'orion', 'f-or-briefs', 'Template GDD spatial RPG', 'doc', '110 Ko', 2,
 'Document maître proposé aux candidats. Versionné.', '', '', 1),
('df-at-labra', 'atelier', 'f-at-pieces', 'Labradorite · making-of', 'video', '04:18', 1,
 'Taille de la pièce, liée au produit-enfant.',
 E'00:00 — Brut\n01:20 — Cabochon\n03:00 — Inclusion',
 'Le Drive de l''atelier nourrit la fiche produit.', 0),
('df-rda-showreel', 'rda', 'f-rda-roles', 'Showreel MacGyver → O''Neill', 'video', '08:15', 2,
 'Une vidéo-parent pour tous les rôles-enfants.',
 E'00:00 — Vancouver\n01:40 — MacGyver\n04:10 — SG-1\n06:50 — Producteur',
 'Le Drive de l''acteur relie les univers.', 0)
on conflict (id) do nothing;
