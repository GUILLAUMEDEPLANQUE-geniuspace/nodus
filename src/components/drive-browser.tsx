import { useMemo, useState } from "react";
import { FileText, Film, Folder, Image as ImageIcon, Music } from "lucide-react";
import type { DriveFile, DriveFolder } from "@/lib/graph";
import { VideoFiche } from "@/components/video-fiche";
import { addDriveFile } from "@/lib/graph-api";

const KIND_ICON = {
  video: Film,
  image: ImageIcon,
  audio: Music,
  pdf: FileText,
  doc: FileText,
};

/** Drive maison : dossiers, versions, drop d'images/vidéos vers le Node. */
export function DriveBrowser({
  folders,
  files,
  slug,
}: {
  folders: DriveFolder[];
  files: DriveFile[];
  slug?: string;
}) {
  const [folderId, setFolderId] = useState<string | "all">("all");
  const [activeId, setActiveId] = useState<string | null>(files[0]?.id ?? null);
  const [extra, setExtra] = useState<DriveFile[]>([]);
  const allFiles = [...files, ...extra];

  const visible = useMemo(
    () => (folderId === "all" ? allFiles : allFiles.filter((f) => f.folderId === folderId)),
    [allFiles, folderId],
  );
  const active = allFiles.find((f) => f.id === activeId) ?? visible[0];

  async function onDrop(e: React.DragEvent) {
    e.preventDefault();
    if (!slug) return;
    const list = [...e.dataTransfer.files];
    for (const file of list) {
      const kind = file.type.startsWith("video")
        ? "video"
        : file.type.startsWith("audio")
          ? "audio"
          : file.type.startsWith("image")
            ? "image"
            : file.type.includes("pdf")
              ? "pdf"
              : "doc";
      try {
        const res = await addDriveFile({
          data: { slug, name: file.name, kind, folderId: folderId === "all" ? undefined : folderId },
        });
        setExtra((cur) => [
          ...cur,
          {
            id: res.id,
            nodeId: "",
            folderId: folderId === "all" ? null : folderId,
            name: file.name,
            kind,
            sizeLabel: `${Math.round(file.size / 1024)} Ko`,
            version: 1,
            summary: "Déposé depuis le gestionnaire",
            chapters: "",
            transcript: "",
          },
        ]);
      } catch {
        /* auth */
      }
    }
  }

  return (
    <div
      onDragOver={(e) => e.preventDefault()}
      onDrop={(e) => void onDrop(e)}
      className="rounded-3xl border border-dashed border-primary/20 p-1"
    >
      <p className="px-3 pt-2 text-xs text-muted">Glissez images, vidéos, PDF ici — le Drive du Node les versionne.</p>
      {folders.length === 0 && allFiles.length === 0 ? (
        <div className="rounded-2xl bg-surface p-8">
          <p className="font-display text-2xl">Drive vide</p>
          <p className="mt-2 text-sm text-muted">Déposez un premier fichier pour habiller l'univers.</p>
        </div>
      ) : (
        <div className="mt-2 grid gap-4 lg:grid-cols-[220px_1fr]">
          <aside className="rounded-2xl bg-surface p-3 shadow-[var(--shadow-border)]">
            <p className="px-2 pb-2 text-[11px] tracking-[0.16em] text-primary uppercase">Dossiers</p>
            <button
              type="button"
              onClick={() => setFolderId("all")}
              className={`flex h-11 w-full items-center gap-2 rounded-xl px-2 text-left text-sm ${
                folderId === "all" ? "bg-surface-2 text-primary" : "text-fg"
              }`}
            >
              <Folder className="size-4 text-primary" />
              Tous les fichiers
            </button>
            {folders.map((f) => (
              <button
                key={f.id}
                type="button"
                onClick={() => setFolderId(f.id)}
                className={`flex h-11 w-full items-center gap-2 rounded-xl px-2 text-left text-sm ${
                  folderId === f.id ? "bg-surface-2 text-primary" : "text-fg"
                }`}
              >
                <Folder className="size-4 text-primary" />
                {f.name}
              </button>
            ))}
          </aside>
          <div className="space-y-4">
            <ul className="divide-y divide-border overflow-hidden rounded-2xl bg-surface shadow-[var(--shadow-border)]">
              {visible.map((f) => {
                const Icon = KIND_ICON[f.kind as keyof typeof KIND_ICON] ?? FileText;
                return (
                  <li key={f.id}>
                    <button
                      type="button"
                      onClick={() => setActiveId(f.id)}
                      className={`flex w-full items-center gap-3 px-4 py-3 text-left ${
                        active?.id === f.id ? "bg-surface-2" : ""
                      }`}
                    >
                      <Icon className="size-4 shrink-0 text-primary" />
                      <span className="min-w-0 flex-1">
                        <span className="block truncate text-sm text-fg">{f.name}</span>
                        <span className="text-xs text-muted">
                          {f.kind} · v{f.version}
                          {f.sizeLabel ? ` · ${f.sizeLabel}` : ""}
                        </span>
                      </span>
                    </button>
                  </li>
                );
              })}
            </ul>
            {active ? (
              active.kind === "video" ? (
                <VideoFiche
                  media={{
                    id: 0,
                    kind: "video",
                    title: active.name,
                    url: "",
                    duration: active.sizeLabel,
                    genre: "Drive",
                    chapters: active.chapters,
                    transcript: active.transcript || active.summary,
                  }}
                />
              ) : (
                <article className="rounded-2xl bg-surface p-5 shadow-[var(--shadow-border)]">
                  <p className="text-[11px] tracking-[0.16em] text-primary uppercase">
                    {active.kind} · version {active.version}
                  </p>
                  <h3 className="mt-1 font-display text-2xl">{active.name}</h3>
                  <p className="mt-3 text-sm leading-relaxed text-muted">{active.summary || active.transcript}</p>
                </article>
              )
            ) : null}
          </div>
        </div>
      )}
    </div>
  );
}
