<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ESTIMATE → DESIGN → OBSERVE → EVALUATE.
 *
 * estimate = prior. Ce n’est pas une observation.
 * observe  = événements du monde APRÈS une intervention. Sinon null.
 * Un contraste de catalogue n’est jamais un observed_gain.
 * evaluate = surprise, réfutation. Winner seulement si evidence=world et n >= MIN.
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
        $metric = $hypothesis['metric'] ?? 'preuves_tenues';
        $baseline = (float) ($hypothesis['baseline'] ?? $world[$metric] ?? 0);
        $gaps = $world['gaps'] ?? [];
        $confounders = ['saison', 'taille_du_lieu', 'trafic_externe'];
        foreach ($gaps as $lever => $g) {
            if (($g['size'] ?? 0) > 0.15) {
                $confounders[] = $lever;
            }
        }

        return [
            'id' => null,
            'strategy_code' => $strategy['code'] ?? '',
            'hypothesis_code' => $hypothesis['code'] ?? '',
            'objective' => $strategy['objective'] ?? '',
            'node_id' => $nodeId !== '' ? $nodeId : ($world['node_id'] ?? ''),
            'kind' => 'intervention',
            'population' => 'visiteurs du lieu, après déploiement',
            'control_group' => 'avant intervention (baseline)',
            'treatment_group' => 'après APPLY humain de strategy.deploy',
            'metric' => $metric,
            'baseline' => $baseline,
            'prediction' => $pred,
            'observed' => null,
            'delta' => null,
            'surprise' => null,
            'innovation' => null,
            'discovery_score' => null,
            'confidence' => null,
            'n_control' => (int) ($world[$metric] ?? 0),
            'n_treatment' => 0,
            'sample_size' => 0,
            'confounders' => array_values(array_unique($confounders)),
            'causal_method' => '',
            'causal_claim' => false,
            'stopping_reason' => null,
            'status' => self::PLANNED,
            'evidence' => null,
            'strategy' => $strategy,
            'hypothesis' => $hypothesis,
            'started_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Observation du monde. Pas de jitter. Pas de ratio de catalogue.
     *
     * @param  array<string, mixed>  $trial
     * @param  array<string, mixed>  $world
     * @return array<string, mixed>
     */
    public static function observe(array $trial, array $world): array
    {
        $nodeId = (string) ($trial['node_id'] ?? $world['node_id'] ?? '');
        $code = (string) ($trial['strategy_code'] ?? '');
        $at = GhostWorldObserver::intervenedAt($nodeId, $code);
        $events = GhostWorldObserver::events($nodeId, $at);
        $n = (int) $events['n'];
        $trial['n_treatment'] = $n;
        $trial['sample_size'] = $n + (int) ($trial['n_control'] ?? 0);

        if ($at === null) {
            $trial['status'] = self::AWAITING;
            $trial['observed'] = null;
            $trial['delta'] = null;
            $trial['evidence'] = 'none';
            $trial['causal_claim'] = false;
            $trial['causal_method'] = '';
            $trial['stopping_reason'] = $n >= GhostWorldObserver::MIN_N
                ? 'Des événements existent. Pas d’intervention déployée. Pas de causalité.'
                : 'Pas d’intervention. Ghost n’a pas le droit d’en inventer une.';

            return $trial;
        }
        if ($n < GhostWorldObserver::MIN_N) {
            $trial['status'] = self::UNDERPOWERED;
            $trial['observed'] = null;
            $trial['delta'] = null;
            $trial['evidence'] = 'none';
            $trial['causal_claim'] = false;
            $trial['causal_method'] = 'interrupted_time_series';
            $trial['stopping_reason'] = 'Intervention tenue, échantillon trop petit (n < '.GhostWorldObserver::MIN_N.'). Delta non publié.';

            return $trial;
        }
        $metric = (string) ($trial['metric'] ?? 'preuves_tenues');
        $after = match ($metric) {
            'participation' => $events['replies'],
            'visits' => $events['visits'],
            default => $events['grants'],
        };
        $before = (float) ($trial['baseline'] ?? 0);
        $obs = $before > 0 ? round(($after - $before) / max($before, 1), 4) : round($after / max($n, 1), 4);
        $trial['status'] = self::OBSERVED;
        $trial['observed'] = $obs;
        $trial['delta'] = $obs;
        $trial['evidence'] = 'world';
        $trial['causal_method'] = 'interrupted_time_series';
        $trial['causal_claim'] = false;
        $trial['stopping_reason'] = 'Avant/après sur événements datés. Contrôle non randomisé : pas une causalité établie.';

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
            'n' => (int) ($trial['n_treatment'] ?? $trial['sample_size'] ?? 0),
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
        $trial['confidence'] = round(min(0.9, 0.35 + 0.05 * (int) ($trial['n_treatment'] ?? 0)), 4);
        $trial['verdict'] = $h['verdict'];
        $s = $trial['strategy'] ?? [];
        $s['observed_gain'] = $obs;
        $s['expected_gain'] = $pred;
        $s['surprise'] = $trial['surprise'];
        $s['innovation'] = $innovation;
        $s['evidence'] = $trial['evidence'];
        $s['status'] = self::strategyStatus($h, $obs, $risk, $trial);
        $trial['strategy'] = $s;

        return $trial;
    }

    /**
     * @param  array<string, mixed>  $h
     * @param  array<string, mixed>  $trial
     */
    public static function strategyStatus(array $h, float $obs, float $risk, array $trial = []): string
    {
        if ($risk > 0.35) {
            return 'dangerous';
        }
        if (($trial['evidence'] ?? '') !== 'world' || ($trial['observed'] ?? null) === null) {
            return 'untested';
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
