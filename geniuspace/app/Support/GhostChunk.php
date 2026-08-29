<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Preuves découpées. 600 / 150. ID stable.
 * Texte du pack seulement. Pas d’OCR, pas de PDF, pas de crawl.
 */
class GhostChunk
{
    public const SIZE = 600;

    public const OVERLAP = 150;

    public const MAX_BYTES = 400000;

    /** @var list<string> */
    public const ALLOWED_EXT = ['txt', 'md', 'csv', 'json', 'html', 'htm'];

    /**
     * @return list<array{id:string, text:string, asset_id:string}>
     */
    public static function split(string $text, string $assetId): array
    {
        $clean = str_replace("\r\n", "\n", $text);
        $out = [];
        $start = 0;
        $len = mb_strlen($clean);
        while ($start < $len) {
            $end = min($start + self::SIZE, $len);
            $slice = mb_substr($clean, $start, $end - $start);
            $cut = $end;
            $period = mb_strrpos($slice, '.');
            $space = mb_strrpos($slice, ' ');
            if ($period !== false && $period > self::SIZE * 0.5) {
                $cut = $start + $period + 1;
            } elseif ($space !== false && $space > self::SIZE * 0.5) {
                $cut = $start + $space;
            }
            $final = trim(mb_substr($clean, $start, $cut - $start));
            if (mb_strlen($final) > 10) {
                $id = 'chk_'.substr(sha1($assetId.'|'.$final), 0, 16);
                $out[] = ['id' => $id, 'text' => $final, 'asset_id' => $assetId];
            }
            $start = max($start + 1, $cut - self::OVERLAP);
        }

        return $out;
    }

    /**
     * @return list<array{id:string, text:string}>
     */
    public static function ingest(GpNode $node, string $text, string $assetId, string $kind = 'note'): array
    {
        $chunks = self::split($text, $assetId);
        if (! Schema::hasTable('ghost_chunks')) {
            return $chunks;
        }
        foreach ($chunks as $c) {
            $id = 'chk_'.substr(sha1($node->id.'|'.$c['id']), 0, 16);
            $payload = [
                'node_id' => $node->id,
                'asset_id' => $assetId,
                'text' => $c['text'],
                'source_kind' => $kind,
                'importance' => 0.5,
            ];
            $exists = DB::table('ghost_chunks')->where('id', $id)->exists();
            if ($exists) {
                DB::table('ghost_chunks')->where('id', $id)->update($payload);
            } else {
                DB::table('ghost_chunks')->insert($payload + ['id' => $id, 'created_at' => now()]);
            }
        }

        return $chunks;
    }

    /**
     * Fichier du pack. Refuse image / PDF (pas d’OCR). N’écrit pas Engine / grant.
     *
     * @return array{ok:bool, reason:string, chunks:list<array>, applied:false, asset_id?:string}
     */
    public static function fromUpload(GpNode $node, UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $mime = strtolower((string) $file->getMimeType());
        $blocked = str_contains($mime, 'pdf')
            || str_starts_with($mime, 'image/')
            || in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'doc', 'docx'], true);
        if ($blocked || ! in_array($ext, self::ALLOWED_EXT, true)) {
            return [
                'ok' => false,
                'reason' => 'Pas d’OCR, pas de PDF. Dépose un .txt ou .md du pack de ce lieu.',
                'chunks' => [],
                'applied' => false,
            ];
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return [
                'ok' => false,
                'reason' => 'Fichier trop lourd pour un extrait de coffre.',
                'chunks' => [],
                'applied' => false,
            ];
        }
        $path = $file->getRealPath();
        $raw = $path ? (string) file_get_contents($path) : '';
        if ($raw !== '' && ! mb_check_encoding($raw, 'UTF-8')) {
            $raw = (string) mb_convert_encoding($raw, 'UTF-8', 'UTF-8,ISO-8859-1,Windows-1252');
        }
        $text = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (mb_strlen($text) < 20) {
            return [
                'ok' => false,
                'reason' => 'Pas assez de texte tenu dans ce fichier.',
                'chunks' => [],
                'applied' => false,
            ];
        }
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'pack';
        $asset = 'pack-'.$base.'-'.substr(sha1($text), 0, 8);
        $chunks = self::ingest($node, Str::limit($text, 20000, ''), $asset, 'pack');

        return [
            'ok' => true,
            'reason' => '',
            'chunks' => $chunks,
            'applied' => false,
            'asset_id' => $asset,
        ];
    }
}
