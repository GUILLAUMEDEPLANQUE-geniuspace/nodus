<?php

namespace App\Support;

/**
 * Le LLM ne possède pas la vérité. Le vérificateur la garde.
 *
 * Claim → Evidence set → Rule → PASS / FAIL / UNKNOWN.
 * Une recherche textuelle dans un haystack n’est plus le juge unique.
 */
class GhostVerifier
{
    public const PASS = 'pass';

    public const FAIL = 'fail';

    public const UNKNOWN = 'unknown';

    /**
     * Extraire des claims structurés. Pas d’invention.
     *
     * @return list<array{type:string, value:string, claim:string}>
     */
    public static function claims(string $text): array
    {
        $out = [];
        if (preg_match_all('/(\d{2,5})\s*€/u', $text, $hits)) {
            foreach ($hits[1] as $n) {
                $out[] = ['type' => 'price', 'value' => (string) $n, 'claim' => $n.' €'];
            }
        }

        return $out;
    }

    /**
     * Ensemble de preuves observées (état, pas prose).
     *
     * @param  array<string, mixed>|list<mixed>  $observed
     * @return array{prices: list<string>, titles: list<string>, raw: string}
     */
    public static function evidenceFrom(array $observed): array
    {
        $raw = mb_strtolower((string) json_encode($observed, JSON_UNESCAPED_UNICODE));
        $prices = [];
        if (preg_match_all('/(\d{2,5})/u', $raw, $hits)) {
            $prices = array_values(array_unique($hits[1]));
        }
        $titles = [];
        foreach ($observed as $row) {
            if (is_array($row)) {
                foreach (['titre', 'title', 'label', 'name'] as $k) {
                    if (! empty($row[$k])) {
                        $titles[] = mb_strtolower((string) $row[$k]);
                    }
                }
            }
        }

        return ['prices' => $prices, 'titles' => $titles, 'raw' => $raw];
    }

    /**
     * @param  array{type:string, value:string, claim:string}  $claim
     * @param  array{prices?: list<string>, titles?: list<string>, raw?: string}  $evidence
     * @return array{claim: array, result: string, evidence: list<string>}
     */
    public static function rule(array $claim, array $evidence): array
    {
        if (($claim['type'] ?? '') === 'price') {
            $v = (string) ($claim['value'] ?? '');
            $prices = $evidence['prices'] ?? [];
            if ($prices === []) {
                return ['claim' => $claim, 'result' => self::UNKNOWN, 'evidence' => []];
            }
            if (in_array($v, $prices, true)) {
                return ['claim' => $claim, 'result' => self::PASS, 'evidence' => [$v]];
            }

            return ['claim' => $claim, 'result' => self::FAIL, 'evidence' => $prices];
        }

        return ['claim' => $claim, 'result' => self::UNKNOWN, 'evidence' => []];
    }

    /**
     * @param  list<array{result:string}>  $verdicts
     */
    public static function overall(array $verdicts): string
    {
        if ($verdicts === []) {
            return self::PASS;
        }
        foreach ($verdicts as $v) {
            if (($v['result'] ?? '') === self::FAIL) {
                return self::FAIL;
            }
        }
        foreach ($verdicts as $v) {
            if (($v['result'] ?? '') === self::UNKNOWN) {
                return self::UNKNOWN;
            }
        }

        return self::PASS;
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $observed
     * @return array{result:string, claims: list<array>}
     */
    public static function verifyText(string $text, array $observed): array
    {
        $claims = self::claims($text);
        $evidence = self::evidenceFrom($observed);
        $verdicts = array_map(fn ($c) => self::rule($c, $evidence), $claims);

        return ['result' => self::overall($verdicts), 'claims' => $verdicts];
    }

    /**
     * Garde-fou linguistique (jargon, ACT simulé) + claims chiffrés.
     *
     * @param  array<string, mixed>  $exec
     * @param  array<string, mixed>  $ctx
     * @return array{valid:bool, status:string, unsupported_claims:list<string>, safe_reply:string, citations:list<array>}
     */
    public static function check(string $reply, array $exec, array $ctx): array
    {
        $unsupported = [];
        $low = mb_strtolower($reply);

        foreach (['cck', 'parent_of', 'gpnode', 'llm', 'grant=', 'granted=true'] as $ban) {
            if (str_contains($low, $ban)) {
                $unsupported[] = 'jargon:'.$ban;
            }
        }

        if (preg_match('/je (?:vous )?(?:d[eé]bloque|ouvre|grant)|vous [eê]tes embauch/u', $low)) {
            $unsupported[] = 'act_without_permission';
        }

        $observed = array_merge(
            $exec['data']['produits'] ?? [],
            $ctx['produits'] ?? [],
            $ctx['details'] ?? [],
            isset($exec['constraints']) ? [$exec['constraints']] : [],
            isset($ctx['fourchette']) ? [$ctx['fourchette']] : [],
        );
        $priced = self::verifyText($reply, $observed);
        foreach ($priced['claims'] as $v) {
            if ($v['result'] === self::FAIL || $v['result'] === self::UNKNOWN) {
                $unsupported[] = 'unpriced:'.($v['claim']['value'] ?? '');
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
            'claim_check' => $priced,
        ];
    }
}
