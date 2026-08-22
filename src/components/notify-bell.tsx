/** Cloche notifs. Marquer lu. Pas de websocket : poll à l'ouverture. */
import { Bell } from "lucide-react";
import { useEffect, useState } from "react";
import { listNotifications, markNotifRead } from "@/lib/platform-api";
import type { Notification } from "@/lib/platform";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

export function NotifyBell() {
  const { user } = useCurrentUserState();
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState<Notification[]>([]);

  useEffect(() => {
    if (!user || !open) return;
    void listNotifications()
      .then(setItems)
      .catch(() => setItems([]));
  }, [user, open]);

  const unread = items.filter((n) => !n.read).length;

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="grid size-11 place-items-center rounded-full text-muted hover:text-fg"
        aria-label="Notifications"
      >
        <Bell className="size-4" />
        {unread > 0 ? <span className="absolute top-1 right-1 size-2 rounded-full bg-primary" /> : null}
      </button>
      {open ? (
        <ul className="absolute right-0 z-50 mt-2 max-h-80 w-80 overflow-y-auto rounded-2xl border border-border bg-bg p-2">
          {items.length ? (
            items.map((n) => (
              <li key={n.id}>
                <a
                  href={n.href}
                  onClick={() => void markNotifRead({ data: { id: n.id } })}
                  className="block rounded-xl p-2 text-sm hover:bg-surface"
                >
                  <p className="text-primary">{n.title}</p>
                  <p className="text-xs text-muted">{n.body}</p>
                </a>
              </li>
            ))
          ) : (
            <li className="p-3 text-sm text-muted">Aucune notif</li>
          )}
        </ul>
      ) : null}
    </div>
  );
}