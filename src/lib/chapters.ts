/**
 * Parsing chapitres JoomCCK-grade (mm:ss, hh:mm:ss, 1h25, 21s).
 * Utilisé par la fiche vidéo ET le JSON-LD Clip / hasPart.
 */
export type Chapter = { t: string; title: string; sec: number };

export function convertTimeToSeconds(time: string): number {
  const s = time.trim().toLowerCase().replace(",", ".");
  if (!s) return 0;
  if (/^\d{3,6}$/.test(s)) {
    if (s.length <= 4) return Number(s.slice(0, -2)) * 60 + Number(s.slice(-2));
    return Number(s.slice(0, -4)) * 3600 + Number(s.slice(-4, -2)) * 60 + Number(s.slice(-2));
  }
  if (s.includes(":")) {
    const p = s.split(":").map(Number);
    if (p.length === 3) return p[0] * 3600 + p[1] * 60 + p[2];
    if (p.length === 2) return p[0] * 60 + p[1];
  }
  const re = /(\d+(?:\.\d+)?)\s*(h|hr|min|mn|m|s|sec)\b/g;
  let total = 0;
  let m: RegExpExecArray | null;
  let hit = false;
  while ((m = re.exec(s))) {
    hit = true;
    const n = Number(m[1]);
    const u = m[2];
    total += u.startsWith("h") ? n * 3600 : u.startsWith("s") ? n : n * 60;
  }
  if (hit) return Math.round(total);
  if (/^\d+(?:\.\d+)?$/.test(s)) return Math.round(Number(s));
  return 0;
}

export function parseChapters(raw: string): Chapter[] {
  const seen = new Set<number>();
  const out: Chapter[] = [];
  for (const line of raw.split(/\r?\n/)) {
    const trimmed = line.trim();
    if (!trimmed) continue;
    const m = trimmed.match(
      /(?<time>(?:\d+(?:\.\d+)?\s*(?:h|hr|min|mn|m|s|sec))|(?:\d{1,2}:)?\d{1,2}:\d{2}|\d{3,6})/i,
    );
    if (!m?.groups?.time) continue;
    const t = m.groups.time;
    const sec = convertTimeToSeconds(t);
    if (seen.has(sec)) continue;
    seen.add(sec);
    const title = trimmed.replace(t, "").replace(/^\s*[-–—]\s*/, "").trim() || trimmed;
    out.push({ t, title, sec });
  }
  return out.sort((a, b) => a.sec - b.sec);
}

export function videoObjectLd(media: {
  title: string;
  transcript: string;
  duration: string;
  genre: string;
  url?: string;
}, chapters: Chapter[], pageUrl: string) {
  return {
    "@context": "https://schema.org",
    "@type": "VideoObject",
    name: media.title,
    description: media.transcript,
    duration: media.duration,
    genre: media.genre,
    url: pageUrl,
    transcript: media.transcript,
    hasPart: chapters.map((c) => ({
      "@type": "Clip",
      name: c.title,
      startOffset: c.sec,
      url: `${pageUrl}#t=${c.sec}`,
    })),
  };
}
