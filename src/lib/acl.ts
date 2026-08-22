/**
 * ACL NODUS — owner > admin > mod > member > public.
 *
 * Lectures : publiques (SEO).
 * Écritures Drive / CCK / onglets / staff : admin | owner.
 * Modération forum / live : mod | admin | owner.
 * Panier / quêtes / DM : user authentifié (ses lignes seulement).
 *
 * Prod : remplacer name-matching par user_id SSO et un cache Redis du staff.
 * Ne jamais croire le rôle envoyé par le client.
 */
import type { getSql } from "@/lib/db";

export type StaffRole = "admin" | "mod" | "member";
type Sql = Awaited<ReturnType<typeof getSql>>;

const RANK: Record<string, number> = { admin: 3, mod: 2, member: 1 };

export async function roleOnNode(sql: Sql, nodeId: string, userId: string): Promise<StaffRole | "owner" | null> {
  const owned = await sql.query<{ owner_id: string | null }>(`select owner_id from nodes where id = $1`, [nodeId]);
  if (owned[0]?.owner_id && owned[0].owner_id === userId) return "owner";
  const staff = await sql.query<{ role: string }>(
    `select role from node_staff where node_id = $1 and user_id = $2 limit 1`,
    [nodeId, userId],
  );
  const role = staff[0]?.role;
  if (role === "admin" || role === "mod" || role === "member") return role;
  return null;
}

export async function assertMinRole(
  sql: Sql,
  nodeId: string,
  userId: string,
  min: StaffRole,
): Promise<StaffRole | "owner"> {
  const role = await roleOnNode(sql, nodeId, userId);
  if (!role) throw new Error("Pas les droits sur cet univers");
  if (role === "owner") return role;
  if ((RANK[role] ?? 0) < (RANK[min] ?? 0)) throw new Error("Rôle insuffisant");
  return role;
}
