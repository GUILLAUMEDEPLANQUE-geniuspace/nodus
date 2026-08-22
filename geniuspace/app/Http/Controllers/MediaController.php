<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Models\Media;
use App\Support\Grantor;
use App\Support\SignedMedia;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sert le fichier si le HMAC est bon ET si le visiteur a le droit.
 * Preview (teaser) : jeton preview, pas de grant.
 * Full / fichier locké : grant, staff, ou fichier libre.
 */
class MediaController extends Controller
{
    public function play(Request $request): BinaryFileResponse
    {
        $path = base64_decode((string) $request->query('path'), true) ?: '';
        $exp = (int) $request->query('exp');
        $sig = (string) $request->query('sig');
        $preview = (int) $request->query('preview') === 1;
        $ref = (string) $request->query('ref', '');
        abort_unless($path && SignedMedia::valid($path, $exp, $sig, $preview, $ref), 403, 'Jeton expiré');

        $this->authorizeRef($path, $preview, $ref);

        $full = SignedMedia::fullPath($path);
        abort_unless($full, 404);

        return response()->file($full, [
            'Content-Type' => SignedMedia::mime($full),
            'Cache-Control' => 'private, max-age=60',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function authorizeRef(string $path, bool $preview, string $ref): void
    {
        if (str_starts_with($ref, 'media:')) {
            $media = Media::query()->find(substr($ref, 6));
            if (! $media) {
                return;
            }
            if (Grantor::canSeeMedia($media)) {
                return;
            }
            abort_unless($preview && Grantor::isGated($media), 403, 'Cette suite s’ouvre après l’étape.');

            return;
        }
        if (str_starts_with($ref, 'file:')) {
            $file = DriveFile::query()->find(substr($ref, 5));
            abort_unless($file && Grantor::canSeeFile($file), 403, 'Ce fichier se mérite.');

            return;
        }
        $media = Media::query()->where('path', $path)->orWhere('path', '/'.$path)->first();
        if ($media) {
            if (Grantor::canSeeMedia($media)) {
                return;
            }
            abort_unless($preview && Grantor::isGated($media), 403, 'Cette suite s’ouvre après l’étape.');

            return;
        }
        $file = DriveFile::query()->where('path', $path)->orWhere('path', '/'.$path)->orWhere('path', '/'.ltrim($path, '/'))->first();
        if ($file) {
            abort_unless(Grantor::canSeeFile($file), 403, 'Ce fichier se mérite.');
        }
    }
}
