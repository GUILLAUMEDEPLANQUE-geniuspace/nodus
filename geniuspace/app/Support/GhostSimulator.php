<?php

namespace App\Support;

/**
 * Simulateur mental. « Si je fais X ? »
 * Les sorties sont des prédictions, jamais des observations du monde.
 */
class GhostSimulator
{
    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $sit
     * @param  list<array<string, mixed>>  $episodes
     * @return list<array<string, mixed>>
     */
    public static function of(array $plan, array $sit, array $episodes = []): array
    {
        $skill = $plan['skill'] ?? 'investigate_place';
        $hard = $sit['hard_constraints']['max_price'] ?? null;
        $allow = $sit['tradeoffs'][0]['allow_price'] ?? $hard;
        $budgetNeutral = ! empty($sit['hard_constraints']['budget_neutral']);
        $a = [
            'id' => 'A',
            'kind' => 'simulation',
            'label' => $budgetNeutral ? 'Max conversion, budget tendu' : 'Contrainte dure',
            'plan' => $skill,
            'expected' => $budgetNeutral
                ? 'plus de conversion, coût et risque plus hauts (prior, pas une preuve)'
                : 'respect du plafond'.($hard ? ' '.$hard.' €' : ''),
            'cost' => $budgetNeutral ? 'haut' : 'bas',
            'risk' => $budgetNeutral ? 'haut' : 'bas',
            'novelty' => 'faible',
            'observed' => null,
        ];
        $b = [
            'id' => 'B',
            'kind' => 'simulation',
            'label' => $budgetNeutral ? 'Moins performante, beaucoup moins risquée' : 'Trade-off',
            'plan' => $skill,
            'expected' => $allow && $allow !== $hard
                ? 'autorise '.$allow.' € si le gain de qualité tient'
                : ($budgetNeutral
                    ? 'gain plus faible, budget tenu (prior)'
                    : 'même plafond, moindre risque'),
            'cost' => 'moyen',
            'risk' => 'moyen',
            'novelty' => 'faible',
            'observed' => null,
        ];
        $similar = $episodes[0]['payload']['skill'] ?? null;
        $c = [
            'id' => 'C',
            'kind' => 'simulation',
            'label' => 'Nouvelle',
            'plan' => $similar ?: $skill,
            'expected' => $similar
                ? 'rejouer un épisode similaire (prior, pas une preuve)'
                : 'peu documentée',
            'cost' => 'inconnu',
            'risk' => 'haut',
            'novelty' => 'haute',
            'observed' => null,
        ];

        return [$a, $b, $c];
    }

    /**
     * @param  list<array<string, mixed>>  $sims
     * @return array<string, mixed>
     */
    public static function recommend(array $sims): array
    {
        $pick = $sims[1] ?? $sims[0];

        return [
            'choose' => $pick['id'] ?? 'B',
            'why' => 'Je recommande B : le gain marginal de A ne justifie pas toujours le risque, C est trop peu documentée. Ce n’est pas une observation.',
            'kind' => 'simulation',
        ];
    }
}
