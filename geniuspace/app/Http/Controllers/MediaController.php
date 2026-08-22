<?php

namespace App\Http\Controllers;

use App\Support\SignedMedia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sert le fichier si le HMAC est bon. Prod : rediriger vers R2 temporaryUrl.
 * Jamais de lien /storage/xxx en public pour un média gated.
 */
class MediaController extends Controller
{
    public function play(Request $request): BinaryFileResponse
    {
        $path = base64_decode((string) $request->query('path'), true) ?: '';
        $exp = (int) $request->query('exp');
        $sig = (string) $request->query('sig');
        abort_unless($path && SignedMedia::valid($path, $exp, $sig), 403, 'Jeton expiré');
        $full = public_path($path);
        if (! is_file($full)) {
            $full = storage_path('app/'.$path);
        }
        abort_unless(is_file($full), 404);
        return response()->file($full, [
            'Content-Type' => 'video/mp4',
            'Cache-Control' => 'private, max-age=60',
        ]);
    }
}
