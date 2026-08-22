<?php

namespace App\Support;

/**
 * Player HLS — PAS avant le VPS / Bunny / R2.
 * Sur mutu o2switch : MP4 progressif via SignedMedia (déjà en place).
 * Quand BUNNY_LIBRARY ou R2_ENDPOINT est dans .env, on servira un .m3u8 signé.
 * Ne pas coder le bitrate maintenant : le SEO de la PAGE compte plus.
 */
class Hls
{
    public static function ready(): bool
    {
        return (bool) (env('BUNNY_LIBRARY') ?: env('R2_ENDPOINT'));
    }

    public static function src(string $path): string
    {
        if (! self::ready()) {
            return SignedMedia::url($path);
        }
        return rtrim((string) env('BUNNY_CDN', ''), '/').'/'.ltrim($path, '/').'/playlist.m3u8';
    }
}
