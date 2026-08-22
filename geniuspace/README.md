# Geniuspace (Laravel)

Blade + Tailwind tokens (CSS maison) + Alpine. HTML serveur = SEO.

## Médias (souverain, pas YouTube)

- Démo : `storage/app/media/*.mp4` + jetons HMAC (`SignedMedia`).
- Prod : disque `r2` (Cloudflare R2, egress 0 €) — mêmes chemins.
- VPS (Hetzner/Scaleway/OVH) : monter le disque, même contrat.
- PeerTube / IPFS : plus tard, en source alternative du player — pas le cœur.

## o2switch

PHP 8.3 + SQLite (ou MySQL). Document root = `public/`. Les MP4 restent **hors** mutualisé (R2).

```
composer install --no-dev
php artisan migrate --force
php artisan db:seed --force
```

## Local sandbox

`php artisan serve --host=0.0.0.0 --port=8080`
