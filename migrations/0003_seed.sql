insert into nodes (id, slug, kind, title, subtitle, summary, body, year_start, year_end, featured) values
('rda', 'richard-dean-anderson', 'person', 'Richard Dean Anderson', 'Acteur · producteur',
 'Acteur canadien devenu icône grâce à MacGyver puis au colonel Jack O''Neill dans Stargate SG-1. Une seule fiche parent relie tous ses rôles.',
 'Né en 1950 à Vancouver, Richard Dean Anderson enchaîne d''abord des rôles télévisés avant d''incarner Angus MacGyver (1985-1992), l''agent qui désamorce le monde avec un canif et du scotch. En 1997, il reprend du service sous l''uniforme de Jack O''Neill : humour sec, pop-culture et commandement du SG-1. Cette fiche est le nœud parent : chaque personnage est un enfant, chaque série un univers connecté.',
 1950, null, true),

('jack-oneill', 'jack-oneill', 'character', 'Jack O''Neill', 'Colonel · commandant du SG-1',
 'Officier sarcastique de l''US Air Force, commandant de l''équipe SG-1. Enfant du nœud Richard Dean Anderson — et cœur de la franchise Stargate.',
 'Le colonel Jonathan « Jack » O''Neill a perdu un fils, Charlie, et porte cette blessure sous une ironie permanente. Il dirige SG-1 à travers la porte des étoiles, négocie avec les Goa''uld et collectionne les références à The Simpsons. Ici, il n''est pas un profil isolé : il est l''enfant de son interprète, le membre de son équipe, et une pièce de la franchise.',
 1994, 2011, true),

('macgyver', 'angus-macgyver', 'character', 'Angus MacGyver', 'Agent · ingénieur de terrain',
 'Agent du Phoenix Foundation qui résout les crises sans arme à feu — seulement de la physique, un canif et ce qu''il a dans les poches.',
 'Angus MacGyver est l''autre grand rôle-enfant de Richard Dean Anderson. Pacifiste pragmatique, il transforme un luminaire, un chewing-gum ou une batterie en solution. Relier MacGyver à Jack O''Neill via le même parent, c''est exactement le graphe que NODUS rend visible.',
 1985, 1992, true),

('sg-franchise', 'stargate', 'franchise', 'Stargate', 'Franchise de science-fiction',
 'Univers né du film de 1994, déployé en séries (SG-1, Atlantis, Universe) et films TV. Contient les équipes, les mondes et les personnages.',
 'La franchise Stargate part d''une porte circulaire enfouie en Égypte et s''ouvre sur des galaxies. SG-1 en est le pilier. Dans NODUS, la franchise est le sommet de l''arbre : elle contient les séries, qui contiennent les saisons, les équipes et les visages.',
 1994, 2011, true),

('sg1', 'stargate-sg1', 'series', 'Stargate SG-1', 'Série · 10 saisons',
 'L''équipe SG-1 explore la galaxie via la porte des étoiles. Série-fille de la franchise, parent des personnages et des saisons.',
 'De 1997 à 2007, Stargate SG-1 suit Jack O''Neill, Samantha Carter, Daniel Jackson et Teal''c. Dix saisons, deux films, un ton unique : militaire, mythologique et drôle. Ici, chaque membre, chaque saison et l''interprète principal sont des nœuds reliés — pas des pages orphelines.',
 1997, 2007, true),

('atlantis', 'stargate-atlantis', 'series', 'Stargate Atlantis', 'Série-sœur · Pégase',
 'Expédition humaine dans la cité perdue d''Atlantis, galaxie de Pégase. Série contenue par la franchise Stargate.',
 'Atlantis prolonge SG-1 sans la dupliquer : nouvelle galaxie, Wraiths, cité ancestrale. Relier Atlantis à SG-1 via la franchise montre le graphe plutôt qu''un wiki plat.',
 2004, 2009, false),

('macgyver-series', 'macgyver-1985', 'series', 'MacGyver (1985)', 'Série d''action · 7 saisons',
 'Série originale où Angus MacGyver désamorce le monde à mains nues. Parent du personnage, enfant du même acteur que Jack O''Neill.',
 'Diffusée de 1985 à 1992, la série a fixé l''archétype de l''ingénieur héroïque. Dans NODUS, elle n''est pas un silo : elle partage son interprète avec Stargate, ce qui fait apparaître Richard Dean Anderson comme véritable nœud parent.',
 1985, 1992, true),

('carter', 'samantha-carter', 'character', 'Samantha Carter', 'Major / colonel · astrophysicienne',
 'Cerveau scientifique du SG-1, pilote et soldate. Relier Carter à Amanda Tapping et à l''équipe.',
 'Samantha Carter prouve que l''astrophysique et le terrain peuvent cohabiter. Elle calcule les fenêtres de vortex, pilote un F-302 et commande plus tard. Personnage-enfant d''Amanda Tapping, membre de SG-1.',
 1997, 2011, false),

('amanda', 'amanda-tapping', 'person', 'Amanda Tapping', 'Actrice · réalisatrice',
 'Interprète de Samantha Carter. Parent du personnage, liée à Stargate SG-1.',
 'Amanda Tapping incarne Carter pendant dix saisons, réalise plusieurs épisodes, puis poursuit avec Sanctuary. Sa fiche parent agrège le rôle comme celle d''Anderson agrège O''Neill et MacGyver.',
 1965, null, false),

('daniel', 'daniel-jackson', 'character', 'Daniel Jackson', 'Archéologue · linguiste',
 'L''âme civiliste du SG-1. Il ouvre les cultures là où Jack ouvre le feu.',
 'Daniel Jackson déchiffre les langues, négocie avec les peuples et meurt (presque) trop souvent. Interprété par Michael Shanks, membre central de l''équipe.',
 1994, 2011, false),

('shanks', 'michael-shanks', 'person', 'Michael Shanks', 'Acteur',
 'Interprète de Daniel Jackson, parent du personnage dans le graphe.',
 'Michael Shanks porte Daniel Jackson du film à la série, avec une pause puis un retour. La relation parent_of le relie à son rôle comme Anderson à O''Neill.',
 1970, null, false),

('tealc', 'tealc', 'character', 'Teal''c', 'Jaffa · guerrier du SG-1',
 'Ancien Premier de Apophis, allié indéfectible. Membre de l''équipe, enfant de Christopher Judge.',
 'Teal''c a trahi les Goa''uld pour la liberté des Jaffa. Imposant, sentencieux, amateur de films terrestres. Le graphe le relie à SG-1, à l''équipe et à son interprète.',
 1997, 2007, false),

('judge', 'christopher-judge', 'person', 'Christopher Judge', 'Acteur · scénariste',
 'Interprète de Teal''c. Parent du personnage.',
 'Christopher Judge incarne Teal''c pendant toute la série et écrit plusieurs épisodes. Même motif parent / enfant que les autres piliers du SG-1.',
 1964, null, false),

('sg1-team', 'sg-1', 'group', 'Équipe SG-1', 'Unité d''exploration',
 'Le quatuor historique : O''Neill, Carter, Jackson, Teal''c. Groupe-enfant de la série.',
 'SG-1 n''est pas seulement un sigle : c''est un nœud de groupe dont les membres sont des enfants. On parcourt l''équipe comme une constellation, pas une liste.',
 1997, 2011, false),

('sg1-s1', 'stargate-sg1-saison-1', 'season', 'SG-1 · Saison 1', '1997–1998',
 'Première saison : formation de l''équipe, Apophis, Abydos. Enfant de la série.',
 'La saison 1 pose les règles de la porte, les Goa''uld et le ton. Dans l''arbre, elle est un enfant de Stargate SG-1, aux côtés des personnages.',
 1997, 1998, false),

('wright', 'brad-wright', 'person', 'Brad Wright', 'Co-créateur',
 'Co-créateur de Stargate SG-1 et Atlantis. Relie les séries à leurs auteurs.',
 'Brad Wright, avec Jonathan Glassner, adapte le film en série et tient l''univers sur la durée. Relation created vers SG-1 et Atlantis.',
 1961, null, false),

('onepiece', 'one-piece', 'series', 'One Piece', 'Manga · anime · saga',
 'L''équipage au chapeau de paille cherche le One Piece. Univers parent de Luffy, Zoro, Nami.',
 'Eiichiro Oda publie One Piece depuis 1997. C''est l''exemple otaku de NODUS : une œuvre-parent, des personnages-enfants, un créateur relié, un équipage comme groupe.',
 1997, null, true),

('oda', 'eiichiro-oda', 'person', 'Eiichiro Oda', 'Mangaka',
 'Créateur de One Piece. Parent de l''œuvre entière.',
 'Oda construit un océan de personnages. Ici, une seule fiche créateur ouvre l''arbre : manga, protagonistes, équipage.',
 1975, null, true),

('luffy', 'monkey-d-luffy', 'character', 'Monkey D. Luffy', 'Capitaine · fruit du Gom Gom',
 'Capitaine des Mugiwara, futur Roi des Pirates. Enfant de One Piece et d''Oda via l''œuvre.',
 'Luffy est le nœud-personnage central. Ses liens : série, équipage, créateur. Un fan de manga construit exactement ce graphe dans NODUS.',
 1997, null, true),

('zoro', 'roronoa-zoro', 'character', 'Roronoa Zoro', 'Épéiste · bras droit',
 'Chasseur de pirates devenu numéro deux des Mugiwara. Enfant de l''équipage et de l''œuvre.',
 'Zoro vise le titre du plus grand sabreur. Relier Zoro à Luffy et à l''équipage évite les fiches orphelines.',
 1997, null, false),

('nami', 'nami', 'character', 'Nami', 'Navigatrice',
 'Cartographe de l''équipage, voleuse repentie, cœur tactique des voyages.',
 'Nami relie la mer, les cartes et l''équipage. Dans NODUS, une navigatrice n''est pas un tag : c''est un nœud dans la constellation Mugiwara.',
 1997, null, false),

('mugiwara', 'equipage-chapeau-de-paille', 'group', 'Équipage du Chapeau de paille', 'Mugiwara',
 'Groupe-parent des pirates de Luffy. Enfant de One Piece.',
 'Le navire change, l''équipage grandit. Le groupe reste le nœud qui agrège Luffy, Zoro, Nami et les autres.',
 1997, null, false),

('orion', 'maison-orion', 'company', 'Maison Orion', 'Studio · vivier créatif',
 'Maison de production de jeux. Un recruteur y accroche offres, parcours et médias — aussi puissant qu''un CCK métier.',
 'Orion illustre le Node recruteur : une maison parente, des offres enfants, un drive de briefs. Même mécanique que Stargate : un parent, des rôles, des univers.',
 2018, null, true),

('job-gd', 'lead-game-designer', 'job', 'Lead Game Designer', 'CDI · Paris / remote',
 'Offre-enfant de Maison Orion. Conception systèmes, narration émergente, live-ops.',
 'Vous dirigez la vision gameplay d''un RPG spatial. Salaire publié, simulation d''épreuve, lien vers la maison. L''offre n''est pas une fiche LinkedIn : c''est un nœud dans le graphe du studio.',
 2026, null, true),

('atelier', 'atelier-nocturne', 'company', 'Atelier Nocturne', 'Création ésotérique',
 'Atelier de pièces rituelles et de tarot. Node créateur : produits enfants, vidéos, drive.',
 'L''Atelier Nocturne montre qu''un artisan spirituel a le même graphe qu''un studio ou qu''une franchise : une maison, des objets, des récits.',
 2021, null, false),

('labradorite', 'labradorite-polaire', 'product', 'Labradorite polaire', 'Pierre · pièce unique',
 'Cabochon de labradorite, chatoyance bleue. Produit-enfant de l''Atelier Nocturne.',
 'Chaque pierre a une fiche, une vidéo de taille, un lien vers l''atelier. Le commerce n''est pas un catalogue plat : c''est un enfant du Node créateur.',
 2026, null, true)

on conflict (id) do nothing;

insert into edges (from_id, to_id, kind, label, note) values
('rda', 'jack-oneill', 'parent_of', 'Rôle', 'Personnage-enfant principal'),
('rda', 'macgyver', 'parent_of', 'Rôle', 'Personnage-enfant historique'),
('rda', 'jack-oneill', 'portrays', '1997–2007', 'Colonel Jack O''Neill'),
('rda', 'macgyver', 'portrays', '1985–1992', 'Angus MacGyver'),
('rda', 'sg1', 'appears_in', 'Rôle titre', ''),
('rda', 'macgyver-series', 'appears_in', 'Rôle titre', ''),

('sg-franchise', 'sg1', 'parent_of', 'Série pilier', ''),
('sg-franchise', 'atlantis', 'parent_of', 'Série-sœur', ''),
('sg-franchise', 'sg1', 'contains', '', ''),
('sg-franchise', 'atlantis', 'contains', '', ''),

('sg1', 'jack-oneill', 'parent_of', 'Personnage', ''),
('sg1', 'carter', 'parent_of', 'Personnage', ''),
('sg1', 'daniel', 'parent_of', 'Personnage', ''),
('sg1', 'tealc', 'parent_of', 'Personnage', ''),
('sg1', 'sg1-team', 'parent_of', 'Unité', ''),
('sg1', 'sg1-s1', 'parent_of', 'Saison', ''),
('sg1', 'jack-oneill', 'features', 'Commandant', ''),
('sg1', 'carter', 'features', 'Scientifique', ''),
('sg1', 'daniel', 'features', 'Archéologue', ''),
('sg1', 'tealc', 'features', 'Guerrier', ''),
('sg1', 'sg1-team', 'has_member', '', ''),
('sg1', 'sg1-s1', 'contains', '', ''),

('sg1-team', 'jack-oneill', 'has_member', 'Commandant', ''),
('sg1-team', 'carter', 'has_member', 'Astrophysicienne', ''),
('sg1-team', 'daniel', 'has_member', 'Archéologue', ''),
('sg1-team', 'tealc', 'has_member', 'Jaffa', ''),
('sg1-team', 'jack-oneill', 'parent_of', '', ''),
('sg1-team', 'carter', 'parent_of', '', ''),
('sg1-team', 'daniel', 'parent_of', '', ''),
('sg1-team', 'tealc', 'parent_of', '', ''),

('amanda', 'carter', 'parent_of', 'Rôle', ''),
('amanda', 'carter', 'portrays', '1997–2011', ''),
('shanks', 'daniel', 'parent_of', 'Rôle', ''),
('shanks', 'daniel', 'portrays', '', ''),
('judge', 'tealc', 'parent_of', 'Rôle', ''),
('judge', 'tealc', 'portrays', '', ''),

('wright', 'sg1', 'created', 'Co-créateur', ''),
('wright', 'atlantis', 'created', 'Co-créateur', ''),
('wright', 'sg1', 'parent_of', 'Œuvre', ''),

('macgyver-series', 'macgyver', 'parent_of', 'Protagoniste', ''),
('macgyver-series', 'macgyver', 'features', '', ''),

('jack-oneill', 'carter', 'related', 'Équipiers', 'SG-1'),
('jack-oneill', 'daniel', 'related', 'Équipiers', ''),
('jack-oneill', 'tealc', 'related', 'Équipiers', ''),

('oda', 'onepiece', 'created', 'Auteur', ''),
('oda', 'onepiece', 'parent_of', 'Œuvre', ''),
('oda', 'luffy', 'parent_of', 'Personnage', ''),
('onepiece', 'luffy', 'parent_of', '', ''),
('onepiece', 'zoro', 'parent_of', '', ''),
('onepiece', 'nami', 'parent_of', '', ''),
('onepiece', 'mugiwara', 'parent_of', '', ''),
('onepiece', 'luffy', 'features', 'Protagoniste', ''),
('onepiece', 'zoro', 'features', '', ''),
('onepiece', 'nami', 'features', '', ''),
('onepiece', 'mugiwara', 'has_member', '', ''),
('mugiwara', 'luffy', 'has_member', 'Capitaine', ''),
('mugiwara', 'zoro', 'has_member', 'Épéiste', ''),
('mugiwara', 'nami', 'has_member', 'Navigatrice', ''),
('mugiwara', 'luffy', 'parent_of', '', ''),
('mugiwara', 'zoro', 'parent_of', '', ''),
('mugiwara', 'nami', 'parent_of', '', ''),
('luffy', 'zoro', 'related', 'Équipage', ''),
('luffy', 'nami', 'related', 'Équipage', ''),

('orion', 'job-gd', 'parent_of', 'Offre', ''),
('orion', 'job-gd', 'offers', 'CDI', ''),
('atelier', 'labradorite', 'parent_of', 'Pièce', ''),
('atelier', 'labradorite', 'offers', 'Boutique', '')
on conflict (from_id, to_id, kind) do nothing;

insert into node_tags (node_id, tag) values
('rda', 'acteur'), ('rda', 'stargate'), ('rda', 'macgyver'),
('jack-oneill', 'sg-1'), ('jack-oneill', 'militaire'), ('jack-oneill', 'science-fiction'),
('macgyver', 'action'), ('macgyver', 'ingénierie'),
('sg1', 'science-fiction'), ('sg1', 'porte des étoiles'),
('onepiece', 'manga'), ('onepiece', 'shonen'), ('luffy', 'manga'),
('job-gd', 'recrutement'), ('job-gd', 'jeux vidéo'),
('labradorite', 'ésotérisme'), ('labradorite', 'minéral')
on conflict do nothing;

insert into node_media (node_id, kind, title, url, duration, genre, chapters, transcript, sort_order) values
('jack-oneill', 'video', 'Fiche vidéo · Jack O''Neill', '', '12:40', 'Portrait',
 E'00:00 — Ouverture : la porte\n01:12 — Charlie et la blessure\n04:05 — Commander SG-1\n08:20 — Humour comme armure\n11:00 — Héritage de l''acteur',
 'Jack O''Neill n''est pas un héros lisse. Cette fiche relie le personnage à Richard Dean Anderson, à l''équipe et à la franchise — chapitres cliquables, pas une pièce jointe.',
 0),
('rda', 'video', 'Parcours d''acteur · RDA', '', '08:15', 'Biographie',
 E'00:00 — Vancouver\n01:40 — MacGyver\n04:10 — Stargate SG-1\n06:50 — Producteur',
 'Une seule vidéo-parent pour tous les rôles. Les chapitres pointent vers les nœuds enfants.',
 0),
('macgyver', 'video', 'Anatomie d''un MacGyverism', '', '06:02', 'Analyse',
 E'00:00 — Le canif\n01:10 — Physique de salon\n03:40 — Pacifisme tactique',
 'Comment un rôle-enfant explique l''autre : de MacGyver à O''Neill, même acteur, deux éthiques.',
 0),
('sg1', 'file', 'Dossier de bible · SG-1', '', '', 'Document', '', 'Pitch, arcs Goa''uld, règles de la porte.', 1),
('luffy', 'video', 'Luffy · volonté du D.', '', '09:30', 'Lore',
 E'00:00 — East Blue\n02:15 — Fruit\n05:00 — Équipage\n08:00 — Roi des Pirates',
 'Fiche personnage manga : lore, relations, œuvre parente.',
 0),
('job-gd', 'file', 'Brief d''épreuve · 7 étapes', '', '', 'Recrutement', '', 'Simulation métier : GDD, économie, live-ops, pitch studio.', 0),
('labradorite', 'video', 'Taille de la pièce', '', '04:18', 'Making-of',
 E'00:00 — Brut\n01:20 — Cabochon\n03:00 — Inclusion',
 'La fiche produit n''est pas une photo : c''est une vidéo-chapitre liée à l''atelier parent.',
 0);
