<?php

namespace App\Support;

/** Ouvrier : richesse sémantique d’un guide de bounty. */
class LlmJudge
{
    public static function run(string $keyword, string $body): array
    {
        $score = 0;
        $notes = [];
        $len = mb_strlen(trim($body));
        if ($len >= 400) {
            $score += 40;
            $notes[] = 'longueur ok';
        } else {
            $notes[] = 'trop court (<400)';
        }
        if (mb_stripos($body, $keyword) !== false) {
            $score += 30;
            $notes[] = 'mot-clé présent';
        } else {
            $notes[] = 'mot-clé absent';
        }
        if (preg_match('/^#|\*\*|## /m', $body)) {
            $score += 10;
            $notes[] = 'structure';
        }
        if (preg_match_all('/\b([A-Z][\p{L}]{3,}|\d{3}|GTI|Haki)\b/u', $body) >= 2) {
            $score += 20;
            $notes[] = 'maillage';
        }
        $score = min(100, $score);
        return ['score' => $score, 'ok' => $score >= 60, 'note' => implode(' · ', $notes)];
    }
}
