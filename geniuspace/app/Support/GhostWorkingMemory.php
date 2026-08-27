<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * Mémoire de travail. Continuité d’un tour à l’autre. Pas la vérité du monde.
 */
class GhostWorkingMemory
{
    /**
     * @param  array<string, mixed>  $sit
     * @param  array<string, mixed>  $world
     * @return array<string, mixed>
     */
    public static function open(GpNode $node, array $sit, array $world): array
    {
        $prev = GhostMemory::load($node);

        return [
            'goal' => $sit['goal'] ?? $prev['goal'],
            'subgoals' => self::subgoals((string) ($sit['goal'] ?? '')),
            'known' => array_filter([
                'style' => $sit['soft_preferences']['style'] ?? ($prev['constraints']['style'] ?? null),
                'max_price' => $sit['hard_constraints']['max_price'] ?? ($prev['constraints']['max_price'] ?? null),
            ]),
            'unknowns' => $sit['unknowns'] ?? [],
            'hypotheses' => $prev['hypotheses'] ?? [],
            'constraints' => array_merge($prev['constraints'] ?? [], GhostSituation::constraints($sit)),
            'open_questions' => $sit['unknowns'] ?? [],
            'risks' => [],
            'pending' => [],
            'evidence' => [],
            'plan' => $prev['plan'] ?? [],
            'alternatives' => [],
            'episodes' => [],
            'world' => [
                'participation' => $world['participation'] ?? 0,
                'completion' => $world['completion'] ?? 0,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function subgoals(string $goal): array
    {
        return match ($goal) {
            'discover_strategy', 'increase_sales' => [
                'comprendre la baisse',
                'identifier les segments',
                'trouver les causes',
                'générer des stratégies',
                'simuler',
                'préparer une expérience',
            ],
            'recover_failed_campaign' => [
                'inspecter l’erreur',
                'classer l’échec',
                'relire les épisodes',
                'générer des reprises',
                'simuler',
                'demander l’autorité',
            ],
            'find_best_product' => [
                'lire les contraintes',
                'lister',
                'comparer',
                'appliquer le trade-off',
            ],
            default => ['observer', 'ancrer', 'répondre'],
        };
    }
}
