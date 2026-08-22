import { useEffect, useState } from "react";
import { readLocale, t, writeLocale, type Locale } from "@/lib/i18n";

export function LocaleSwitch() {
  const [locale, setLocale] = useState<Locale>("fr");
  useEffect(() => setLocale(readLocale()), []);
  return (
    <select
      value={locale}
      aria-label={t(locale, "explore")}
      onChange={(e) => {
        const next = e.target.value as Locale;
        setLocale(next);
        writeLocale(next);
        document.documentElement.lang = next;
      }}
      className="h-11 rounded-full border border-border bg-transparent px-2 text-xs text-muted"
    >
      <option value="fr">FR</option>
      <option value="en">EN</option>
      <option value="ja">JA</option>
    </select>
  );
}
