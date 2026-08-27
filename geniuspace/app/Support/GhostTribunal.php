<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * Réponse fondée, ou refus. Pas un LLM puis un filet.
 *
 * Preuves = world + evidence. Jamais belief.
 * Citation obligatoire. Claim chiffré hors preuve → refus.
 */
class GhostTribunal
{
    /**
     * @return array{ok:bool, answer:string, evidence:list<array>, evidence_used:list<int>, confidence:float, refusal:?string, layers:list<string>}
     */
    public static function answer(GpNode $node, string $question, int $max = 4): array
    {
        $hits = GhostCortex::search($node, $question, max($max, 4), [GhostCortex::WORLD, GhostCortex::EVIDENCE]);
        $hits = array_values(array_filter($hits, fn ($h) => $h['layer'] !== GhostCortex::BELIEF && ($h['lexical'] ?? 0) > 0));
        if ($hits === []) {
            return self::refuse($question, [], "Je n’ai pas cette preuve dans le coffre.");
        }
        $used = [];
        $lines = [];
        foreach (array_slice($hits, 0, $max) as $i => $h) {
            $n = $i + 1;
            $used[] = $n;
            $snippet = \Illuminate\Support\Str::limit(trim($h['title'].' — '.$h['text']), 180);
            $lines[] = '['.$n.'] '.$snippet;
        }
        $answer = implode("\n", $lines);
        $check = GhostVerifier::verifyText($answer, $hits);
        if ($check['result'] === GhostVerifier::FAIL) {
            return self::refuse($question, $hits, "Je refuse d’affirmer un chiffre qui n’est pas dans le coffre.");
        }
        $conf = array_sum(array_column($hits, 'score')) / max(1, count($hits));

        return [
            'ok' => true,
            'answer' => $answer,
            'evidence' => array_slice($hits, 0, $max),
            'evidence_used' => $used,
            'confidence' => round(max(0, min(1, $conf)), 4),
            'refusal' => null,
            'layers' => array_values(array_unique(array_column($hits, 'layer'))),
        ];
    }

    public static function looksFactual(string $message): bool
    {
        $m = mb_strtolower($message);

        return (bool) preg_match('/combien|prix|co[uû]te|titre|salaire|où|quel|quelle|preuve|coffre|document/u', $m);
    }

    /**
     * @param  list<array<string, mixed>>  $hits
     * @return array{ok:bool, answer:string, evidence:list<array>, evidence_used:list<int>, confidence:float, refusal:string, layers:list<string>}
     */
    private static function refuse(string $question, array $hits, string $why): array
    {
        return [
            'ok' => false,
            'answer' => '',
            'evidence' => $hits,
            'evidence_used' => [],
            'confidence' => 0,
            'refusal' => $why,
            'layers' => [],
        ];
    }
}
