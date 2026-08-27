<?php

namespace App\Support;

/**
 * Une stratégie commence par une hypothèse, pas par un génome.
 * Le LLM (optionnel) propose des concepts. Ghost les structure. Le juge, c’est l’évidence.
 */
class GhostHypothesis
{
    public const DRAFT = 'draft';

    public const TESTED = 'tested';

    public const SUPPORTED = 'supported';

    public const REFUTED = 'refuted';

    public const UNKNOWN = 'unknown';

    /**
     * Concepts hors récompense financière. Hook pour un modèle externe.
     *
     * @var array<string, string> concept → levier
     */
    public const CONCEPTS = [
        'réputation' => 'social',
        'progression' => 'activation',
        'responsabilité' => 'motivation',
        'rareté' => 'timing',
        'statut' => 'social',
        'coopération' => 'social',
        'défi' => 'motivation',
        'personnalisation' => 'activation',
        'reconnaissance' => 'social',
        'réciprocité' => 'social',
        'friction' => 'friction',
        'contenu' => 'content',
        'rétention' => 'retention',
        'acquisition' => 'acquisition',
        'récompense' => 'reward',
    ];

    /**
     * @param  array<string, mixed>  $world
     * @return array{objective:string, key:string, metric:string, baseline:float, levers:list<string>, target:string, why:array<string,string>}
     */
    public static function problem(string $objective, array $world = []): array
    {
        $m = mb_strtolower($objective);
        $target = 'all';
        if (preg_match('/nouveau|new.user|premi[eè]re/u', $m)) {
            $target = 'new_users';
        } elseif (preg_match('/avanc|fid[eè]l|r[eé]current/u', $m)) {
            $target = 'advanced';
        }

        if (preg_match('/particip|forum|salon|r[eé]pons/u', $m)) {
            return [
                'objective' => $objective,
                'key' => 'increase_participation',
                'metric' => 'participation',
                'baseline' => (float) ($world['participation'] ?? 0),
                'levers' => ['social', 'friction', 'content', 'timing', 'motivation'],
                'target' => $target,
                'why' => [
                    'social' => 'Le salon est le lieu de la participation.',
                    'friction' => 'Un paywall avant le premier mot coupe la prise de parole.',
                    'content' => 'Sans matière à discuter, pas de réponse.',
                    'timing' => 'Le moment de la question change le taux.',
                    'motivation' => 'La raison de parler n’est pas toujours une récompense.',
                ],
            ];
        }
        if (preg_match('/r[eé]tent|reviens|fid[eé]l/u', $m)) {
            return [
                'objective' => $objective,
                'key' => 'increase_retention',
                'metric' => 'completion',
                'baseline' => (float) ($world['completion'] ?? 0),
                'levers' => ['retention', 'social', 'content', 'timing'],
                'target' => $target,
                'why' => [
                    'retention' => 'Revenir n’est pas arriver.',
                    'social' => 'Une boucle sociale retient sans payer.',
                    'content' => 'La suite manque.',
                    'timing' => 'Trop tôt ou trop tard, ça lâche.',
                ],
            ];
        }
        if (preg_match('/acquisi|trafic|visite/u', $m)) {
            return [
                'objective' => $objective,
                'key' => 'increase_acquisition',
                'metric' => 'visits',
                'baseline' => (float) ($world['visits'] ?? 0),
                'levers' => ['acquisition', 'content', 'social'],
                'target' => $target,
                'why' => [
                    'acquisition' => 'Faire venir.',
                    'content' => 'Une raison de cliquer.',
                    'social' => 'Une raison d’inviter.',
                ],
            ];
        }

        return [
            'objective' => $objective,
            'key' => 'increase_completion',
            'metric' => 'completion',
            'baseline' => (float) ($world['completion'] ?? 0),
            'levers' => ['friction', 'activation', 'content', 'motivation'],
            'target' => $target,
            'why' => [
                'friction' => 'Moins d’étapes, plus de tenues.',
                'activation' => 'Le premier geste décide.',
                'content' => 'On termine ce qu’on comprend.',
                'motivation' => 'Sans enjeu, on part.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $problem
     * @param  array<string, mixed>  $world
     * @param  list<string>  $concepts  idées d’un modèle externe, optionnel
     * @return list<array<string, mixed>>
     */
    public static function raise(array $problem, array $world, array $concepts = []): array
    {
        $metric = $problem['metric'];
        $baseline = (float) ($problem['baseline'] ?? 0);
        $assoc = GhostWorldObserver::associations($world);
        $out = [];

        $primaryCause = $assoc[0]['cause'] ?? ($problem['levers'][0] ?? 'friction');
        $out[] = self::make(
            $world,
            $problem,
            sprintf(
                'Les nouveaux quittent après la première interaction (baseline %s).',
                self::pct($baseline)
            ),
            sprintf('La cause principale est %s.', GhostStrategy::label($primaryCause)),
            sprintf('Agir sur %s devrait bouger %s de 8 à 15 %% (prior).', GhostStrategy::label($primaryCause), $metric),
            0.62,
            'Le problème pourrait être le manque de motivation, pas '.$primaryCause.'.',
            [$primaryCause],
            $assoc[0] ?? null
        );

        $counterLever = in_array('motivation', $problem['levers'], true) ? 'motivation' : ($problem['levers'][1] ?? 'content');
        $out[] = self::make(
            $world,
            $problem,
            sprintf('Même observation : baseline %s.', self::pct($baseline)),
            sprintf('Contre-hypothèse : %s, pas %s.', GhostStrategy::label($counterLever), GhostStrategy::label($primaryCause)),
            'Si on mute la première et que rien ne bouge, la contre-hypothèse gagne.',
            0.48,
            'La première hypothèse pourrait quand même être vraie.',
            [$counterLever],
            null
        );

        foreach (self::ingestConcepts($concepts, $problem) as $h) {
            $out[] = $h;
        }

        return $out;
    }

    /**
     * Le modèle propose des mots. Ghost en fait des hypothèses structurées. Il ne les juge pas.
     *
     * @param  list<string>  $concepts
     * @param  array<string, mixed>  $problem
     * @return list<array<string, mixed>>
     */
    public static function ingestConcepts(array $concepts, array $problem): array
    {
        $out = [];
        foreach ($concepts as $c) {
            $key = mb_strtolower(trim((string) $c));
            $lever = self::CONCEPTS[$key] ?? null;
            if ($lever === null) {
                continue;
            }
            $out[] = self::make(
                [],
                $problem,
                'Concept proposé (extérieur). Pas encore une preuve.',
                'Mécanisme : '.$key.'.',
                'À structurer en génome, puis à tenter de réfuter.',
                0.4,
                'Ce concept peut être cosmétique.',
                [$lever],
                null
            );
        }

        return $out;
    }

    /**
     * Cherche à réfuter. Pas à confirmer.
     *
     * @param  array<string, mixed>  $h
     * @param  array<string, mixed>  $evidence  {observed:?float, prediction:float, n:int}
     * @return array<string, mixed>
     */
    public static function falsify(array $h, array $evidence): array
    {
        $obs = $evidence['observed'] ?? null;
        $pred = (float) ($evidence['prediction'] ?? 0);
        $n = (int) ($evidence['n'] ?? 0);
        $h['evidence'] = $evidence;
        if ($obs === null || $n < 2) {
            $h['status'] = self::UNKNOWN;
            $h['verdict'] = 'Pas assez d’observations du monde.';

            return $h;
        }
        $obs = (float) $obs;
        $h['status'] = self::TESTED;
        $surprise = abs($obs - $pred);
        $h['surprise'] = round($surprise, 4);
        if ($pred > 0 && $obs < 0) {
            $h['status'] = self::REFUTED;
            $h['verdict'] = 'Découverte négative. L’hypothèse est fausse dans ce contexte.';

            return $h;
        }
        if ($obs >= 0 && abs($obs - $pred) <= max(0.03, abs($pred) * 0.5)) {
            $h['status'] = self::SUPPORTED;
            $h['verdict'] = 'L’évidence ne réfute pas. Ce n’est pas une preuve définitive.';

            return $h;
        }
        if ($surprise >= 0.08) {
            $h['status'] = self::TESTED;
            $h['verdict'] = 'Surprise. L’écart prédiction/monde mérite une nouvelle hypothèse.';

            return $h;
        }
        $h['status'] = self::UNKNOWN;
        $h['verdict'] = 'Inconclusif.';

        return $h;
    }

    /**
     * @param  array<string, mixed>  $world
     * @param  array<string, mixed>  $problem
     * @param  list<string>  $levers
     * @param  array<string, mixed>|null  $assoc
     * @return array<string, mixed>
     */
    public static function make(
        array $world,
        array $problem,
        string $observation,
        string $hypothesis,
        string $prediction,
        float $confidence,
        string $counter,
        array $levers,
        ?array $assoc
    ): array {
        $code = 'HYP-'.str_pad((string) (abs(crc32($hypothesis.$prediction)) % 10000), 4, '0', STR_PAD_LEFT);

        return [
            'code' => $code,
            'observation' => $observation,
            'hypothesis' => $hypothesis,
            'prediction' => $prediction,
            'confidence' => $confidence,
            'counter_hypothesis' => $counter,
            'levers' => $levers,
            'metric' => $problem['metric'] ?? 'completion',
            'baseline' => (float) ($problem['baseline'] ?? 0),
            'status' => self::DRAFT,
            'association' => $assoc,
            'verdict' => null,
            'surprise' => null,
            'world_node' => $world['node_id'] ?? null,
        ];
    }

    private static function pct(float $n): string
    {
        return number_format($n * 100, 1, ',', ' ').' %';
    }
}
