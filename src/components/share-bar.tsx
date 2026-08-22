/**
 * Partage natif (Web Share) + copie d'URL + intent X.
 * Pas de pixels Meta. Canonical = moat SEO.
 */
import { Link2, Share2, Star } from "lucide-react";
import { useState } from "react";

export function ShareBar({
  title,
  path,
  text,
}: {
  title: string;
  path: string;
  text: string;
}) {
  const [copied, setCopied] = useState(false);
  const href = () => `${window.location.origin}${path}`;

  async function share() {
    const url = href();
    try {
      if (navigator.share) {
        await navigator.share({ title, text, url });
        return;
      }
    } catch {
      /* dismissed */
    }
    await navigator.clipboard.writeText(url);
    setCopied(true);
    window.setTimeout(() => setCopied(false), 1600);
  }

  async function copy() {
    await navigator.clipboard.writeText(href());
    setCopied(true);
    window.setTimeout(() => setCopied(false), 1600);
  }

  return (
    <div className="flex flex-wrap gap-2">
      <button
        type="button"
        onClick={() => void share()}
        className="inline-flex h-11 items-center gap-2 rounded-full border border-border px-3 text-sm"
      >
        <Share2 className="size-4 text-primary" />
        Partager
      </button>
      <button
        type="button"
        onClick={() => void copy()}
        className="inline-flex h-11 items-center gap-2 rounded-full border border-border px-3 text-sm"
      >
        <Link2 className="size-4 text-primary" />
        {copied ? "Lien copié" : "Copier le lien"}
      </button>
      <a
        href={`https://x.com/intent/tweet?text=${encodeURIComponent(`${title} — ${text}`)}&url=${encodeURIComponent(path)}`}
        target="_blank"
        rel="noreferrer"
        className="inline-flex h-11 items-center rounded-full border border-border px-3 text-sm text-muted"
      >
        X
      </a>
    </div>
  );
}

export function Stars({ rating, votes }: { rating: string; votes?: number }) {
  const n = Math.round(Number(rating) || 0);
  return (
    <p className="inline-flex items-center gap-1 text-sm">
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={i}
          className={`size-3.5 ${i < n ? "fill-primary text-primary" : "text-muted"}`}
        />
      ))}
      <span className="ml-1 text-muted">
        {rating}/5{votes != null ? ` · ${votes} avis` : ""}
      </span>
    </p>
  );
}
