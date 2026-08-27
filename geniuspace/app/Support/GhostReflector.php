<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Reflection loop. Expected vs actual. Met à jour mémoire, skill, croyance.
 */
class GhostReflector
{
    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $exec
     * @param  array<string, mixed>  $check
     * @param  array<string, mixed>|null  $trial
     * @return array<string, mixed>
     */
    public static function turn(GpNode $node, array $plan, array $exec, array $check, ?array $trial = null): array
    {
        $expected = $trial['prediction'] ?? (($check['valid'] ?? true) ? 1.0 : 0.0);
        $actual = $trial['observed'] ?? (($check['valid'] ?? true) ? 1.0 : 0.0);
        $gap = is_numeric($actual) && is_numeric($expected) ? abs((float) $actual - (float) $expected) : null;
        $why = 'Tour ancré.';
        $rule = null;
        if (! ($check['valid'] ?? true)) {
            $why = 'Prédiction verbale réfutée par le vérifieur.';
            $rule = 'Ne pas citer hors coffre.';
        } elseif ($trial && $trial['observed'] === null) {
            $why = 'Pas d’observation du monde. Rien à généraliser.';
        } elseif ($trial && ($trial['hypothesis']['status'] ?? '') === GhostHypothesis::REFUTED) {
            $why = 'L’hypothèse est fausse dans ce contexte.';
            $rule = $trial['hypothesis']['verdict'] ?? 'Réviser la cause.';
        } elseif ($gap !== null && $gap >= 0.08) {
            $why = 'Écart prédiction / monde. Hypothèse d’audience ou de timing à revoir.';
            $rule = 'Ne pas réutiliser la même campagne trop tôt sur le même segment.';
        }
        $ref = [
            'expected' => $expected,
            'actual' => $actual,
            'gap' => $gap,
            'why' => $why,
            'assumed' => $plan['skill'] ?? '',
            'wrong' => $rule ? true : false,
            'change' => $rule,
            'confidence' => $trial['confidence'] ?? (($check['valid'] ?? true) ? 0.7 : 0.4),
        ];
        self::store($node, $ref);
        if ($rule) {
            GhostLearn::afterExperiment((string) $node->id, [
                'hypothesis' => ['status' => GhostHypothesis::REFUTED, 'hypothesis' => $why, 'levers' => [$plan['skill'] ?? 'unknown']],
                'observed' => is_numeric($actual) ? $actual : null,
                'surprise' => $gap,
            ]);
        }

        return $ref;
    }

    /**
     * @param  array<string, mixed>  $ref
     */
    public static function store(GpNode $node, array $ref): void
    {
        if (! Schema::hasTable('ghost_reflections')) {
            return;
        }
        DB::table('ghost_reflections')->insert([
            'node_id' => $node->id,
            'expected' => $ref['expected'],
            'actual' => is_numeric($ref['actual']) ? $ref['actual'] : null,
            'gap' => $ref['gap'],
            'why' => Str::limit((string) $ref['why'], 240),
            'change_rule' => $ref['change'] ? Str::limit((string) $ref['change'], 240) : null,
            'confidence' => $ref['confidence'],
            'created_at' => now(),
        ]);
    }
}
