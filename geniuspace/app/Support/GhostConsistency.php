<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * SUPPORT / CONTRADICTION / NEUTRAL / NEW.
 * Jaccard + négation. Pas un T5, pas un LLM.
 */
class GhostConsistency
{
    public const SUPPORT = 'SUPPORT';

    public const CONTRADICTION = 'CONTRADICTION';

    public const NEUTRAL = 'NEUTRAL';

    public const NEW = 'NEW';

    /**
     * @return array{status:string, related:?string, snippet:?string, confidence:float, overlap:float}
     */
    public static function check(GpNode $node, string $text): array
    {
        if (mb_strlen(trim($text)) < 12) {
            return ['status' => self::NEW, 'related' => null, 'snippet' => null, 'confidence' => 1, 'overlap' => 0];
        }
        $hits = GhostCortex::search($node, $text, 3);
        if ($hits === [] || ($hits[0]['score'] ?? 0) < 0.4) {
            return ['status' => self::NEW, 'related' => null, 'snippet' => null, 'confidence' => 0.8, 'overlap' => 0];
        }
        $top = $hits[0];
        $overlap = self::jaccard($text, (string) $top['text']);
        $flip = self::negation($text) !== self::negation((string) $top['text']);
        if ($flip && $overlap >= 0.25) {
            $status = self::CONTRADICTION;
            $conf = 0.8;
            GhostSynapse::reinforce((string) $node->id, 'mem:new', (string) $top['id'], 'CONTRADICTION', 0.2);
            GhostBelief::observe($node, \Illuminate\Support\Str::limit($text, 180), 'contradict', $top['layer']);
        } elseif ($overlap >= 0.5 && ! $flip) {
            $status = self::SUPPORT;
            $conf = 0.9;
            GhostSynapse::reinforce((string) $node->id, 'mem:new', (string) $top['id'], 'SUPPORT', 0.2);
            GhostBelief::observe($node, \Illuminate\Support\Str::limit($text, 180), 'support', $top['layer']);
        } elseif ($overlap < 0.15) {
            $status = self::NEW;
            $conf = 0.75;
        } else {
            $status = self::NEUTRAL;
            $conf = 0.5;
        }

        return [
            'status' => $status,
            'related' => $top['id'],
            'snippet' => \Illuminate\Support\Str::limit((string) $top['text'], 80),
            'confidence' => $conf,
            'overlap' => round($overlap, 4),
        ];
    }

    public static function jaccard(string $a, string $b): float
    {
        $A = array_unique(GhostCortex::tokens($a));
        $B = array_unique(GhostCortex::tokens($b));
        if ($A === [] && $B === []) {
            return 0;
        }
        $inter = count(array_intersect($A, $B));
        $union = count(array_unique(array_merge($A, $B)));

        return $union ? $inter / $union : 0;
    }

    public static function negation(string $text): bool
    {
        return (bool) preg_match('/\b(pas|jamais|aucun|aucune|faux|contraire|sans|plus)\b/u', mb_strtolower($text));
    }
}
