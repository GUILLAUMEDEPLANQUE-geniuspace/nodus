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
     * « 2 400 € » = 2400, pas 400.
     *
     * @return list<array{type:string, value:string, claim:string}>
     */
    public static function claims(string $text): array
    {
        $out = [];
        foreach (self::moneyValues($text) as $n) {
            $out[] = ['type' => 'price', 'value' => $n, 'claim' => $n.' €'];
        }

        return $out;
    }

    /**
     * « 2 400 € » → « 2400 € ». Espaces entre chiffres seulement.
     */
    public static function foldMoney(string $text): string
    {
        return (string) preg_replace('/(?<=\d)[\s\x{00A0}](?=\d)/u', '', $text);
    }

    /**
     * @return list<string>
     */
    public static function moneyValues(string $text): array
    {
        $out = [];
        if (preg_match_all('/(\d{2,6})\s*€/u', self::foldMoney($text), $hits)) {
            foreach ($hits[1] as $n) {
                $out[] = (string) (int) $n;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Preuves = champs prix / texte avec €. Pas les scores JSON.
     *
     * @param  array<string, mixed>|list<mixed>  $observed
     * @return array{prices: list<string>, titles: list<string>, raw: string}
     */
    public static function evidenceFrom(array $observed): array
    {
        $prices = [];
        $titles = [];
        $raw = [];
        $walk = function ($row) use (&$prices, &$titles, &$raw, &$walk): void {
            if (! is_array($row)) {
                if (is_string($row) && str_contains($row, '€')) {
                    foreach (self::moneyValues($row) as $n) {
                        $prices[] = $n;
                    }
                    $raw[] = $row;
                }

                return;
            }
            foreach (['prix', 'price', 'amount', 'floor', 'ceil', 'list', 'min', 'max', 'offer', 'held'] as $k) {
                if (! isset($row[$k]) || is_array($row[$k])) {
                    continue;
                }
                $v = (string) $row[$k];
                $raw[] = $v;
                if (is_numeric($row[$k])) {
                    $prices[] = (string) (int) $row[$k];
                } else {
                    foreach (self::moneyValues($v) as $n) {
                        $prices[] = $n;
                    }
                    if ($v !== '' && ! str_contains($v, '€') && preg_match('/^\d{2,6}$/', self::foldMoney($v))) {
                        $prices[] = (string) (int) self::foldMoney($v);
                    }
                }
            }
            foreach (['titre', 'title', 'label', 'name', 'text'] as $k) {
                if (empty($row[$k]) || ! is_string($row[$k])) {
                    continue;
                }
                $raw[] = $row[$k];
                if (in_array($k, ['titre', 'title', 'label', 'name'], true)) {
                    $titles[] = mb_strtolower($row[$k]);
                }
                foreach (self::moneyValues($row[$k]) as $n) {
                    $prices[] = $n;
                }
            }
            foreach ($row as $v) {
                if (is_array($v)) {
                    $walk($v);
                }
            }
        };
        $walk($observed);

        return [
            'prices' => array_values(array_unique($prices)),
            'titles' => $titles,
            'raw' => mb_strtolower(implode(' ', $raw)),
        ];
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
            $exec['citations'] ?? [],
            $ctx['media'] ?? [],
            $ctx['medias'] ?? [],
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
