<?php

namespace App\Support;

/**
 * Jetons de lecture. Prod : même contrat vers R2 (temporaryUrl).
 * Le MP4 n'est jamais une URL devinable si access=freemium.
 */
class SignedMedia
{
    public static function sign(string $path, int $ttl = 900): string
    {
        $exp = time() + $ttl;
        $sig = hash_hmac('sha256', $path.'|'.$exp, (string) config('app.key'));
        return route('media.play', ['path' => base64_encode($path), 'exp' => $exp, 'sig' => $sig]);
    }

    public static function valid(string $path, int $exp, string $sig): bool
    {
        if ($exp < time()) {
            return false;
        }
        $expect = hash_hmac('sha256', $path.'|'.$exp, (string) config('app.key'));
        return hash_equals($expect, $sig);
    }
}
