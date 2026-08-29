<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Str;

/**
 * Ingest → propositions. applied = false. Aucune écriture de nœud.
 */
class EntityProposer
{
    /**
     * @return list<array{title:string,slug:string,entity:string,status:string,applied:bool}>
     */
    public static function fromText(GpNode $world, string $text): array
    {
        $out = [];
        $seen = [];
        foreach (preg_split("/\r\n|\n|\r/", $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $title = null;
            $entity = 'personnage';
            if (preg_match('/^#{1,3}\s+(.+)$/u', $line, $m)) {
                $title = trim($m[1]);
            } elseif (preg_match('/^(fiche|perso|personnage|lieu|village|clan|orga|jutsu|technique|arc)\s*[:\-\x{2013}]\s*(.+)$/iu', $line, $m)) {
                $title = trim($m[2]);
                $entity = self::guess($m[1]);
            }
            if (! $title || mb_strlen($title) < 2 || mb_strlen($title) > 80) {
                continue;
            }
            $slug = Str::slug($title);
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $out[] = [
                'title' => $title,
                'slug' => $slug,
                'entity' => $entity,
                'status' => 'draft',
                'applied' => false,
            ];
            if (count($out) >= 24) {
                break;
            }
        }

        return $out;
    }

    public static function guess(string $hint): string
    {
        $h = mb_strtolower($hint);

        return match (true) {
            str_contains($h, 'lieu') || str_contains($h, 'village') => 'lieu',
            str_contains($h, 'clan') || str_contains($h, 'orga') => 'organisation',
            str_contains($h, 'jutsu') || str_contains($h, 'technique') => 'jutsu',
            str_contains($h, 'arc') => 'arc',
            default => 'personnage',
        };
    }
}
