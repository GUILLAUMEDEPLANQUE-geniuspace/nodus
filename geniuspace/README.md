# Geniuspace (Laravel)

**C’est ici que vit le produit.** Blade + CSS + Alpine. HTML serveur = SEO.

Le dépôt n’a plus d’app React / TanStack. Voir le [README racine](../README.md).

## Médias (souverain, pas YouTube)

- Démo : `storage/app/media/*.mp4` + jetons HMAC (`SignedMedia`).
- Prod : disque `r2` (Cloudflare R2). Les MP4 restent **hors** mutualisé o2switch.

```
composer install --no-dev
php artisan migrate --force
php artisan db:seed --force
php artisan test
```
