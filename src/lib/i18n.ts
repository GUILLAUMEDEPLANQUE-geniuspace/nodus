/**
 * i18n fr / en / ja — Asie–Occident.
 * Pas de lib tierce : dictionnaire court, locale sur profil + localStorage.
 * Les fiches Node restent dans la langue de l'auteur (SEO). Ici = chrome UI.
 */
export type Locale = "fr" | "en" | "ja";

const DICT: Record<Locale, Record<string, string>> = {
  fr: {
    explore: "Explorer",
    create: "Créer",
    profile: "Profil",
    inbox: "Messages",
    cart: "Panier",
    enter: "Entrer",
    search: "Chercher",
    notify: "Notifications",
  },
  en: {
    explore: "Explore",
    create: "Create",
    profile: "Profile",
    inbox: "Inbox",
    cart: "Cart",
    enter: "Enter",
    search: "Search",
    notify: "Notifications",
  },
  ja: {
    explore: "探索",
    create: "作成",
    profile: "プロフィール",
    inbox: "メッセージ",
    cart: "カート",
    enter: "入る",
    search: "検索",
    notify: "通知",
  },
};

const KEY = "nodus-locale";

export function readLocale(): Locale {
  try {
    const v = localStorage.getItem(KEY);
    if (v === "en" || v === "ja" || v === "fr") return v;
  } catch {
    /* ssr */
  }
  return "fr";
}

export function writeLocale(l: Locale) {
  try {
    localStorage.setItem(KEY, l);
  } catch {
    /* private */
  }
}

export function t(locale: Locale, key: string) {
  return DICT[locale][key] ?? DICT.fr[key] ?? key;
}
