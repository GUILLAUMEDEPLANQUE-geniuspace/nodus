<?php

namespace App\Support;

use App\Llm\GhostTools;
use App\Models\GpNode;

/**
 * Exécute un plan multi-outils. Plafond OBSERVE/PREPARE/ACT.
 * Un outil ACT n'est jamais lancé : il devient une confirmation demandée.
 */
class GhostExecutor
{
    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $working
     * @return array<string, mixed>
     */
    public static function run(GpNode $node, array $plan, string $message, array $ctx, array $working = []): array
    {
        $ceiling = $plan['ceiling'] ?? GhostSkills::OBSERVE;
        $tools = [];
        $citations = [];
        $actions = [];
        $observations = [];
        $pending = [];
        $primary = null;
        $data = [];

        foreach ($plan['steps'] ?? [] as $step) {
            $tool = (string) ($step['tool'] ?? '');
            if ($tool === '') {
                continue;
            }
            $need = GhostSkills::toolLevel($tool);
            if ($need === GhostSkills::ACT || ! GhostSkills::may($tool, $ceiling === GhostSkills::ACT ? GhostSkills::PREPARE : $ceiling)) {
                $pending[] = ['tool' => $tool, 'level' => $need, 'reason' => 'confirmation'];
                continue;
            }
            $result = GhostTools::call($tool, $node, $ctx, [
                'message' => $message,
                'constraints' => $plan['constraints'] ?? [],
                'working' => $working,
                'data' => $data,
            ]);
            if (! $result) {
                continue;
            }
            $tools[] = $result['tool'] ?? $tool;
            $citations = array_merge($citations, $result['citations'] ?? []);
            $actions = array_merge($actions, $result['actions'] ?? []);
            $observations[] = [
                'tool' => $result['tool'] ?? $tool,
                'summary' => self::summarize($result),
            ];
            $data = array_merge($data, is_array($result['data'] ?? null) ? $result['data'] : []);
            if ($primary === null) {
                $primary = $result;
            }
        }

        $pick = self::pick($data, $plan['constraints'] ?? [], $working);

        return [
            'tools' => array_values(array_unique($tools)),
            'citations' => $citations,
            'actions' => $actions,
            'observations' => $observations,
            'pending' => $pending,
            'primary' => $primary,
            'data' => $data,
            'pick' => $pick,
            'ceiling' => $ceiling,
            'constraints' => $plan['constraints'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $exec
     * @return array<string, mixed>
     */
    public static function merge(array $ctx, array $exec): array
    {
        foreach ($exec['data'] ?? [] as $k => $v) {
            $ctx[$k] = $v;
        }

        return $ctx;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $constraints
     * @param  array<string, mixed>  $working
     * @return array<string, mixed>|null
     */
    public static function pick(array $data, array $constraints, array $working): ?array
    {
        $ranked = $data['ranked'] ?? $data['matches'] ?? $data['produits'] ?? [];
        if (! is_array($ranked) || $ranked === []) {
            return null;
        }
        $first = $ranked[0];
        if (! is_array($first)) {
            return null;
        }

        return $first + ['_why' => $constraints['style'] ?? ($working['constraints']['style'] ?? null)];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private static function summarize(array $result): string
    {
        $tool = $result['tool'] ?? 'tool';
        $data = $result['data'] ?? [];
        if (isset($data['produits']) && is_array($data['produits'])) {
            return $tool.' · '.count($data['produits']).' produits';
        }
        if (isset($data['matches']) && is_array($data['matches'])) {
            return $tool.' · '.count($data['matches']).' correspondances';
        }
        if (isset($data['fiches']) && is_array($data['fiches'])) {
            return $tool.' · '.count($data['fiches']).' fiches';
        }
        if (isset($data['salles']) && is_array($data['salles'])) {
            return $tool.' · '.count($data['salles']).' salles';
        }

        return $tool;
    }
}
