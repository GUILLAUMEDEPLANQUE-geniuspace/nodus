<?php

namespace App\Support;

/**
 * Attaque le raisonnement. Pas l’arbitre de vérité.
 * PLAN → CRITIC → REPLAN. Nodus reste la source de vérité.
 */
class GhostCritic
{
    /**
     * @param  array<string, mixed>  $sit
     * @param  array<string, mixed>  $wm
     * @return array{problems:list<string>, severe:bool}
     */
    public static function situation(array $sit, array $wm): array
    {
        $problems = [];
        if (($sit['unknowns'] ?? []) !== [] && ($sit['confidence'] ?? 1) > 0.8) {
            $problems[] = 'Confiance trop haute alors que des inconnues restent ouvertes.';
        }
        if (($sit['hard_constraints'] ?? []) === [] && ($sit['goal'] ?? '') === 'find_best_product') {
            $problems[] = 'Aucune contrainte dure. Le trade-off n’est pas posé.';
        }
        if (($wm['episodes'] ?? []) === [] && ($sit['goal'] ?? '') === 'discover_strategy') {
            $problems[] = 'Aucun épisode similaire. La stratégie part de zéro.';
        }

        return ['problems' => $problems, 'severe' => count($problems) >= 2];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $sit
     * @param  array<string, mixed>  $world
     * @return array{problems:list<string>, severe:bool, require:list<string>}
     */
    public static function plan(array $plan, array $sit, array $world = []): array
    {
        $problems = [];
        $require = [];
        $skill = $plan['skill'] ?? '';
        if (in_array($skill, ['run_campaign', 'discover_strategy', 'recover_failed_campaign'], true)) {
            $problems[] = 'Causalité non établie.';
            $require[] = 'baseline';
            if (($world['participation'] ?? 0) === 0 && ($world['completion'] ?? 0) === 0) {
                $problems[] = 'Aucune comparaison avec une baseline du monde.';
            }
            $problems[] = 'Hypothèse timing non testée.';
        }
        if ($skill === 'find_product' && empty($plan['constraints']['style']) && empty($sit['soft_preferences']['style'])) {
            $problems[] = 'Préférence de style absente. Risque de faux match.';
        }
        if (($plan['ceiling'] ?? '') === GhostSkills::ACT) {
            $problems[] = 'Le plan vise ACT. Confirmation humaine obligatoire.';
            $require[] = 'authority';
        }
        if ($skill === 'run_campaign') {
            $problems[] = 'Segment trop large tant que la cause n’est pas distinguée.';
            $problems[] = 'Coût estimé incomplet.';
        }

        return [
            'problems' => array_values(array_unique($problems)),
            'severe' => in_array('authority', $require, true) || count($problems) >= 3,
            'require' => $require,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $hyps
     * @return list<array{a:string, b:string, distinguish:string, falsify:string}>
     */
    public static function distinguish(array $hyps): array
    {
        $out = [];
        for ($i = 0; $i < count($hyps) - 1; $i++) {
            $a = $hyps[$i]['levers'][0] ?? ('H'.($i + 1));
            $b = $hyps[$i + 1]['levers'][0] ?? ('H'.($i + 2));
            $out[] = [
                'a' => $a,
                'b' => $b,
                'distinguish' => 'Observer '.$a.' à contraintes égales, puis '.$b.'.',
                'falsify' => 'Quelle observation réfuterait '.$a.' ? Un delta nul quand on agit sur '.$a.'.',
            ];
        }

        return $out;
    }
}
