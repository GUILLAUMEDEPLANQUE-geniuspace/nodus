import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function initials(title: string) {
  const parts = title
    .replace(/['’]/g, "")
    .split(/\s+/)
    .filter((p) => p && !/^(de|du|des|la|le|les|the|of|and|et)$/i.test(p));
  const a = parts[0]?.[0] ?? "?";
  const b = parts.length > 1 ? (parts[parts.length - 1]?.[0] ?? "") : (parts[0]?.[1] ?? "");
  return (a + b).toUpperCase();
}
