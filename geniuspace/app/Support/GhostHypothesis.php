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
        'réputation' => 'honneur',
        'progression' => 'epreuve',
        'responsabilité' => 'honneur',
        'rareté' => 'rarete',
        'statut' => 'honneur',
        'coopération' => 'salon',
        'défi' => 'epreuve',
        'personnalisation' => 'fiches',
        'reconnaissance' => 'salon',
        'réciprocité' => 'salon',
        'friction' => 'teaser',
        'contenu' => 'fiches',
        'carnet' => 'carnet',
        'rideau' => 'rideau',
        'salaire' => 'clarte_salaire',
        'délai' => 'delai',
        'épreuve' => 'epreuve',
        'certificat' => 'certificat',
        'plancher' => 'plancher',
        'fourchette' => 'fourchette',
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

        $profile = (string) ($world['profile'] ?? '');
        $family = GhostStrategy::family($profile !== '' ? $profile : null);

        if (preg_match('/salaire|d[eé]lai|[eé]preuve|recrut|mission|align/u', $m) || $profile === 'rh') {
            return [
                'objective' => $objective,
                'key' => 'increase_preuves',
                'metric' => 'preuves_tenues',
                'baseline' => (float) ($world['preuves_tenues'] ?? $world['grants'] ?? 0),
                'levers' => GhostStrategy::LEVERS_RH,
                'target' => $target,
                'why' => [
                    'delai' => 'Un délai écrit change qui postule.',
                    'honneur' => 'La fiabilité de la maison se lit sur l’offre.',
                    'clarte_salaire' => 'Sans salaire publié, le signal est mort.',
                    'epreuve' => 'L’épreuve tranche. Le CV non.',
                    'preuves' => 'Le carnet dit ce qui manque.',
                ],
            ];
        }
        if (preg_match('/particip|forum|salon|r[eé]pons|rideau|fiche/u', $m) || $profile === 'guide') {
            return [
                'objective' => $objective,
                'key' => 'increase_participation',
                'metric' => 'participation',
                'baseline' => (float) ($world['participation'] ?? 0),
                'levers' => GhostStrategy::LEVERS_GUIDE,
                'target' => $target,
                'why' => [
                    'salon' => 'Le salon est le lieu de la prise de parole.',
                    'fiches' => 'Sans matière, pas de réponse.',
                    'rideau' => 'Ce qui est caché ne se discute pas.',
                    'portes' => 'Une porte média ouvre un lieu, pas un timecode.',
                    'carnet' => 'Tenir une preuve donne une raison de revenir.',
                ],
            ];
        }
        if (preg_match('/ventes|conversion|chiffre|prix|plancher|certificat/u', $m) || $profile === 'marchand') {
            return [
                'objective' => $objective,
                'key' => 'increase_sales',
                'metric' => 'completion',
                'baseline' => (float) ($world['completion'] ?? 0),
                'levers' => GhostStrategy::LEVERS_SHOP,
                'target' => $target,
                'why' => [
                    'plancher' => 'Le plancher tient. Ghost ne le lâche pas.',
                    'fourchette' => 'La négociation vit dans min/max, pas dans un rabais inventé.',
                    'teaser' => 'Le teaser public, la suite au coffre.',
                    'certificat' => 'La relique sans certificat n’est pas une preuve.',
                    'rarete' => 'La rareté se lit au stock tenu, pas à un badge.',
                ],
            ];
        }

        return [
            'objective' => $objective,
            'key' => 'increase_completion',
            'metric' => 'completion',
            'baseline' => (float) ($world['completion'] ?? 0),
            'levers' => $family,
            'target' => $target,
            'why' => array_combine($family, array_map(fn ($l) => GhostStrategy::label($l), $family)) ?: [],
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
        $gaps = $world['gaps'] ?? [];
        uasort($gaps, fn ($a, $b) => (($b['size'] ?? 0) <=> ($a['size'] ?? 0)));
        $topLever = array_key_first($gaps) ?: ($problem['levers'][0] ?? 'fiches');
        $top = $gaps[$topLever] ?? ['size' => 0, 'label' => $topLever];
        $out = [];

        $out[] = self::make(
            $world,
            $problem,
            sprintf(
                'Le monde montre un trou « %s » (%.0f %% manquant). Baseline %s.',
                $top['label'] ?? GhostStrategy::label((string) $topLever),
                ((float) ($top['size'] ?? 0)) * 100,
                self::pct($baseline)
            ),
            sprintf('Agir sur %s fermera ce trou.', GhostStrategy::label((string) $topLever)),
            sprintf('Prior : %s bouge de 5 à 12 %% si le trou se ferme. Pas une observation.', $metric),
            0.55,
            sprintf('Le trou pourrait venir d’ailleurs que %s.', GhostStrategy::label((string) $topLever)),
            [(string) $topLever],
            $world['contrasts'][0] ?? null
        );

        $counter = $problem['levers'][1] ?? (array_keys($gaps)[1] ?? 'fiches');
        $out[] = self::make(
            $world,
            $problem,
            sprintf('Même lecture de monde. Baseline %s.', self::pct($baseline)),
            sprintf('Contre-hypothèse : %s, pas %s.', GhostStrategy::label((string) $counter), GhostStrategy::label((string) $topLever)),
            'Si on mute le premier levier et que rien ne bouge, la contre-hypothèse gagne.',
            0.45,
            'La première hypothèse pourrait quand même être vraie.',
            [(string) $counter],
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

    /**
     * Pour une croyance, produire les causes rivales. Distinguer. Chercher à réfuter.
     *
     * @return list<array{id:string, cause:string, hypothesis:string, distinguish:string, falsify:string}>
     */
    public static function rivals(string $claim): array
    {
        $m = mb_strtolower($claim);
        $family = GhostStrategy::LEVERS_GUIDE;
        if (preg_match('/prix|plancher|vente|fourchette|certificat/u', $m)) {
            $family = GhostStrategy::LEVERS_SHOP;
        } elseif (preg_match('/salaire|[eé]preuve|d[eé]lai|recrut|align/u', $m)) {
            $family = GhostStrategy::LEVERS_RH;
        }
        $causes = [];
        foreach ($family as $lever) {
            $causes[$lever] = 'Le trou vient de '.GhostStrategy::label($lever).'.';
        }
        $keys = array_keys($causes);
        $out = [];
        $i = 0;
        foreach ($causes as $cause => $hyp) {
            $next = $keys[($i + 1) % count($keys)];
            $out[] = [
                'id' => 'H'.($i + 1),
                'cause' => $cause,
                'hypothesis' => $hyp,
                'claim' => $claim,
                'distinguish' => 'Quelle évidence distinguerait '.$cause.' de '.$next.' ? Isoler '.$cause.', tenir le reste constant.',
                'falsify' => 'Quelle observation réfuterait « '.$hyp.' » ? Un delta nul quand on agit uniquement sur '.$cause.'.',
            ];
            $i++;
        }

        return $out;
    }

    private static function pct(float $n): string
    {
        return number_format($n * 100, 1, ',', ' ').' %';
    }
}
