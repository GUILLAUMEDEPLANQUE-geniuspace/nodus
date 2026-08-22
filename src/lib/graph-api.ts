/**
 * graph-api — unique porte d'entrée SQL du graphe NODUS.
 *
 * Lecture publique (getNodeUniverse, search, explore).
 * Écriture authentifiée (forum, Drive, onglets, CCK, boutique, playlists).
 *
 * getNodeUniverse hydrate TOUT l'habitat d'un Node : enfants, Drive, wiki,
 * CCK, onglets, staff, forum, boutique, playlists. Les peaux (LivingWorld /
 * VeraHouse) ne refont pas de fetch métier.
 */
import { createServerFn } from "@tanstack/react-start";
import { z } from "zod";
import { getSql } from "@/lib/db";
import { authMiddleware } from "@/lib/auth/middleware";
import {
  EDGE_KINDS,
  EDGE_LABEL,
  NODE_KINDS,
  type CckField,
  type DriveFile,
  type DriveFolder,
  type EdgeKind,
  type ForumCategory,
  type ForumReply,
  type GraphMedia,
  type GraphNode,
  type GuildMessage,
  type LiveLine,
  type Neighbor,
  type NodeBundle,
  type NodeKind,
  type NodeUniverse,
  type Playlist,
  type Quest,
  type SalonRoom,
  type ShopProduct,
  type StaffMember,
  type Thread,
  type TimelineEvent,
  type UniverseTab,
  type VideoAsset,
  type VideoNews,
  type WikiPage,
} from "@/lib/graph";

type DbNode = {
  id: string;
  slug: string;
  kind: string;
  title: string;
  subtitle: string;
  summary: string;
  body: string;
  year_start: number | null;
  year_end: number | null;
  featured: boolean;
  owner_id: string | null;
};

function mapNode(r: DbNode): GraphNode {
  return {
    id: r.id,
    slug: r.slug,
    kind: r.kind as NodeKind,
    title: r.title,
    subtitle: r.subtitle,
    summary: r.summary,
    body: r.body,
    yearStart: r.year_start,
    yearEnd: r.year_end,
    featured: Boolean(r.featured),
    ownerId: r.owner_id,
  };
}

const nodeSelect = `id, slug, kind, title, subtitle, summary, body, year_start, year_end, featured, owner_id`;

export const listFeatured = createServerFn({ method: "GET" }).handler(async () => {
  const sql = await getSql();
  const featured = await sql.query<DbNode>(
    `select ${nodeSelect} from nodes where featured = true order by title`,
  );
  return featured.map(mapNode);
});

export const listByKind = createServerFn({ method: "GET" }).handler(async () => {
  const sql = await getSql();
  const rows = await sql.query<DbNode>(
    `select ${nodeSelect} from nodes order by kind, title`,
  );
  return rows.map(mapNode);
});

export const searchNodes = createServerFn({ method: "GET" })
  .validator(z.object({ q: z.string().trim().max(80) }))
  .handler(async ({ data }) => {
    const sql = await getSql();
    const q = `%${data.q}%`;
    const rows = await sql.query<DbNode>(
      `select ${nodeSelect} from nodes
       where title ilike $1 or subtitle ilike $1 or summary ilike $1 or slug ilike $1
       order by featured desc, title
       limit 48`,
      [q],
    );
    return rows.map(mapNode);
  });

export const getExploreGraph = createServerFn({ method: "GET" }).handler(async () => {
  const sql = await getSql();
  const nodes = (await sql.query<DbNode>(`select ${nodeSelect} from nodes order by title`)).map(
    mapNode,
  );
  const edges = await sql.query<{ from_id: string; to_id: string; kind: string; label: string }>(
    `select from_id, to_id, kind, label from edges`,
  );
  return { nodes, edges };
});

export const getNodeBundle = createServerFn({ method: "GET" })
  .validator(z.object({ slug: z.string().min(1) }))
  .handler(async ({ data }): Promise<NodeBundle | null> => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(
      `select ${nodeSelect} from nodes where slug = $1 limit 1`,
      [data.slug],
    );
    const row = found[0];
    if (!row) return null;
    const node = mapNode(row);

    const tags = await sql.query<{ tag: string }>(
      `select tag from node_tags where node_id = $1 order by tag`,
      [node.id],
    );

    const mediaRows = await sql.query<{
      id: number;
      node_id: string;
      kind: string;
      title: string;
      url: string;
      duration: string;
      genre: string;
      chapters: string;
      transcript: string;
      season: string;
      episode: string;
      language: string;
      difficulty: string;
      ribbon: string;
      mode: string;
      access_kind: string;
      teaser_sec: number;
      price: string;
      views: number;
      rating: string;
    }>(
      `select id, node_id, kind, title, url, duration, genre, chapters, transcript,
              coalesce(season, '') as season,
              coalesce(episode, '') as episode,
              coalesce(language, '') as language,
              coalesce(difficulty, '') as difficulty,
              coalesce(ribbon, '') as ribbon,
              coalesce(mode, 'lore') as mode,
              coalesce(access_kind, 'free') as access_kind,
              coalesce(teaser_sec, 0) as teaser_sec,
              coalesce(price, '') as price,
              coalesce(views, 0) as views,
              coalesce(rating, '') as rating
       from node_media
       where node_id = $1
          or node_id in (select to_id from edges where from_id = $1)
       order by sort_order, id`,
      [node.id],
    );
    const media: GraphMedia[] = mediaRows.map((m) => ({
      id: Number(m.id),
      nodeId: m.node_id,
      kind: m.kind,
      title: m.title,
      url: m.url,
      duration: m.duration,
      genre: m.genre,
      chapters: m.chapters,
      transcript: m.transcript,
      season: m.season,
      episode: m.episode,
      language: m.language,
      difficulty: m.difficulty,
      ribbon: m.ribbon,
      mode: m.mode,
      accessKind: m.access_kind,
      teaserSec: Number(m.teaser_sec),
      price: m.price,
      views: Number(m.views),
      rating: m.rating,
    }));

    type EdgeRow = DbNode & {
      edge_kind: string;
      edge_label: string;
      note: string;
      direction: "out" | "in";
    };

    const outRows = await sql.query<EdgeRow>(
      `select n.id, n.slug, n.kind, n.title, n.subtitle, n.summary, n.body,
              n.year_start, n.year_end, n.featured, n.owner_id,
              e.kind as edge_kind, e.label as edge_label, e.note, 'out' as direction
       from edges e join nodes n on n.id = e.to_id
       where e.from_id = $1`,
      [node.id],
    );
    const inRows = await sql.query<EdgeRow>(
      `select n.id, n.slug, n.kind, n.title, n.subtitle, n.summary, n.body,
              n.year_start, n.year_end, n.featured, n.owner_id,
              e.kind as edge_kind, e.label as edge_label, e.note, 'in' as direction
       from edges e join nodes n on n.id = e.from_id
       where e.to_id = $1`,
      [node.id],
    );

    const neighbors: Neighbor[] = [...outRows, ...inRows].map((r) => {
      const edgeKind = r.edge_kind as EdgeKind;
      const meta = EDGE_LABEL[edgeKind] ?? { out: r.edge_kind, inn: r.edge_kind };
      return {
        direction: r.direction,
        edgeKind,
        label: r.edge_label || (r.direction === "out" ? meta.out : meta.inn),
        note: r.note,
        node: mapNode(r),
      };
    });

    const parents = inRows.filter((r) => r.edge_kind === "parent_of").map(mapNode);
    const children = outRows.filter((r) => r.edge_kind === "parent_of").map(mapNode);

    const lineage: GraphNode[] = [];
    const seen = new Set<string>([node.id]);
    let frontier = parents.map((p) => p.id);
    let guard = 0;
    while (frontier.length && guard < 8) {
      guard += 1;
      const next: string[] = [];
      for (const id of frontier) {
        if (seen.has(id)) continue;
        seen.add(id);
        const n = inRows.find((r) => r.id === id) ?? (await loadNode(sql, id));
        if (n) {
          const mapped = mapNode(n);
          lineage.unshift(mapped);
          const more = await sql.query<{ from_id: string }>(
            `select from_id from edges where to_id = $1 and kind = 'parent_of'`,
            [mapped.id],
          );
          for (const m of more) next.push(m.from_id);
        }
      }
      frontier = next;
    }

    return {
      node,
      tags: tags.map((t) => t.tag),
      media,
      neighbors,
      parents,
      children,
      lineage,
    };
  });

async function loadNode(sql: Awaited<ReturnType<typeof getSql>>, id: string) {
  const rows = await sql.query<DbNode>(`select ${nodeSelect} from nodes where id = $1`, [id]);
  return rows[0];
}

const kindEnum = z.enum(NODE_KINDS);
const edgeEnum = z.enum(EDGE_KINDS);

function slugify(title: string) {
  return title
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "")
    .slice(0, 48);
}

export const createNode = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      kind: kindEnum,
      title: z.string().trim().min(2).max(120),
      subtitle: z.string().trim().max(160).optional().default(""),
      summary: z.string().trim().max(800).optional().default(""),
      parentSlug: z.string().trim().max(80).optional(),
      edgeKind: edgeEnum.optional(),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const id = crypto.randomUUID();
    const slug = `${slugify(data.title) || "node"}-${id.slice(0, 6)}`;
    await sql.query(
      `insert into nodes (id, slug, kind, title, subtitle, summary, owner_id)
       values ($1, $2, $3, $4, $5, $6, $7)`,
      [id, slug, data.kind, data.title, data.subtitle ?? "", data.summary ?? "", context.userId],
    );
    if (data.parentSlug) {
      const parent = await sql.query<DbNode>(
        `select ${nodeSelect} from nodes where slug = $1 limit 1`,
        [data.parentSlug],
      );
      const p = parent[0];
      if (p) {
        const edgeKind = data.edgeKind ?? "parent_of";
        await sql.query(
          `insert into edges (from_id, to_id, kind, label) values ($1, $2, $3, $4)
           on conflict (from_id, to_id, kind) do nothing`,
          [p.id, id, edgeKind, ""],
        );
        if (edgeKind !== "parent_of") {
          await sql.query(
            `insert into edges (from_id, to_id, kind, label) values ($1, $2, 'parent_of', $3)
             on conflict (from_id, to_id, kind) do nothing`,
            [p.id, id, ""],
          );
        }
      }
    }
    await seedNodeShell(sql, id, data.kind);
    return { slug };
  });

async function seedNodeShell(sql: Awaited<ReturnType<typeof getSql>>, nodeId: string, kind: string) {
  const folders =
    kind === "series" || kind === "franchise"
      ? ["Lore", "Art & cartes", "Vidéos", "Merch"]
      : kind === "company"
        ? ["Briefs", "Vivier", "Médias"]
        : ["Médias", "Notes"];
  let i = 0;
  for (const name of folders) {
    await sql.query(
      `insert into drive_folders (id, node_id, name, sort_order) values ($1, $2, $3, $4)`,
      [crypto.randomUUID(), nodeId, name, i],
    );
    i += 1;
  }
  await sql.query(
    `insert into wiki_pages (id, node_id, title, body, sort_order) values ($1, $2, $3, $4, 0)`,
    [
      crypto.randomUUID(),
      nodeId,
      "Introduction",
      "Première page du wiki de ce Node. Ajoutez lore, process, ou bible selon votre univers.",
    ],
  );
  await sql.query(
    `insert into timeline_events (id, node_id, year_label, title, body, sort_order) values ($1, $2, $3, $4, $5, 0)`,
    [crypto.randomUUID(), nodeId, "Début", "Ouverture du Node", "Premier jalon. La chronologie grandit avec l'univers."],
  );
}

export const getNodeUniverse = createServerFn({ method: "GET" })
  .validator(z.object({ slug: z.string().min(1) }))
  .handler(async ({ data }): Promise<NodeUniverse | null> => {
    const bundle = await getNodeBundle({ data: { slug: data.slug } });
    if (!bundle) return null;
    const sql = await getSql();
    const folders = await sql.query<{
      id: string;
      node_id: string;
      parent_id: string | null;
      name: string;
    }>(`select id, node_id, parent_id, name from drive_folders where node_id = $1 order by sort_order, name`, [
      bundle.node.id,
    ]);
    const files = await sql.query<{
      id: string;
      node_id: string;
      folder_id: string | null;
      name: string;
      kind: string;
      size_label: string;
      version: number;
      summary: string;
      chapters: string;
      transcript: string;
    }>(
      `select id, node_id, folder_id, name, kind, size_label, version, summary, chapters, transcript
       from drive_files where node_id = $1 order by sort_order, name`,
      [bundle.node.id],
    );
    const wiki = await sql.query<{ id: string; title: string; body: string }>(
      `select id, title, body from wiki_pages where node_id = $1 order by sort_order, title`,
      [bundle.node.id],
    );
    const timeline = await sql.query<{ id: string; year_label: string; title: string; body: string }>(
      `select id, year_label, title, body from timeline_events where node_id = $1 order by sort_order, year_label`,
      [bundle.node.id],
    );
    const threads = await sql.query<{
      id: string;
      kind: string;
      title: string;
      author: string;
      body: string;
      replies: number;
      cover: string;
      views: number;
      fires: number;
    }>(
      `select id, kind, title, author, body, replies,
              coalesce(cover, '') as cover,
              coalesce(views, 0) as views,
              coalesce(fires, 0) as fires
       from threads where node_id = $1`,
      [bundle.node.id],
    );
    const messages = await sql.query<{ id: string; author: string; body: string }>(
      `select id, author, body from guild_messages where node_id = $1`,
      [bundle.node.id],
    );
    const cck = await sql.query<{
      id: string;
      field_key: string;
      label: string;
      value: string;
      field_type: string;
      target_kind: string;
      target_id: string;
    }>(
      `select id, field_key, label, value,
              coalesce(field_type, 'text') as field_type,
              coalesce(target_kind, 'node') as target_kind,
              coalesce(target_id, '') as target_id
       from cck_fields where node_id = $1 order by sort_order`,
      [bundle.node.id],
    );
    const tabs = await sql.query<{ id: string; tab_key: string; label: string; icon: string }>(
      `select id, tab_key, label, icon from node_tabs where node_id = $1 and enabled = true order by sort_order`,
      [bundle.node.id],
    );
    const staff = await sql.query<{ id: string; name: string; role: string }>(
      `select id, name, role from node_staff where node_id = $1`,
      [bundle.node.id],
    );
    const categories = await sql.query<{ id: string; title: string; body: string }>(
      `select id, title, body from forum_categories where node_id = $1`,
      [bundle.node.id],
    );
    const replies = await sql.query<{ id: string; thread_id: string; author: string; body: string }>(
      `select id, thread_id, author, body from forum_replies where thread_id in (select id from threads where node_id = $1)`,
      [bundle.node.id],
    );
    const live = await sql.query<{ id: string; thread_id: string; author: string; body: string; kind: string }>(
      `select id, thread_id, author, body, kind from forum_live
       where thread_id in (select id from threads where node_id = $1)`,
      [bundle.node.id],
    );
    const products = await sql.query<{
      id: string;
      title: string;
      price: string;
      summary: string;
      kind: string;
      rating: string;
      votes: number;
      stock: string;
    }>(
      `select id, title, price, summary, kind,
              coalesce(rating, '0') as rating,
              coalesce(votes, 0) as votes,
              coalesce(stock, '') as stock
       from shop_products where node_id = $1`,
      [bundle.node.id],
    );
    const lists = await sql.query<{ id: string; title: string; author: string }>(
      `select id, title, author from playlists where node_id = $1`,
      [bundle.node.id],
    );
    const items = await sql.query<{ id: string; playlist_id: string; title: string; kind: string; duration: string }>(
      `select id, playlist_id, title, kind, duration from playlist_items
       where playlist_id in (select id from playlists where node_id = $1)`,
      [bundle.node.id],
    );
    const theme = await sql.query<{ hero_url: string }>(
      `select hero_url from node_theme where node_id = $1`,
      [bundle.node.id],
    );
    const quests = await sql.query<{
      id: string;
      title: string;
      skill: string;
      prompt: string;
      option_a: string;
      option_b: string;
      body: string;
    }>(
      `select id, title, skill, prompt, option_a, option_b, body from quests where node_id = $1 order by sort_order`,
      [bundle.node.id],
    );
    const rooms = await sql.query<{
      id: string;
      title: string;
      kind: string;
      body: string;
      grid_x: number;
      grid_y: number;
    }>(`select id, title, kind, body, grid_x, grid_y from salon_rooms where node_id = $1`, [bundle.node.id]);
    const assetRows = await sql.query<{
      id: string;
      media_id: number;
      chapter_sec: number;
      name: string;
      kind: string;
      locked: boolean;
      url: string;
    }>(
      `select id, media_id, chapter_sec, name, kind, locked, url from video_assets
       where media_id in (
         select id from node_media
         where node_id = $1 or node_id in (select to_id from edges where from_id = $1)
       )`,
      [bundle.node.id],
    );
    const newsRows = await sql.query<{ id: string; media_id: number; kind: string; body: string }>(
      `select id, media_id, kind, body from video_news
       where media_id in (
         select id from node_media
         where node_id = $1 or node_id in (select to_id from edges where from_id = $1)
       )`,
      [bundle.node.id],
    );
    return {
      ...bundle,
      folders: folders.map(
        (f): DriveFolder => ({
          id: f.id,
          nodeId: f.node_id,
          parentId: f.parent_id,
          name: f.name,
        }),
      ),
      files: files.map(
        (f): DriveFile => ({
          id: f.id,
          nodeId: f.node_id,
          folderId: f.folder_id,
          name: f.name,
          kind: f.kind,
          sizeLabel: f.size_label,
          version: Number(f.version),
          summary: f.summary,
          chapters: f.chapters,
          transcript: f.transcript,
        }),
      ),
      wiki: wiki.map((w): WikiPage => ({ id: w.id, title: w.title, body: w.body })),
      timeline: timeline.map(
        (t): TimelineEvent => ({
          id: t.id,
          yearLabel: t.year_label,
          title: t.title,
          body: t.body,
        }),
      ),
      threads: threads.map(
        (t): Thread => ({
          id: t.id,
          kind: t.kind,
          title: t.title,
          author: t.author,
          body: t.body,
          replies: Number(t.replies),
          cover: t.cover,
          views: Number(t.views),
          fires: Number(t.fires),
        }),
      ),
      messages: messages.map((m): GuildMessage => ({ id: m.id, author: m.author, body: m.body })),
      cck: cck.map(
        (f): CckField => ({
          id: f.id,
          key: f.field_key,
          label: f.label,
          value: f.value,
          fieldType: f.field_type,
          targetKind: f.target_kind,
          targetId: f.target_id,
        }),
      ),
      tabs: tabs.map(
        (t): UniverseTab => ({ id: t.id, key: t.tab_key, label: t.label, icon: t.icon }),
      ),
      staff: staff.map((s): StaffMember => ({ id: s.id, name: s.name, role: s.role })),
      categories: categories.map((c): ForumCategory => ({ id: c.id, title: c.title, body: c.body })),
      replies: replies.map(
        (r): ForumReply => ({ id: r.id, threadId: r.thread_id, author: r.author, body: r.body }),
      ),
      live: live.map(
        (l): LiveLine => ({
          id: l.id,
          threadId: l.thread_id,
          author: l.author,
          body: l.body,
          kind: l.kind,
        }),
      ),
      products: products.map(
        (p): ShopProduct => ({
          id: p.id,
          title: p.title,
          price: p.price,
          summary: p.summary,
          kind: p.kind,
          rating: p.rating,
          votes: Number(p.votes),
          stock: p.stock,
        }),
      ),
      playlists: lists.map(
        (l): Playlist => ({
          id: l.id,
          title: l.title,
          author: l.author,
          items: items
            .filter((i) => i.playlist_id === l.id)
            .map((i) => ({ id: i.id, title: i.title, kind: i.kind, duration: i.duration })),
        }),
      ),
      heroUrl: theme[0]?.hero_url ?? "",
      quests: quests.map(
        (q): Quest => ({
          id: q.id,
          title: q.title,
          skill: q.skill,
          prompt: q.prompt,
          optionA: q.option_a,
          optionB: q.option_b,
          body: q.body,
        }),
      ),
      rooms: rooms.map(
        (r): SalonRoom => ({
          id: r.id,
          title: r.title,
          kind: r.kind,
          body: r.body,
          x: Number(r.grid_x),
          y: Number(r.grid_y),
        }),
      ),
      // Sécurité : url jamais envoyée si locked. Le grant passe par unlockVideo.
      videoAssets: assetRows.map(
        (a): VideoAsset => ({
          id: a.id,
          mediaId: Number(a.media_id),
          chapterSec: Number(a.chapter_sec),
          name: a.name,
          kind: a.kind,
          locked: Boolean(a.locked),
          url: a.locked ? "" : a.url,
        }),
      ),
      videoNews: newsRows.map(
        (n): VideoNews => ({
          id: n.id,
          mediaId: Number(n.media_id),
          kind: n.kind,
          body: n.body,
        }),
      ),
    };
  });

export const addDriveFile = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      folderId: z.string().optional(),
      name: z.string().trim().min(2).max(120),
      kind: z.enum(["video", "image", "pdf", "doc", "audio"]),
      summary: z.string().trim().max(400).optional().default(""),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    // Contribuer au Drive de l'univers (démo). En prod : vérifier node_staff.
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(
      `insert into drive_files (id, node_id, folder_id, name, kind, summary, size_label, version)
       values ($1, $2, $3, $4, $5, $6, 'brouillon', 1)`,
      [id, node.id, data.folderId ?? null, data.name, data.kind, data.summary ?? ""],
    );
    return { id };
  });

export const postGuildMessage = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      body: z.string().trim().min(1).max(400),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    const author = context.userId.slice(0, 8);
    await sql.query(`insert into guild_messages (id, node_id, author, body) values ($1, $2, $3, $4)`, [
      id,
      node.id,
      author,
      data.body,
    ]);
    return { id, author };
  });

/** Forum : un sujet. Tout membre connecté peut poster — le Node vit par la contribution. */
export const postThread = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      title: z.string().trim().min(3).max(140),
      body: z.string().trim().min(2).max(2000),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    const author = context.userId.slice(0, 8);
    await sql.query(
      `insert into threads (id, node_id, kind, title, author, body, replies) values ($1, $2, 'forum', $3, $4, $5, 0)`,
      [id, node.id, data.title, author, data.body],
    );
    return { id, author };
  });

export const postReply = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      threadId: z.string().min(1),
      body: z.string().trim().min(1).max(1200),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const id = crypto.randomUUID();
    const author = context.userId.slice(0, 8);
    await sql.query(`insert into forum_replies (id, thread_id, author, body) values ($1, $2, $3, $4)`, [
      id,
      data.threadId,
      author,
      data.body,
    ]);
    await sql.query(`update threads set replies = replies + 1 where id = $1`, [data.threadId]);
    return { id, author };
  });

export const addUniverseTab = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      label: z.string().trim().min(2).max(24),
      tabKey: z.string().trim().min(2).max(24),
      icon: z.string().trim().min(2).max(24).optional().default("sparkles"),
    }),
  )
  .handler(async ({ data }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(
      `insert into node_tabs (id, node_id, tab_key, label, icon, sort_order) values ($1, $2, $3, $4, $5, 20)`,
      [id, node.id, data.tabKey, data.label, data.icon ?? "sparkles"],
    );
    return { id };
  });

export const addStaffMember = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      name: z.string().trim().min(2).max(40),
      role: z.enum(["admin", "mod", "member"]),
    }),
  )
  .handler(async ({ data }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(`insert into node_staff (id, node_id, name, role) values ($1, $2, $3, $4)`, [
      id,
      node.id,
      data.name,
      data.role,
    ]);
    return { id };
  });

export const addProduct = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      title: z.string().trim().min(2).max(80),
      price: z.string().trim().min(1).max(24),
      summary: z.string().trim().max(200).optional().default(""),
    }),
  )
  .handler(async ({ data }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(
      `insert into shop_products (id, node_id, title, price, summary, kind, rating, votes, stock)
       values ($1, $2, $3, $4, $5, 'objet', '0', 0, 'en stock')`,
      [id, node.id, data.title, data.price, data.summary ?? ""],
    );
    return { id };
  });

export const addPlaylist = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      title: z.string().trim().min(2).max(80),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(`insert into playlists (id, node_id, title, author) values ($1, $2, $3, $4)`, [
      id,
      node.id,
      data.title,
      context.userId.slice(0, 8),
    ]);
    return { id };
  });

/** Builder CCK : pose un champ typé sur node / thread / product / media. */
export const addCckField = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      label: z.string().trim().min(2).max(40),
      key: z.string().trim().min(2).max(40),
      fieldType: z.enum(["text", "html", "choice", "media", "relation", "scale"]),
      targetKind: z.enum(["node", "thread", "product", "media"]),
      targetId: z.string().trim().max(80).optional().default(""),
      value: z.string().trim().max(400).optional().default(""),
    }),
  )
  .handler(async ({ data }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(
      `insert into cck_fields (id, node_id, field_key, label, value, sort_order, field_type, target_kind, target_id)
       values ($1, $2, $3, $4, $5, 50, $6, $7, $8)`,
      [id, node.id, data.key, data.label, data.value ?? "", data.fieldType, data.targetKind, data.targetId ?? ""],
    );
    return { id };
  });

export const listNodeSlugs = createServerFn({ method: "GET" }).handler(async () => {
  const sql = await getSql();
  return sql.query<{ slug: string; kind: string; title: string }>(
    `select slug, kind, title from nodes order by featured desc, title`,
  );
});

export const listForumTopics = createServerFn({ method: "GET" }).handler(async () => {
  const sql = await getSql();
  return sql.query<{ slug: string; id: string; title: string }>(
    `select n.slug, t.id, t.title from threads t join nodes n on n.id = t.node_id where t.kind = 'forum'`,
  );
});

export const postLive = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      threadId: z.string().min(1),
      body: z.string().trim().min(1).max(400),
      kind: z.enum(["text", "video", "product", "node"]).optional().default("text"),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const id = crypto.randomUUID();
    const author = context.userId.slice(0, 8);
    await sql.query(`insert into forum_live (id, thread_id, author, body, kind) values ($1, $2, $3, $4, $5)`, [
      id,
      data.threadId,
      author,
      data.body,
      data.kind ?? "text",
    ]);
    return { id, author };
  });

/** Éclatement sémantique : une réponse Legacy devient un nouveau sujet parent (URL SEO). */
export const promoteReply = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      title: z.string().trim().min(3).max(140),
      body: z.string().trim().min(2).max(2000),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const found = await sql.query<DbNode>(`select ${nodeSelect} from nodes where slug = $1`, [data.slug]);
    const node = found[0];
    if (!node) throw new Error("Univers introuvable");
    const id = crypto.randomUUID();
    await sql.query(
      `insert into threads (id, node_id, kind, title, author, body, replies, cover, views, fires)
       values ($1, $2, 'forum', $3, $4, $5, 0, '', 1, 0)`,
      [id, node.id, data.title, context.userId.slice(0, 8), data.body],
    );
    return { id };
  });

/**
 * Débloque une VOD. Prod = Stripe/USDC. Ici grant auth.
 * Les URLs locked ne sortent jamais du handler public.
 */
export const unlockVideo = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ mediaId: z.number().int().positive() }))
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    await sql.query(
      `insert into video_grants (user_id, media_id) values ($1, $2) on conflict do nothing`,
      [context.userId, data.mediaId],
    );
    const rows = await sql.query<{
      id: string;
      media_id: number;
      chapter_sec: number;
      name: string;
      kind: string;
      locked: boolean;
      url: string;
    }>(`select id, media_id, chapter_sec, name, kind, locked, url from video_assets where media_id = $1`, [
      data.mediaId,
    ]);
    return {
      granted: true,
      assets: rows.map(
        (a): VideoAsset => ({
          id: a.id,
          mediaId: Number(a.media_id),
          chapterSec: Number(a.chapter_sec),
          name: a.name,
          kind: a.kind,
          locked: false,
          url: a.url,
        }),
      ),
    };
  });


