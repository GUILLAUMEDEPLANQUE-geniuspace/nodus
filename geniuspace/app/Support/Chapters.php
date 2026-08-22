<?php

namespace App\Support;

use App\Models\GpNode;

/** Parse "00:12 Titre @slug" → Clip + porte vers un lieu. */
class Chapters
{
    public static function parse(?string $raw): array
    {
        $out = [];
        foreach (preg_split('/\n+/', $raw ?? '') ?: [] as $line) {
            $line = trim($line);
            if (! preg_match('/^(\d{1,2}:)?(\d{1,2}):(\d{2})\s+[—\-–]?\s*(.+)$/u', $line, $m)) {
                continue;
            }
            $h = $m[1] ? (int) $m[1] : 0;
            $min = (int) $m[2];
            $sec = (int) $m[3];
            $name = trim($m[4], " \t-—");
            $slug = '';
            if (preg_match('/@([\w\-]+)\s*$/u', $name, $s)) {
                $slug = $s[1];
                $name = trim(preg_replace('/\s*@[\w\-]+\s*$/u', '', $name));
            }
            $href = '';
            if ($slug !== '') {
                $n = GpNode::query()->where('slug', $slug)->orWhere('id', $slug)->first();
                $href = $n ? Engine::href($n) : '/n/'.$slug;
            }
            $out[] = [
                'name' => $name,
                'startOffset' => $h * 3600 + $min * 60 + $sec,
                'label' => $line,
                'slug' => $slug,
                'href' => $href,
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
