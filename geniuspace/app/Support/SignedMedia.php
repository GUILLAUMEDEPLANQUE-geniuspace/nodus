<?php

namespace App\Support;

use App\Models\Media;

/**
 * URL d'un MP4.
 *
 * MEDIA_DISK=public (défaut, mutu / VPS / dédié) : /media/fichier.mp4
 * MEDIA_DISK=signed : jeton 15 min, même fichier local
 * MEDIA_DISK=r2 : à brancher plus tard
 */
class SignedMedia
{
    public static function url(?Media $media): string
    {
        if (! $media) {
            return '/media/teaser.mp4';
        }
        $disk = config('media.disk', 'public');
        $path = ltrim($media->path, '/');
        if ($disk === 'public' || ($disk !== 'signed' && $media->access === 'free')) {
            return '/'.$path;
        }
        return self::sign($path);
    }

    public static function sign(string $path, int $ttl = 900): string
    {
        $exp = time() + $ttl;
        $sig = hash_hmac('sha256', $path.'|'.$exp, (string) config('app.key'));
        return route('media.play', [
            'path' => base64_encode($path),
            'exp' => $exp,
            'sig' => $sig,
        ], false);
    }

    public static function valid(string $path, int $exp, string $sig): bool
    {
        if ($exp < time()) {
            return false;
        }
        $expect = hash_hmac('sha256', $path.'|'.$exp, (string) config('app.key'));
        return hash_equals($expect, $sig);
    }

    public static function storeUpload(\Illuminate\Http\UploadedFile $file): string
    {
        $dir = public_path(config('media.dir', 'media'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = now()->format('Ymd-His').'-'.preg_replace('/[^a-zA-Z0-9._-]/', '-', $file->getClientOriginalName());
        $file->move($dir, $name);
        return config('media.dir', 'media').'/'.$name;
    }
}
