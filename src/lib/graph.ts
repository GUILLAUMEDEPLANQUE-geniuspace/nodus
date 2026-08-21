export const NODE_KINDS = [
  "person",
  "character",
  "work",
  "series",
  "franchise",
  "group",
  "season",
  "job",
  "company",
  "product",
  "place",
  "concept",
] as const;

export type NodeKind = (typeof NODE_KINDS)[number];

export const EDGE_KINDS = [
  "parent_of",
  "portrays",
  "features",
  "contains",
  "created",
  "has_member",
  "offers",
  "appears_in",
  "related",
] as const;

export type EdgeKind = (typeof EDGE_KINDS)[number];

export const KIND_LABEL: Record<NodeKind, string> = {
  person: "Personne",
  character: "Personnage",
  work: "Œuvre",
  series: "Série",
  franchise: "Franchise",
  group: "Groupe",
  season: "Saison",
  job: "Offre",
  company: "Maison",
  product: "Produit",
  place: "Lieu",
  concept: "Concept",
};

export const EDGE_LABEL: Record<EdgeKind, { out: string; inn: string }> = {
  parent_of: { out: "Enfants / rôles", inn: "Parent" },
  portrays: { out: "Interprète", inn: "Interprété par" },
  features: { out: "Met en scène", inn: "Apparaît dans" },
  contains: { out: "Contient", inn: "Fait partie de" },
  created: { out: "A créé", inn: "Créé par" },
  has_member: { out: "Membres", inn: "Membre de" },
  offers: { out: "Offres", inn: "Proposées par" },
  appears_in: { out: "Apparaît dans", inn: "Présence" },
  related: { out: "Lié à", inn: "Lié à" },
};

export type GraphNode = {
  id: string;
  slug: string;
  kind: NodeKind;
  title: string;
  subtitle: string;
  summary: string;
  body: string;
  yearStart: number | null;
  yearEnd: number | null;
  featured: boolean;
  ownerId: string | null;
};

export type GraphMedia = {
  id: number;
  kind: string;
  title: string;
  url: string;
  duration: string;
  genre: string;
  chapters: string;
  transcript: string;
};

export type Neighbor = {
  direction: "out" | "in";
  edgeKind: EdgeKind;
  label: string;
  note: string;
  node: GraphNode;
};

export type NodeBundle = {
  node: GraphNode;
  tags: string[];
  media: GraphMedia[];
  neighbors: Neighbor[];
  parents: GraphNode[];
  children: GraphNode[];
  lineage: GraphNode[];
};

export type DriveFolder = {
  id: string;
  nodeId: string;
  parentId: string | null;
  name: string;
};

export type DriveFile = {
  id: string;
  nodeId: string;
  folderId: string | null;
  name: string;
  kind: string;
  sizeLabel: string;
  version: number;
  summary: string;
  chapters: string;
  transcript: string;
};

export type WikiPage = {
  id: string;
  title: string;
  body: string;
};

export type TimelineEvent = {
  id: string;
  yearLabel: string;
  title: string;
  body: string;
};

export type Thread = {
  id: string;
  kind: string;
  title: string;
  author: string;
  body: string;
  replies: number;
};

export type GuildMessage = {
  id: string;
  author: string;
  body: string;
};

/** Valeur CCK posée sur un Node, un thread, un produit ou une relique. Voir src/lib/cck.ts */
export type CckField = {
  id: string;
  key: string;
  label: string;
  value: string;
  fieldType: string;
  targetKind: string;
  targetId: string;
};

export type UniverseTab = {
  id: string;
  key: string;
  label: string;
  icon: string;
};

export type StaffMember = {
  id: string;
  name: string;
  role: string;
};

export type ForumCategory = {
  id: string;
  title: string;
  body: string;
};

export type ForumReply = {
  id: string;
  threadId: string;
  author: string;
  body: string;
};

export type ShopProduct = {
  id: string;
  title: string;
  price: string;
  summary: string;
  kind: string;
};

export type Playlist = {
  id: string;
  title: string;
  author: string;
  items: { id: string; title: string; kind: string; duration: string }[];
};

export type NodeUniverse = NodeBundle & {
  folders: DriveFolder[];
  files: DriveFile[];
  wiki: WikiPage[];
  timeline: TimelineEvent[];
  threads: Thread[];
  messages: GuildMessage[];
  cck: CckField[];
  tabs: UniverseTab[];
  staff: StaffMember[];
  categories: ForumCategory[];
  replies: ForumReply[];
  products: ShopProduct[];
  playlists: Playlist[];
  heroUrl: string;
};



