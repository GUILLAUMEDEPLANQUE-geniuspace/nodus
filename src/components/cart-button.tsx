/** Panier persistant. Checkout démo (carte / crypto) → order paid + grants VOD. */
import { ShoppingBag } from "lucide-react";
import { useEffect, useState } from "react";
import { addToCart, checkoutCart, getCart } from "@/lib/platform-api";
import type { CartLine } from "@/lib/platform";
import { useCurrentUserState } from "@/lib/auth/use-current-user";

export function CartButton() {
  const { user } = useCurrentUserState();
  const [open, setOpen] = useState(false);
  const [lines, setLines] = useState<CartLine[]>([]);
  const [msg, setMsg] = useState("");

  async function refresh() {
    if (!user) return;
    try {
      setLines(await getCart());
    } catch {
      /* auth */
    }
  }

  useEffect(() => {
    void refresh();
  }, [user, open]);

  async function pay(provider: "card" | "crypto") {
    try {
      await checkoutCart({ data: { provider } });
      setLines([]);
      setMsg("Commande payée (démo). Les VOD liées sont débloquées.");
    } catch (e) {
      setMsg(e instanceof Error ? e.message : "Panier");
    }
  }

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="grid size-11 place-items-center rounded-full text-muted hover:text-fg"
        aria-label="Panier"
      >
        <ShoppingBag className="size-4" />
        {lines.length > 0 ? (
          <span className="absolute top-1 right-1 size-2 rounded-full bg-primary" />
        ) : null}
      </button>
      {open ? (
        <div className="absolute right-0 z-50 mt-2 w-72 rounded-2xl border border-border bg-bg p-3 shadow-[var(--shadow-border)]">
          {lines.length ? (
            <ul className="space-y-2 text-sm">
              {lines.map((l) => (
                <li key={l.id} className="flex justify-between">
                  <span>{l.title}</span>
                  <span className="text-primary">{l.price}</span>
                </li>
              ))}
            </ul>
          ) : (
            <p className="text-sm text-muted">Panier vide</p>
          )}
          {msg ? <p className="mt-2 text-xs text-primary">{msg}</p> : null}
          {lines.length > 0 ? (
            <div className="mt-3 flex gap-2">
              <button type="button" onClick={() => void pay("card")} className="h-10 flex-1 rounded-full bg-primary text-xs text-primary-fg">
                Carte
              </button>
              <button type="button" onClick={() => void pay("crypto")} className="h-10 flex-1 rounded-full border border-border text-xs">
                Crypto
              </button>
            </div>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

export async function buyProduct(productId: string) {
  await addToCart({ data: { productId } });
}
