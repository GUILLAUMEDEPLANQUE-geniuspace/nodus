/**
 * platform-api — Drive blobs, panier, checkout, profil, ATS, notifs, DM, visites.
 *
 * Sécurité :
 * - bytes_b64 d'un blob locked n'est renvoyé que si grant vidéo OU staff.
 * - checkout démo : status=paid immédiat. Prod = Stripe session + webhook
 *   qui seul passe paid et appelle unlockVideo.
 * - Toute écriture staff passe assertMinRole.
 */
import { createServerFn } from "@tanstack/react-start";
import { z } from "zod";
import { assertMinRole } from "@/lib/acl";
import { authMiddleware } from "@/lib/auth/middleware";
import { getSql } from "@/lib/db";
import type { Candidate, CartLine, DmMessage, FollowedNode, Notification, Profile } from "@/lib/platform";

type DbNode = { id: string; slug: string; owner_id: string | null };

async function nodeBySlug(slug: string) {
  const sql = await getSql();
  const found = await sql.query<DbNode>(`select id, slug, owner_id from nodes where slug = $1`, [slug]);
  return { sql, node: found[0] ?? null };
}

export const recordVisit = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ slug: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) return { ok: false };
    await sql.query(`insert into node_visits (id, user_id, node_id) values ($1, $2, $3)`, [
      crypto.randomUUID(),
      context.userId,
      node.id,
    ]);
    return { ok: true };
  });

export const getMyProfile = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await getSql();
    const rows = await sql.query<{
      user_id: string;
      display_name: string;
      bio: string;
      cover_url: string;
      locale: string;
    }>(`select user_id, display_name, bio, cover_url, locale from profiles where user_id = $1`, [context.userId]);
    const p = rows[0];
    const follows = await sql.query<{ id: string; slug: string; title: string; kind: string }>(
      `select n.id, n.slug, n.title, n.kind from profile_follows f join nodes n on n.id = f.node_id
       where f.user_id = $1`,
      [context.userId],
    );
    const visits = await sql.query<{ slug: string; title: string }>(
      `select n.slug, n.title from node_visits v join nodes n on n.id = v.node_id
       where v.user_id = $1 order by v.visited_at desc limit 8`,
      [context.userId],
    );
    const profile: Profile = p
      ? { userId: p.user_id, displayName: p.display_name, bio: p.bio, coverUrl: p.cover_url, locale: p.locale }
      : { userId: context.userId, displayName: "", bio: "", coverUrl: "", locale: "fr" };
    return { profile, follows: follows as FollowedNode[], visits };
  });

export const saveProfile = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      displayName: z.string().trim().max(60),
      bio: z.string().trim().max(400),
      coverUrl: z.string().trim().max(200).optional().default(""),
      locale: z.enum(["fr", "en", "ja"]).optional().default("fr"),
    }),
  )
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    await sql.query(
      `insert into profiles (user_id, display_name, bio, cover_url, locale)
       values ($1, $2, $3, $4, $5)
       on conflict (user_id) do update set display_name = $2, bio = $3, cover_url = $4, locale = $5`,
      [context.userId, data.displayName, data.bio, data.coverUrl ?? "", data.locale ?? "fr"],
    );
    return { ok: true };
  });

export const followNode = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ slug: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) throw new Error("Univers introuvable");
    await sql.query(`insert into profile_follows (user_id, node_id) values ($1, $2) on conflict do nothing`, [
      context.userId,
      node.id,
    ]);
    return { ok: true };
  });

/** Upload Drive. Cap 1.4 Mo (base64). Prod = S3 multipart + virus scan. */
export const uploadDriveBlob = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      name: z.string().trim().min(1).max(120),
      kind: z.enum(["video", "image", "pdf", "doc", "audio"]),
      mime: z.string().max(80),
      base64: z.string().min(8).max(2_000_000),
      locked: z.boolean().optional().default(false),
    }),
  )
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) throw new Error("Univers introuvable");
    await assertMinRole(sql, node.id, context.userId, "mod");
    const id = crypto.randomUUID();
    await sql.query(
      `insert into drive_files (id, node_id, name, kind, summary, size_label, version)
       values ($1, $2, $3, $4, 'upload', $5, 1)`,
      [id, node.id, data.name, data.kind, `${Math.round(data.base64.length / 1370)} Ko`],
    );
    await sql.query(`insert into drive_blobs (file_id, mime, bytes_b64, locked) values ($1, $2, $3, $4)`, [
      id,
      data.mime,
      data.base64,
      data.locked ?? false,
    ]);
    return { id };
  });

/**
 * Lecture blob. locked => staff OU grant sur une vidéo du même Node.
 * Ne jamais coller bytes_b64 dans getNodeUniverse.
 */
export const readDriveBlob = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .validator(z.object({ fileId: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const rows = await sql.query<{
      file_id: string;
      mime: string;
      bytes_b64: string;
      locked: boolean;
      node_id: string;
    }>(
      `select b.file_id, b.mime, b.bytes_b64, b.locked, f.node_id
       from drive_blobs b join drive_files f on f.id = b.file_id where b.file_id = $1`,
      [data.fileId],
    );
    const row = rows[0];
    if (!row) throw new Error("Fichier introuvable");
    if (row.locked) {
      const staff = await sql.query(
        `select 1 from node_staff where node_id = $1 and user_id = $2
         union select 1 from nodes where id = $1 and owner_id = $2
         union select 1 from video_grants g
           join node_media m on m.id = g.media_id
          where g.user_id = $2 and m.node_id = $1`,
        [row.node_id, context.userId],
      );
      if (!staff[0]) throw new Error("Fichier verrouillé");
    }
    return { mime: row.mime, base64: row.bytes_b64 };
  });

export const getCart = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await getSql();
    const rows = await sql.query<{
      id: string;
      product_id: string;
      title: string;
      price: string;
      qty: number;
    }>(
      `select c.id, c.product_id, p.title, p.price, c.qty
       from cart_items c join shop_products p on p.id = c.product_id
       where c.user_id = $1`,
      [context.userId],
    );
    return rows.map(
      (r): CartLine => ({
        id: r.id,
        productId: r.product_id,
        title: r.title,
        price: r.price,
        qty: Number(r.qty),
      }),
    );
  });

export const addToCart = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ productId: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const existing = await sql.query<{ id: string; qty: number }>(
      `select id, qty from cart_items where user_id = $1 and product_id = $2`,
      [context.userId, data.productId],
    );
    if (existing[0]) {
      await sql.query(`update cart_items set qty = qty + 1 where id = $1`, [existing[0].id]);
      return { id: existing[0].id };
    }
    const id = crypto.randomUUID();
    await sql.query(`insert into cart_items (id, user_id, product_id, qty) values ($1, $2, $3, 1)`, [
      id,
      context.userId,
      data.productId,
    ]);
    return { id };
  });

/**
 * Checkout démo. Prod : créer PaymentIntent, ne marquer paid que sur webhook.
 * Commission modèle 6–7 % : documentée, pas prélevée ici.
 */
export const checkoutCart = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ provider: z.enum(["card", "crypto"]).optional().default("card") }))
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const lines = await sql.query<{ id: string; product_id: string; title: string; price: string; node_id: string }>(
      `select c.id, c.product_id, p.title, p.price, p.node_id
       from cart_items c join shop_products p on p.id = c.product_id
       where c.user_id = $1`,
      [context.userId],
    );
    if (!lines.length) throw new Error("Panier vide");
    const orderId = crypto.randomUUID();
    const total = lines.map((l) => l.price).join(" + ");
    await sql.query(`insert into orders (id, user_id, total_label, status, provider) values ($1, $2, $3, 'paid', $4)`, [
      orderId,
      context.userId,
      total,
      data.provider ?? "card",
    ]);
    for (const l of lines) {
      await sql.query(`insert into order_items (id, order_id, product_id, title, price) values ($1, $2, $3, $4, $5)`, [
        crypto.randomUUID(),
        orderId,
        l.product_id,
        l.title,
        l.price,
      ]);
      const medias = await sql.query<{ id: number }>(
        `select id from node_media where node_id = $1 and kind = 'video'`,
        [l.node_id],
      );
      for (const m of medias) {
        await sql.query(`insert into video_grants (user_id, media_id) values ($1, $2) on conflict do nothing`, [
          context.userId,
          m.id,
        ]);
      }
    }
    await sql.query(`delete from cart_items where user_id = $1`, [context.userId]);
    await sql.query(
      `insert into notifications (id, user_id, kind, title, body, href) values ($1, $2, 'shop', $3, $4, '/profil')`,
      [crypto.randomUUID(), context.userId, "Commande payée", `Démo ${data.provider} · ${total}`],
    );
    return { orderId };
  });

export const listNotifications = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await getSql();
    const rows = await sql.query<{
      id: string;
      kind: string;
      title: string;
      body: string;
      href: string;
      read: boolean;
    }>(
      `select id, kind, title, body, href, read from notifications where user_id = $1 order by created_at desc limit 30`,
      [context.userId],
    );
    return rows.map(
      (r): Notification => ({
        id: r.id,
        kind: r.kind,
        title: r.title,
        body: r.body,
        href: r.href,
        read: Boolean(r.read),
      }),
    );
  });

export const markNotifRead = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ id: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    await sql.query(`update notifications set read = true where id = $1 and user_id = $2`, [data.id, context.userId]);
    return { ok: true };
  });

export const listInbox = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .handler(async ({ context }) => {
    const sql = await getSql();
    const threads = await sql.query<{ id: string; title: string }>(
      `select t.id, t.title from dm_threads t join dm_members m on m.thread_id = t.id where m.user_id = $1`,
      [context.userId],
    );
    const messages = await sql.query<{ id: string; thread_id: string; author_id: string; body: string }>(
      `select id, thread_id, author_id, body from dm_messages
       where thread_id in (select thread_id from dm_members where user_id = $1)
       order by created_at`,
      [context.userId],
    );
    return {
      threads,
      messages: messages.map(
        (m): DmMessage => ({ id: m.id, threadId: m.thread_id, authorId: m.author_id, body: m.body }),
      ),
    };
  });

export const sendDm = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(z.object({ threadId: z.string().min(1), body: z.string().trim().min(1).max(500) }))
  .handler(async ({ data, context }) => {
    const sql = await getSql();
    const ok = await sql.query(`select 1 from dm_members where thread_id = $1 and user_id = $2`, [
      data.threadId,
      context.userId,
    ]);
    if (!ok[0]) throw new Error("Pas membre de ce canal");
    const id = crypto.randomUUID();
    await sql.query(`insert into dm_messages (id, thread_id, author_id, body) values ($1, $2, $3, $4)`, [
      id,
      data.threadId,
      context.userId,
      data.body,
    ]);
    return { id };
  });

export const startOrAdvanceCandidate = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      jobId: z.string().min(1),
      questId: z.string().optional(),
      choice: z.string().optional(),
    }),
  )
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) throw new Error("Maison introuvable");
    const existing = await sql.query<{ id: string; step: number }>(
      `select id, step from candidates where job_id = $1 and user_id = $2`,
      [data.jobId, context.userId],
    );
    let candidateId = existing[0]?.id;
    let step = existing[0]?.step ?? 1;
    if (!candidateId) {
      candidateId = crypto.randomUUID();
      await sql.query(
        `insert into candidates (id, node_id, job_id, user_id, step, status) values ($1, $2, $3, $4, 1, 'en_cours')`,
        [candidateId, node.id, data.jobId, context.userId],
      );
      step = 1;
    }
    if (data.questId && data.choice) {
      await sql.query(`insert into candidate_answers (id, candidate_id, quest_id, choice) values ($1, $2, $3, $4)`, [
        crypto.randomUUID(),
        candidateId,
        data.questId,
        data.choice,
      ]);
      step = Math.min(7, step + 1);
      await sql.query(`update candidates set step = $1 where id = $2`, [step, candidateId]);
    }
    return { candidateId, step };
  });

export const listCandidates = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .validator(z.object({ slug: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) throw new Error("Maison introuvable");
    await assertMinRole(sql, node.id, context.userId, "mod");
    const rows = await sql.query<{ id: string; job_id: string; user_id: string; step: number; status: string }>(
      `select id, job_id, user_id, step, status from candidates where node_id = $1`,
      [node.id],
    );
    return rows.map(
      (r): Candidate => ({
        id: r.id,
        jobId: r.job_id,
        userId: r.user_id,
        step: Number(r.step),
        status: r.status,
      }),
    );
  });

export const listRecentForHome = createServerFn({ method: "GET" }).handler(async () => {
  const sql = await getSql();
  return sql.query<{ slug: string; title: string; kind: string }>(
    `select slug, title, kind from nodes where featured = true order by title limit 6`,
  );
});

/** Rôle du visiteur connecté. Pour cacher l'éditeur SEO / Studio. */
export const getMyRole = createServerFn({ method: "GET" })
  .middleware([authMiddleware])
  .validator(z.object({ slug: z.string().min(1) }))
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) return { role: null as string | null };
    const { roleOnNode } = await import("@/lib/acl");
    const role = await roleOnNode(sql, node.id, context.userId);
    return { role };
  });

/**
 * Écriture meta SEO. Owner / admin seulement.
 * Le rendu public (head + JSON-LD) lit la même table — Google voit le résultat, pas le formulaire.
 */
export const saveNodeSeo = createServerFn({ method: "POST" })
  .middleware([authMiddleware])
  .validator(
    z.object({
      slug: z.string().min(1),
      title: z.string().trim().max(70),
      description: z.string().trim().max(160),
      keywords: z.string().trim().max(200),
      noindex: z.boolean().optional().default(false),
    }),
  )
  .handler(async ({ data, context }) => {
    const { sql, node } = await nodeBySlug(data.slug);
    if (!node) throw new Error("Univers introuvable");
    await assertMinRole(sql, node.id, context.userId, "admin");
    await sql.query(
      `insert into node_seo (node_id, title, description, keywords, noindex)
       values ($1, $2, $3, $4, $5)
       on conflict (node_id) do update set title = $2, description = $3, keywords = $4, noindex = $5`,
      [node.id, data.title, data.description, data.keywords, data.noindex ?? false],
    );
    return { ok: true };
  });
