<?php

namespace App\Support;

use App\Models\DriveFile;
use App\Models\Media;

/**
 * URL d'un média.
 *
 * Fichier libre : /media/… (public)
 * Fichier mérité / teaser : jeton HMAC 15 min, fichier dans storage/app/private
 * Jamais de lien public vers un MP4 gated.
 */
class SignedMedia
{
    public static function url(?Media $media, bool $preview = false): string
    {
        if (! $media) {
            return '/media/teaser.mp4';
        }
        $path = ltrim($media->path, '/');
        $gated = Grantor::isGated($media);
        if (! $gated && ! str_starts_with($path, 'private/')) {
            return '/'.$path;
        }
        if ($preview && $gated && ! Grantor::canSeeMedia($media)) {
            return self::sign($path, 900, true, 'media:'.$media->id);
        }
        if ($gated && ! Grantor::canSeeMedia($media) && ! $preview) {
            return self::sign($path, 900, true, 'media:'.$media->id);
        }

        return self::sign($path, 900, false, 'media:'.$media->id);
    }

    public static function sign(string $path, int $ttl = 900, bool $preview = false, string $ref = ''): string
    {
        $exp = time() + $ttl;
        $flag = $preview ? 'p' : 'f';
        $sig = hash_hmac('sha256', $path.'|'.$exp.'|'.$flag.'|'.$ref, (string) config('app.key'));

        return route('media.play', [
            'path' => base64_encode($path),
            'exp' => $exp,
            'sig' => $sig,
            'preview' => $preview ? 1 : 0,
            'ref' => $ref,
        ], false);
    }

    public static function valid(string $path, int $exp, string $sig, bool $preview = false, string $ref = ''): bool
    {
        if ($exp < time()) {
            return false;
        }
        $flag = $preview ? 'p' : 'f';
        $expect = hash_hmac('sha256', $path.'|'.$exp.'|'.$flag.'|'.$ref, (string) config('app.key'));

        return hash_equals($expect, $sig);
    }

    public static function fullPath(string $path): ?string
    {
        $path = ltrim($path, '/');
        foreach ([
            storage_path('app/'.$path),
            storage_path('app/private/'.$path),
            public_path($path),
        ] as $full) {
            if (is_file($full)) {
                return $full;
            }
        }

        return null;
    }

    public static function mime(string $full): string
    {
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        return match ($ext) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            default => 'application/octet-stream',
        };
    }

    public static function storeUpload(\Illuminate\Http\UploadedFile $file, bool $private = false): string
    {
        $dirRel = $private ? 'private/media' : config('media.dir', 'media');
        $dir = $private ? storage_path('app/'.$dirRel) : public_path($dirRel);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = now()->format('Ymd-His').'-'.preg_replace('/[^a-zA-Z0-9._-]/', '-', $file->getClientOriginalName());
        $file->move($dir, $name);

        return $dirRel.'/'.$name;
    }

    /** Copie un fichier public dans le coffre privé (seed / upload locké). */
    public static function vaultFromPublic(string $publicRel, string $name): string
    {
        $src = public_path(ltrim($publicRel, '/'));
        $dir = storage_path('app/private/media');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir.'/'.$name;
        if (is_file($src) && ! is_file($dest)) {
            copy($src, $dest);
        }

        return 'private/media/'.$name;
    }

    public static function thumb(string $srcPath, string $kind = 'image'): string
    {
        if ($kind !== 'image' || ! function_exists('imagecreatefromstring')) {
            return '';
        }
        $full = self::fullPath($srcPath) ?: public_path(ltrim($srcPath, '/'));
        if (! is_file($full)) {
            return '';
        }
        $bin = @file_get_contents($full);
        if ($bin === false) {
            return '';
        }
        $im = @imagecreatefromstring($bin);
        if (! $im) {
            return '';
        }
        $w = imagesx($im);
        $h = imagesy($im);
        $nw = 480;
        $nh = max(1, (int) round($h * $nw / max(1, $w)));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $dir = public_path('media/thumbs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 't-'.substr(md5($srcPath), 0, 10).'.jpg';
        imagejpeg($dst, $dir.'/'.$name, 82);
        imagedestroy($im);
        imagedestroy($dst);

        return '/media/thumbs/'.$name;
    }
}
