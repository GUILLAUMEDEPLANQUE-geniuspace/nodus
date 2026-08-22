<?php

namespace App\Support;

use App\Models\DriveFile;
use App\Models\GpNode;
use App\Models\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Coffre à preuves. Un média débloqué, un fichier mérité, une relique
 * tombée d’un chapitre : même table, même export, même carnet.
 * L’UI n’affiche jamais « grant » — seulement des preuves tenues.
 */
class Grantor
{
    public static function guestId(): string
    {
        if (! session()->has('gp_guest')) {
            session()->put('gp_guest', (string) Str::uuid());
        }

        return (string) session('gp_guest');
    }

    public static function has(string $type, string $id): bool
    {
        $q = DB::table('grants')->where('subject_type', $type)->where('subject_id', (string) $id);
        $uid = Auth::id();
        $sid = session('gp_guest');
        $q->where(function ($w) use ($uid, $sid) {
            if ($sid) {
                $w->where('session_id', $sid);
            }
            if ($uid) {
                $w->orWhere('user_id', $uid);
            }
        });

        return $q->exists();
    }

    public static function give(string $type, string $id, string $reason, string $nodeId, string $label = '', array $meta = []): void
    {
        if (self::has($type, $id)) {
            return;
        }
        $sid = self::guestId();
        $uid = Auth::id();
        DB::table('grants')->insert([
            'user_id' => $uid,
            'session_id' => $sid,
            'node_id' => $nodeId,
            'subject_type' => $type,
            'subject_id' => (string) $id,
            'reason' => $reason,
            'label' => $label,
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        if ($uid && in_array($type, ['relic', 'proof'], true)) {
            DB::table('inventory')->insert([
                'user_id' => $uid,
                'kind' => $type === 'proof' ? 'proof' : 'relic',
                'node_id' => $nodeId,
                'label' => $label ?: $type,
                'meta' => (string) $id,
            ]);
        }
    }

    public static function isGated(?Media $media): bool
    {
        return $media && ($media->access ?? 'free') !== 'free';
    }

    public static function canSeeMedia(?Media $media): bool
    {
        if (! $media) {
            return false;
        }
        if (! self::isGated($media)) {
            return true;
        }
        if (Acl::atLeast($media->node_id, 'mod')) {
            return true;
        }

        return self::has('media', (string) $media->id);
    }

    public static function canSeeFile(?DriveFile $file): bool
    {
        if (! $file) {
            return false;
        }
        if (! $file->locked) {
            return true;
        }
        if (Acl::atLeast($file->node_id, 'mod')) {
            return true;
        }
        if (self::has('file', (string) $file->id)) {
            return true;
        }
        $kind = (string) ($file->lock_kind ?? '');
        $ref = (string) ($file->lock_ref ?? '');
        if ($kind === 'purchase' && $ref !== '' && self::has('product', $ref)) {
            return true;
        }
        if (in_array($kind, ['quest', 'ats'], true) && self::has('proof', $file->node_id)) {
            return true;
        }

        return false;
    }

    public static function unlockMedia(Media $media, string $reason = 'teaser'): array
    {
        $reason = $reason ?: self::reasonFor($media);
        self::give('media', (string) $media->id, $reason, $media->node_id, $media->title, ['mode' => $media->mode]);

        $node = GpNode::query()->find($media->node_id);
        $plain = match ($reason) {
            'quest', 'ats' => 'Étape tenue. La preuve voyage avec vous.',
            'purchase' => 'Œuvre acquise. Le certificat s’ouvre.',
            'drop' => 'Relique posée dans le coffre.',
            default => 'Suite ouverte. Le lieu se souvient.',
        };

        if (in_array($reason, ['quest', 'ats', 'teaser'], true) && in_array($media->mode, ['interview', 'quest'], true)) {
            self::give('proof', $media->node_id, 'quest', $media->node_id, $media->title, ['media_id' => $media->id]);
            self::unlockFiles($media->node_id, ['quest', 'ats']);
        }
        if ($reason === 'purchase') {
            self::unlockFiles($media->node_id, ['purchase']);
        }

        return [
            'ok' => true,
            'granted' => true,
            'src' => SignedMedia::url($media, false),
            'preuve' => [
                'titre' => $media->title,
                'maison' => $node->title ?? '',
                'quoi' => $plain,
                'href' => $node ? Engine::href($node) : '/',
            ],
        ];
    }

    public static function drop(Media $media, ?object $door = null): array
    {
        $label = $door->label ?? $door->relic_title ?? ('Relique · '.$media->title);
        $path = $door->relic_path ?? '';
        $nodeId = $media->node_id;
        $relicId = 'drop-'.$media->id.'-'.(int) ($door->at_sec ?? 0);
        self::give('relic', $relicId, 'drop', $nodeId, $label, [
            'media_id' => $media->id,
            'at' => $door->at_sec ?? 0,
            'path' => $path,
        ]);
        if ($path === '') {
            $file = DriveFile::query()->where('node_id', $nodeId)->where('lock_kind', 'drop')->first();
        } else {
            $file = DriveFile::query()->where('node_id', $nodeId)->where(function ($q) use ($path, $label) {
                $q->where('path', $path)->orWhere('path', '/'.ltrim($path, '/'))->orWhere('title', $label);
            })->first();
        }
        if ($file) {
            self::give('file', (string) $file->id, 'drop', $nodeId, $file->title);
        } else {
            self::unlockFiles($nodeId, ['drop']);
        }

        return [
            'ok' => true,
            'label' => $label,
            'quoi' => 'Posé dans le coffre. Ça voyage avec le carnet.',
            'href' => '/drive?slug='.(($node = GpNode::query()->find($nodeId))?->slug ?? ''),
        ];
    }

    public static function grantProduct(string $productId, string $nodeId, string $title): void
    {
        self::give('product', $productId, 'purchase', $nodeId, $title);
        $files = DriveFile::query()->where('node_id', $nodeId)->where('lock_kind', 'purchase')->where('lock_ref', $productId)->get();
        foreach ($files as $f) {
            self::give('file', (string) $f->id, 'purchase', $nodeId, $f->title);
        }
        $media = Media::query()->where('node_id', $nodeId)->where('access', '!=', 'free')->get();
        foreach ($media as $m) {
            self::give('media', (string) $m->id, 'purchase', $nodeId, $m->title);
        }
    }

    public static function unlockFiles(string $nodeId, array $kinds): void
    {
        $files = DriveFile::query()->where('node_id', $nodeId)->whereIn('lock_kind', $kinds)->get();
        foreach ($files as $f) {
            self::give('file', (string) $f->id, $f->lock_kind ?: 'teaser', $nodeId, $f->title);
        }
    }

    public static function reasonFor(Media $media): string
    {
        return match ($media->mode) {
            'interview', 'quest' => 'quest',
            'shop' => 'teaser',
            default => 'teaser',
        };
    }

    /** Portes d’un média : chapitres @slug + drops seedés. Jamais « edge ». */
    public static function doors(Media $media): array
    {
        $out = [];
        foreach (Chapters::parse($media->chapters) as $c) {
            $href = $c['href'] ?? '';
            if ($href === '' && empty($c['slug'])) {
                continue;
            }
            if ($href === '' && ! empty($c['slug'])) {
                $n = GpNode::query()->where('slug', $c['slug'])->orWhere('id', $c['slug'])->first();
                $href = $n ? Engine::href($n) : '';
            }
            if ($href === '') {
                continue;
            }
            $out[] = [
                'at' => (int) $c['startOffset'],
                'kind' => 'door',
                'label' => preg_replace('/\s*@[\w\-]+\s*$/u', '', $c['name']),
                'href' => $href,
            ];
        }
        if (! \Illuminate\Support\Facades\Schema::hasTable('media_doors')) {
            return $out;
        }
        foreach (DB::table('media_doors')->where('media_id', $media->id)->orderBy('at_sec')->get() as $d) {
            $href = '';
            if ($d->target_slug) {
                $n = GpNode::query()->where('slug', $d->target_slug)->orWhere('id', $d->target_slug)->first();
                $href = $n ? Engine::href($n) : '/n/'.$d->target_slug;
            }
            $out[] = [
                'at' => (int) $d->at_sec,
                'kind' => $d->kind,
                'label' => $d->label,
                'href' => $href,
                'relic_title' => $d->relic_title,
                'relic_path' => $d->relic_path,
            ];
        }
        usort($out, fn ($a, $b) => $a['at'] <=> $b['at']);

        return $out;
    }

    /** Preuves tenues par le visiteur actuel. */
    public static function mine(?string $nodeId = null): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('grants')) {
            return [];
        }
        $uid = Auth::id();
        $sid = session('gp_guest');
        if (! $uid && ! $sid) {
            return [];
        }
        $q = DB::table('grants')->orderByDesc('id');
        $q->where(function ($w) use ($uid, $sid) {
            if ($sid) {
                $w->where('session_id', $sid);
            }
            if ($uid) {
                $w->orWhere('user_id', $uid);
            }
        });
        if ($nodeId) {
            $q->where('node_id', $nodeId);
        }
        $rows = $q->get();
        $preuves = [];
        foreach ($rows as $r) {
            $node = $r->node_id ? GpNode::query()->find($r->node_id) : null;
            $quoi = match ($r->subject_type) {
                'media' => 'Vidéo ouverte',
                'file' => 'Fichier mérité',
                'relic' => 'Relique',
                'product' => 'Œuvre acquise',
                'proof' => 'Étape tenue',
                default => 'Preuve',
            };
            $href = $node ? Engine::href($node) : '/';
            if ($r->subject_type === 'media') {
                $m = Media::query()->find($r->subject_id);
                if ($m && $node) {
                    $href = '/n/'.$node->slug.'/v/'.$m->id;
                }
            }
            $preuves[] = [
                'titre' => $r->label ?: $quoi,
                'maison' => $node->title ?? '',
                'quoi' => $quoi,
                'href' => $href,
                'at' => $r->created_at,
            ];
        }

        return $preuves;
    }

    public static function export(?string $nodeId = null): array
    {
        return [
            'type' => 'GeniuspaceCarnet',
            'version' => '2026.2',
            'issued' => now()->toDateString(),
            'preuves' => self::mine($nodeId),
        ];
    }

    public static function fileHref(DriveFile $file): string
    {
        if (! $file->locked && $file->path && ! str_starts_with(ltrim($file->path, '/'), 'private/')) {
            return $file->path;
        }

        return SignedMedia::sign(ltrim($file->path, '/'), 900, false, 'file:'.$file->id);
    }
}
