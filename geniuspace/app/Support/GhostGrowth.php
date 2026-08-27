<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * Moteur de croissance. Écart prédiction / réalité.
 * Sans observation du monde : rien n’est mis à jour.
 *
 * Prediction → Outcome → Error → Reflect → Memory / Skill / Strategy / Self
 */
class GhostGrowth
{
    /**
     * @param  array{expected?:mixed, actual?:mixed, trial?:?array, reflection?:?array}  $turn
     * @return array{updated:bool, error:?float, reason:string, memory:bool, skill:bool, strategy:bool, self:bool}
     */
    public static function after(GpNode $node, array $turn): array
    {
        $trial = $turn['trial'] ?? null;
        $expected = $turn['expected'] ?? ($trial['prediction'] ?? ($turn['reflection']['expected'] ?? null));
        $actual = $turn['actual'] ?? ($trial['observed'] ?? ($turn['reflection']['actual'] ?? null));
        if ($actual === null) {
            return [
                'updated' => false,
                'error' => null,
                'reason' => 'Pas d’observation du monde. Rien à généraliser.',
                'memory' => false,
                'skill' => false,
                'strategy' => false,
                'self' => false,
            ];
        }
        $err = is_numeric($expected) && is_numeric($actual)
            ? abs((float) $actual - (float) $expected)
            : null;
        $surprise = $err !== null && $err >= 0.08;

        return [
            'updated' => true,
            'error' => $err,
            'reason' => $surprise ? 'Écart prédiction / monde.' : 'Écart acceptable.',
            'memory' => true,
            'skill' => ! $surprise,
            'strategy' => $surprise,
            'self' => true,
        ];
    }
}
