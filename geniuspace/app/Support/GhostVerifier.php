<?php

namespace App\Support;

/**
 * Le LLM ne possède pas la vérité. Le vérificateur la garde.
 * Toute affirmation chiffrée ou nominale doit vivre dans les observations.
 */
class GhostVerifier
{
    /**
     * @param  array<string, mixed>  $exec
     * @param  array<string, mixed>  $ctx
     * @return array{valid:bool, status:string, unsupported_claims:list<string>, safe_reply:string, citations:list<array>}
     */
    public static function check(string $reply, array $exec, array $ctx): array
    {
        $unsupported = [];
        $hay = self::haystack($exec, $ctx);
        $low = mb_strtolower($reply);

        foreach (['cck', 'parent_of', 'gpnode', 'llm', 'grant=', 'granted=true'] as $ban) {
            if (str_contains($low, $ban)) {
                $unsupported[] = 'jargon:'.$ban;
            }
        }

        if (preg_match('/je (?:vous )?(?:d[eé]bloque|ouvre|grant)|vous [eê]tes embauch/u', $low)) {
            $unsupported[] = 'act_without_permission';
        }

        if (preg_match_all('/(\d{2,5})\s*€/u', $reply, $hits)) {
            foreach ($hits[1] as $n) {
                if (! str_contains($hay, $n)) {
                    $unsupported[] = 'unpriced:'.$n;
                }
            }
        }

        $valid = $unsupported === [];
        $safe = $valid
            ? $reply
            : (str_contains(implode(' ', $unsupported), 'act_')
                ? 'Je n’ouvre rien moi-même. Le coffre tranche — achat, épreuve ou unlock.'
                : 'Je n’ai pas cette preuve dans le coffre.');

        return [
            'valid' => $valid,
            'status' => $valid ? GhostMemory::KNOWN : GhostMemory::UNCERTAIN,
            'unsupported_claims' => $unsupported,
            'safe_reply' => $safe,
            'citations' => $exec['citations'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $exec
     * @param  array<string, mixed>  $ctx
     */
    private static function haystack(array $exec, array $ctx): string
    {
        $parts = [json_encode($exec['data'] ?? [], JSON_UNESCAPED_UNICODE)];
        $parts[] = json_encode($ctx['produits'] ?? [], JSON_UNESCAPED_UNICODE);
        $parts[] = json_encode($ctx['fourchette'] ?? [], JSON_UNESCAPED_UNICODE);
        $parts[] = json_encode($ctx['details'] ?? [], JSON_UNESCAPED_UNICODE);
        $parts[] = json_encode($ctx['liens'] ?? [], JSON_UNESCAPED_UNICODE);

        return mb_strtolower(implode(' ', $parts));
    }
}
