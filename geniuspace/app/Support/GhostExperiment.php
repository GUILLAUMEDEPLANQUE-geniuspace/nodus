<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ESTIMATE → DESIGN → OBSERVE → EVALUATE.
 *
 * estimate = prior (formule). Ce n’est pas une observation.
 * observe  = monde (Engine / cohortes). Peut renvoyer null.
 * evaluate = surprise, réfutation, discovery. Jamais un winner sans évidence.
 */
class GhostExperiment
{
    public const PLANNED = 'planned';

    public const AWAITING = 'awaiting_authority';

    public const OBSERVED = 'observed';

    public const UNDERPOWERED = 'underpowered';

    public const UNKNOWN = 'unknown';

    /**
     * @param  array<string, mixed>  $strategy
     * @param  array<string, mixed>  $hypothesis
     * @param  array<string, mixed>  $world
     * @return array<string, mixed>
     */
    public static function design(array $strategy, array $hypothesis, array $world, string $nodeId = ''): array
    {
        $pred = (float) ($strategy['expected_gain'] ?? $strategy['performance'] ?? 0);
        $c = $world['cohorts'] ?? [];
        $controlN = (int) ($c['threads_cold'] ?? 0) + (int) ($c['media_gated'] ?? 0);
        $treatN = (int) ($c['threads_hot'] ?? 0) + (int) ($c['media_free'] ?? 0);

        return [
            'id' => null,
            'strategy_code' => $strategy['code'] ?? '',
            'hypothesis_code' => $hypothesis['code'] ?? '',
            'objective' => $strategy['objective'] ?? '',
            'node_id' => $nodeId !== '' ? $nodeId : ($world['node_id'] ?? ''),
            'kind' => 'natural',
            'population' => 'lieu',
            'control_group' => 'cohorte froide / gated',
            'treatment_group' => 'cohorte chaude / libre',
            'metric' => $hypothesis['metric'] ?? 'participation',
            'baseline' => (float) ($hypothesis['baseline'] ?? $world['participation'] ?? 0),
            'prediction' => $pred,
            'observed' => null,
            'delta' => null,
            'surprise' => null,
            'innovation' => null,
            'discovery_score' => null,
            'confidence' => null,
            'n_control' => $controlN,
            'n_treatment' => $treatN,
            'sample_size' => $controlN + $treatN,
            'confounders' => ['taille_du_lieu', 'saison'],
            'causal_method' => 'natural_experiment',
            'stopping_reason' => null,
            'status' => self::PLANNED,
            'evidence' => null,
            'strategy' => $strategy,
            'hypothesis' => $hypothesis,
        ];
    }

    /**
     * Observation du monde. Pas de jitter. Pas de formule déguisée.
     *
     * @param  array<string, mixed>  $trial
     * @param  array<string, mixed>  $world
     * @return array<string, mixed>
     */
    public static function observe(array $trial, array $world): array
    {
        $assoc = GhostWorldObserver::associations($world);
        $levers = $trial['hypothesis']['levers'] ?? $trial['strategy']['genome']['mechanisms'] ?? [];
        $hit = null;
        foreach ($assoc as $a) {
            if (in_array($a['cause'], $levers, true) && ! ($a['underpowered'] ?? true)) {
                $hit = $a;
                break;
            }
        }
        if ($hit === null) {
            foreach ($assoc as $a) {
                if (in_array($a['cause'], $levers, true)) {
                    $hit = $a;
                    break;
                }
            }
        }
        $n = (int) ($trial['sample_size'] ?? 0);
        if ($hit === null || $n < 2) {
            $trial['status'] = $n < 2 ? self::UNDERPOWERED : self::AWAITING;
            $trial['observed'] = null;
            $trial['delta'] = null;
            $trial['stopping_reason'] = $n < 2
                ? 'Pas de cohorte témoin dans le monde. L’autorité n’a pas déployé.'
                : 'Association trop faible. Expérience planifiée, pas encore tenue.';
            $trial['evidence'] = 'none';

            return $trial;
        }
        if ($hit['underpowered'] ?? false) {
            $trial['status'] = self::UNDERPOWERED;
            $trial['observed'] = (float) $hit['delta'];
            $trial['delta'] = (float) $hit['delta'];
            $trial['n_treatment'] = (int) ($hit['n'] ?? $n);
            $trial['stopping_reason'] = 'Échantillon trop petit. Delta noté, pas une tenue.';
            $trial['evidence'] = 'weak';

            return $trial;
        }
        $trial['status'] = self::OBSERVED;
        $trial['observed'] = (float) $hit['delta'];
        $trial['delta'] = (float) $hit['delta'];
        $trial['causal_method'] = $hit['method'];
        $trial['evidence'] = 'world';
        $trial['stopping_reason'] = 'Expérience naturelle. Pas une intervention Ghost.';

        return $trial;
    }

    /**
     * @param  array<string, mixed>  $trial
     * @return array<string, mixed>
     */
    public static function evaluate(array $trial): array
    {
        $pred = (float) ($trial['prediction'] ?? 0);
        $obs = $trial['observed'];
        $novelty = (float) ($trial['strategy']['novelty'] ?? 0);
        $cost = (float) ($trial['strategy']['cost'] ?? 0);
        $risk = (float) ($trial['strategy']['risk'] ?? 0);
        $h = GhostHypothesis::falsify($trial['hypothesis'] ?? [], [
            'observed' => $obs,
            'prediction' => $pred,
            'n' => (int) ($trial['sample_size'] ?? 0),
        ]);
        $trial['hypothesis'] = $h;
        if ($obs === null) {
            $trial['surprise'] = null;
            $trial['innovation'] = 0.0;
            $trial['discovery_score'] = round($pred + $novelty - $cost - $risk, 4);
            $trial['confidence'] = null;
            $trial['verdict'] = $h['verdict'];

            return $trial;
        }
        $obs = (float) $obs;
        $surprise = abs($obs - $pred);
        $innovation = round($obs * $surprise, 4);
        $trial['surprise'] = round($surprise, 4);
        $trial['innovation'] = $innovation;
        $trial['discovery_score'] = round($obs + $novelty + $surprise - $cost - $risk, 4);
        $trial['confidence'] = round(min(0.9, 0.4 + 0.1 * (int) ($trial['sample_size'] ?? 0)), 4);
        $trial['verdict'] = $h['verdict'];
        $s = $trial['strategy'] ?? [];
        $s['observed_gain'] = $obs;
        $s['expected_gain'] = $pred;
        $s['surprise'] = $trial['surprise'];
        $s['innovation'] = $innovation;
        $s['evidence'] = $trial['evidence'];
        $s['status'] = self::strategyStatus($h, $obs, $risk);
        $trial['strategy'] = $s;

        return $trial;
    }

    /**
     * @param  array<string, mixed>  $h
     */
    public static function strategyStatus(array $h, float $obs, float $risk): string
    {
        if ($risk > 0.35) {
            return 'dangerous';
        }
        if (($h['status'] ?? '') === GhostHypothesis::REFUTED) {
            return 'loser';
        }
        if (($h['status'] ?? '') === GhostHypothesis::SUPPORTED && $obs >= 0.05) {
            return 'winner';
        }
        if ($obs < 0) {
            return 'loser';
        }

        return 'partial';
    }

    /**
     * @param  array<string, mixed>  $trial
     */
    public static function store(array $trial): void
    {
        if (! Schema::hasTable('ghost_strategy_experiments')) {
            return;
        }
        DB::table('ghost_strategy_experiments')->insert([
            'node_id' => $trial['node_id'] ?? '',
            'strategy_code' => $trial['strategy_code'] ?? '',
            'hypothesis_code' => $trial['hypothesis_code'] ?? '',
            'objective' => $trial['objective'] ?? '',
            'population' => $trial['population'] ?? '',
            'control_group' => $trial['control_group'] ?? '',
            'treatment_group' => $trial['treatment_group'] ?? '',
            'metric' => $trial['metric'] ?? '',
            'baseline' => $trial['baseline'] ?? 0,
            'prediction' => $trial['prediction'] ?? 0,
            'observed' => $trial['observed'],
            'delta' => $trial['delta'],
            'confidence' => $trial['confidence'],
            'evidence' => $trial['evidence'] ?? '',
            'status' => $trial['status'] ?? self::UNKNOWN,
            'causal_method' => $trial['causal_method'] ?? '',
            'sample_size' => $trial['sample_size'] ?? 0,
            'confounders' => json_encode($trial['confounders'] ?? [], JSON_UNESCAPED_UNICODE),
            'stopping_reason' => $trial['stopping_reason'] ?? '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
