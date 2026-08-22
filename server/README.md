# `server/` — chrome Grok, pas Laravel

Ces fichiers **n’appartiennent pas** à Geniuspace (stack : `geniuspace/`, Laravel + Blade).

| Fichier | Rôle |
| --- | --- |
| `middleware/grok-pwa.ts` | Injecte le PWA / OG / pill « Created with Grok » sur un host Vite/Nitro. |
| `virtual-grok-og-identity.d.ts` | Types du module virtuel OG Grok. |

Ils sont restés dans le commit flagships par accident de sandbox. **Ne pas les importer, ne pas les router dans Laravel.** Le produit se lance avec `cd geniuspace && php artisan serve`.

Si tu clones pour développer Geniuspace : tu peux ignorer tout le dossier `server/`.
