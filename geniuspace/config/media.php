<?php

/**
 * Où vivent les MP4.
 *
 * public  = public/media (o2switch mutu, VPS, dédié) — URL /media/fichier.mp4
 * signed  = même dossier, lecture via jeton HMAC (paywall)
 * r2      = Cloudflare R2 (option, pas obligatoire)
 */
return [
    'disk' => env('MEDIA_DISK', 'public'),
    'dir' => env('MEDIA_DIR', 'media'),
];
