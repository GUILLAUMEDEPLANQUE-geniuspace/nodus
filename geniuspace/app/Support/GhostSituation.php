<?php

namespace App\Support;

/**
 * Situation structurée. Le LLM (optionnel) propose un JSON. Nodus valide.
 * Regex = extracteurs de champs, pas le cerveau.
 */
class GhostSituation
{
    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    public static function parse(string $message, array $ctx = []): array
    {
        $m = mb_strtolower($message);
        $hard = [];
        $soft = [];
        $tradeoffs = [];
        $unknowns = [];

        preg_match_all('/(\d{2,5})/u', $m, $hits);
        $nums = [];
        foreach ($hits[1] ?? [] as $raw) {
            $n = (int) $raw;
            if ($n >= 20) {
                $nums[] = $n;
            }
        }
        $nums = array_values(array_unique($nums));
        $isOffer = (bool) preg_match('/offre|propose|prend|n[eé]goc|rabais|r[eé]duc/u', $m);
        if ($nums !== []) {
            $hard['amount'] = $nums[0];
            if (! $isOffer) {
                $hard['max_price'] = $nums[0];
                if (preg_match('/mieux|quand m[eê]me|si tu (?:trouves|vois)|sauf si|exception/u', $m) && isset($nums[1]) && $nums[1] > $nums[0]) {
                    $tradeoffs[] = [
                        'if' => 'quality_gain > threshold',
                        'allow_price' => $nums[1],
                    ];
                }
            }
        }

        if (preg_match('/sombre|dark|clair|simple|vintage|minimal|classique/u', $m, $h)) {
            $soft['style'] = $h[0] === 'dark' ? 'sombre' : $h[0];
        }
        if (preg_match('/vite|urgent|tout de suite|rapidement/u', $m)) {
            $hard['availability'] = 'fast';
        }
        if (preg_match('/sans augmenter le budget|sans budget|budget (?:fixe|constant)|pas plus cher/u', $m)) {
            $hard['budget_neutral'] = true;
        }
        if (preg_match('/ventes|conversion|chiffre/u', $m)) {
            $unknowns[] = 'cause of drop';
        }

        $goal = self::goalName($m);
        $stance = self::stance($m, $hard, $soft);

        return [
            'goal' => $goal['name'],
            'skill' => $goal['skill'],
            'intent' => $goal['intent'],
            'hard_constraints' => $hard,
            'soft_preferences' => $soft,
            'tradeoffs' => $tradeoffs,
            'urgency' => isset($hard['availability']) ? 'high' : 'normal',
            'confidence' => $goal['confidence'],
            'unknowns' => $unknowns,
            'stance' => $stance,
            'raw' => $message,
        ];
    }

    /**
     * Hook LLM : un JSON de situation est fusionné, puis recalé sur le monde.
     *
     * @param  array<string, mixed>  $proposed
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>
     */
    public static function ingest(array $proposed, array $base): array
    {
        $out = $base;
        foreach (['goal', 'skill', 'intent', 'urgency', 'stance'] as $k) {
            if (isset($proposed[$k]) && is_string($proposed[$k]) && $proposed[$k] !== '') {
                $out[$k] = $proposed[$k];
            }
        }
        if (isset($proposed['hard_constraints']) && is_array($proposed['hard_constraints'])) {
            $out['hard_constraints'] = array_merge($out['hard_constraints'], $proposed['hard_constraints']);
        }
        if (isset($proposed['soft_preferences']) && is_array($proposed['soft_preferences'])) {
            $out['soft_preferences'] = array_merge($out['soft_preferences'], $proposed['soft_preferences']);
        }
        if (isset($proposed['tradeoffs']) && is_array($proposed['tradeoffs'])) {
            $out['tradeoffs'] = array_merge($out['tradeoffs'], $proposed['tradeoffs']);
        }
        if (isset($proposed['confidence']) && is_numeric($proposed['confidence'])) {
            $out['confidence'] = max(0, min(1, (float) $proposed['confidence']));
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $sit
     * @return array<string, mixed>
     */
    public static function constraints(array $sit): array
    {
        $c = $sit['hard_constraints'] ?? [];
        if (! empty($sit['soft_preferences']['style'])) {
            $c['style'] = $sit['soft_preferences']['style'];
        }
        if (! empty($sit['tradeoffs'][0]['allow_price'])) {
            $c['allow_price'] = (int) $sit['tradeoffs'][0]['allow_price'];
        }

        return $c;
    }

    /**
     * @return array{name:string, skill:string, intent:string, confidence:float}
     */
    public static function goalName(string $m): array
    {
        if (preg_match('/bonjour|salut|hello|hey|qui es-tu/u', $m)) {
            return ['name' => 'greet', 'skill' => 'greet', 'intent' => 'hello', 'confidence' => 0.95];
        }
        if (preg_match('/campagne.*(échou|rat[eé]|failed)|r[eé]cup[eé]r.*campagne|recover_failed/u', $m)) {
            return ['name' => 'recover_failed_campaign', 'skill' => 'recover_failed_campaign', 'intent' => 'campaign', 'confidence' => 0.92];
        }
        if (GhostStrategy::looksLike($m) || preg_match('/ventes|conversion|sans augmenter le budget/u', $m)) {
            return ['name' => 'discover_strategy', 'skill' => 'discover_strategy', 'intent' => 'strategy', 'confidence' => 0.9];
        }
        if (preg_match('/offre|propose|prend|n[eé]goc|rabais|r[eé]duc/u', $m) && preg_match('/\d{2,5}/u', $m)) {
            return ['name' => 'negotiate', 'skill' => 'negotiate', 'intent' => 'price', 'confidence' => 0.9];
        }
        if (preg_match('/trouve|cherche|autour|sombre|produit|œuvre|oeuvre/u', $m)) {
            return ['name' => 'find_best_product', 'skill' => 'find_product', 'intent' => 'price', 'confidence' => 0.91];
        }
        if (GhostEdit::looksLike($m)) {
            return ['name' => 'edit_page', 'skill' => 'edit_page', 'intent' => 'studio', 'confidence' => 0.9];
        }

        return ['name' => 'investigate_place', 'skill' => 'investigate_place', 'intent' => 'navigate', 'confidence' => 0.5];
    }

    /**
     * @param  array<string, mixed>  $hard
     * @param  array<string, mixed>  $soft
     */
    public static function stance(string $m, array $hard, array $soft): string
    {
        if (preg_match('/je ne sais pas|tu ne sais pas|inconnu/u', $m)) {
            return 'dont_know';
        }
        if (preg_match('/peut-[eê]tre|il me semble|j.imagine/u', $m)) {
            return 'suspect';
        }
        if ($hard !== [] || $soft !== []) {
            return 'know';
        }
        if (preg_match('/pourquoi|comment se fait/u', $m)) {
            return 'need_evidence';
        }

        return 'infer';
    }
}
