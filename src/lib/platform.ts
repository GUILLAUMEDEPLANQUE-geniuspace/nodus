/** Types plateforme 0.9 — panier, ATS, notifs, DM, profil. */

export type Profile = {
  userId: string;
  displayName: string;
  bio: string;
  coverUrl: string;
  locale: string;
};

export type CartLine = {
  id: string;
  productId: string;
  title: string;
  price: string;
  qty: number;
};

export type Notification = {
  id: string;
  kind: string;
  title: string;
  body: string;
  href: string;
  read: boolean;
};

export type DmMessage = {
  id: string;
  threadId: string;
  authorId: string;
  body: string;
};

export type Candidate = {
  id: string;
  jobId: string;
  userId: string;
  step: number;
  status: string;
};

export type FollowedNode = {
  id: string;
  slug: string;
  title: string;
  kind: string;
};
