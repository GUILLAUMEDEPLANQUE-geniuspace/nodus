<?php

namespace App\Support;

/** Parse "00:12 Titre" → Clip schema.org */
class Chapters
{
    public static function parse(?string $raw): array
    {
        $out = [];
        foreach (preg_split('/\n+/', $raw ?? '') ?: [] as $line) {
            $line = trim($line);
            if (! preg_match('/^(\d{1,2}:)?(\d{1,2}):(\d{2})\s+(.+)$/u', $line, $m)) {
                continue;
            }
            $h = $m[1] ? (int) $m[1] : 0;
            $min = (int) $m[2];
            $sec = (int) $m[3];
            $out[] = [
                'name' => $m[4],
                'startOffset' => $h * 3600 + $min * 60 + $sec,
                'label' => $line,
            ];
        }
        return $out;
    }

    public static function clips(array $chaps, string $url): array
    {
        return array_map(fn ($c) => [
            '@type' => 'Clip',
            'name' => $c['name'],
            'startOffset' => $c['startOffset'],
            'url' => $url.'#t='.$c['startOffset'],
        ], $chaps);
    }
}
