# Geniuspace (Laravel)

**C’est ici que vit le produit.** Blade + CSS + Alpine. HTML serveur = SEO.

Le dépôt n’a plus d’app React / TanStack. Voir le [README racine](../README.md).

## Médias (souverain, pas YouTube)

- Libre : `public/media/*.mp4`
- Mérité : `storage/app/private/media` + jeton HMAC (`SignedMedia`) + table `grants`
- JSON-LD gated : `embedUrl` seulement, jamais le MP4 full
- Prod : disque `r2` (Cloudflare R2). HLS : `Hls.php` (pas avant VPS / Bunny)

```
composer install --no-dev
php artisan migrate --force
php artisan db:seed --force
php artisan test
```
