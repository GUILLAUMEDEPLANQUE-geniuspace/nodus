<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Preuves découpées. 600 / 150. ID stable. Pas d’OCR, pas de PDF.
 */
class GhostChunk
{
    public const SIZE = 600;

    public const OVERLAP = 150;

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
}
