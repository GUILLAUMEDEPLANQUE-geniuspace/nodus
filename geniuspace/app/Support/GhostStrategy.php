<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Discovery de stratégies. Ghost invente, mute, recombine, estime, observe.
 * Il n’applique jamais tout seul : autonome sur la stratégie, jamais sur l’autorité.
 *
 * estimate() = prior. observe() = monde. Une formule n’est pas une expérience.
 */
class GhostStrategy
{
    /** @var list<string> */
    public const LEVERS = [
        'acquisition', 'activation', 'motivation', 'friction',
        'reward', 'social', 'timing', 'content', 'retention',
    ];

    /** @var list<string> */
    public const MUTATIONS = [
        'REMOVE', 'ADD', 'REVERSE', 'COMBINE', 'SEQUENCE',
        'CONDITION', 'AMPLIFY', 'MINIMIZE', 'PERSONALIZE',
        'DELAY', 'ACCELERATE',
    ];

    /** @var list<string> */
    public const STATUSES = [
        'untested', 'active', 'winner', 'loser', 'partial',
        'invalid', 'dangerous', 'context_dependent',
    ];

    public const EXPLORE_RATIO = 0.30;

    /** perf, cost, risk — base par levier. */
    private const BASE = [
        'acquisition' => [0.12, 0.18, 0.08],
        'activation' => [0.15, 0.10, 0.06],
        'motivation' => [0.14, 0.08, 0.07],
        'friction' => [0.18, 0.06, 0.05],
        'reward' => [0.16, 0.22, 0.12],
        'social' => [0.13, 0.09, 0.10],
        'timing' => [0.10, 0.05, 0.08],
        'content' => [0.11, 0.12, 0.04],
        'retention' => [0.17, 0.10, 0.06],
    ];

    public static function looksLike(string $message): bool
    {
        $m = mb_strtolower($message);

        return (bool) preg_match(
            '/strat[eé]g|participation|compl[eé]tion|taux d.activation|d[eé]couverte de strat|laboratoire de strat|ventes|conversion|sans augmenter le budget|chiffre d.affaires/u',
            $m
        );
    }

    /**
     * @param  array<string, mixed>  $world
     * @return array{objective:string, key:string, metric:string, baseline:float, levers:list<string>, target:string, why:array<string,string>}
     */
    public static function decompose(string $objective, array $world = []): array
    {
        return GhostHypothesis::problem($objective, $world);
    }

    /**
     * Combinatoire des leviers du problème. Pas trois paraphrases. Pas les 9 leviers par défaut.
     *
     * @param  list<string>|null  $levers
     * @param  list<string>  $banned
     * @return list<array<string, mixed>>
     */
    public static function generate(string $objective, int $budget = 24, ?array $levers = null, string $target = 'all', array $banned = []): array
    {
        $problem = self::decompose($objective);
        $levers = $levers ?? $problem['levers'];
        $levers = array_values(array_filter($levers, fn ($l) => in_array($l, self::LEVERS, true) && ! in_array($l, $banned, true)));
        if ($levers === []) {
            $levers = $problem['levers'];
        }
        $target = $target === 'all' ? $problem['target'] : $target;
        $out = [];
        foreach ($levers as $l) {
            $out[] = self::make($objective, [$l], [$l], $target);
        }
        $n = count($levers);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $pair = [$levers[$i], $levers[$j]];
                $out[] = self::make($objective, $pair, $pair, $target);
            }
        }
        if (isset($levers[0], $levers[2], $levers[min(4, $n - 1)])) {
            $triple = array_values(array_unique([$levers[0], $levers[2], $levers[min(4, $n - 1)]]));
            $out[] = self::make($objective, $triple, $triple, $target);
        }

        return array_slice($out, 0, max(3, $budget));
    }

    /**
     * @param  list<string>  $mechanisms
     * @param  list<string>  $sequence
     * @return array<string, mixed>
     */
    public static function make(string $objective, array $mechanisms, array $sequence, string $target = 'all', array $ops = []): array
    {
        $genome = self::genome([
            'objective' => self::objectiveKey($objective),
            'mechanisms' => array_values(array_unique($mechanisms)),
            'sequence' => array_values($sequence),
            'target' => $target,
            'mutations' => $ops,
        ]);
        $s = [
            'code' => self::code($genome),
            'objective' => $objective,
            'genome' => $genome,
            'status' => 'untested',
            'parent' => null,
            'generation' => $ops === [] ? 0 : 1,
        ];

        return array_merge($s, self::score($s));
    }

    /**
     * @param  array<string, mixed>  $g
     * @return array<string, mixed>
     */
    public static function genome(array $g): array
    {
        $mechs = array_values(array_intersect(self::LEVERS, $g['mechanisms'] ?? []));
        if ($mechs === []) {
            $mechs = ['activation'];
        }
        $seq = $g['sequence'] ?? $mechs;
        $seq = array_values(array_filter($seq, fn ($x) => in_array($x, self::LEVERS, true)));
        $ops = array_values(array_intersect(self::MUTATIONS, $g['mutations'] ?? []));

        return [
            'objective' => (string) ($g['objective'] ?? 'increase_completion'),
            'mechanisms' => $mechs,
            'sequence' => $seq ?: $mechs,
            'target' => in_array($g['target'] ?? 'all', ['all', 'new_users', 'advanced'], true)
                ? ($g['target'] ?? 'all') : 'all',
            'mutations' => $ops,
            'cost' => 0,
            'risk' => 0,
            'novelty' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $s
     * @param  list<array<string, mixed>>  $memory
     * @return array{performance:float, novelty:float, cost:float, risk:float, fitness:float}
     */
    public static function score(array $s, array $memory = []): array
    {
        $g = $s['genome'] ?? self::genome([]);
        $perf = 0.0;
        $cost = 0.0;
        $risk = 0.0;
        foreach ($g['mechanisms'] as $m) {
            $b = self::BASE[$m] ?? [0.1, 0.1, 0.1];
            $perf += $b[0];
            $cost += $b[1];
            $risk += $b[2];
        }
        $set = $g['mechanisms'];
        if (in_array('reward', $set, true) && in_array('social', $set, true)) {
            $perf += 0.07;
        }
        if (in_array('friction', $set, true) && in_array('activation', $set, true)) {
            $perf += 0.08;
        }
        if (in_array('timing', $set, true) && in_array('content', $set, true)) {
            $perf += 0.04;
        }
        if (in_array('acquisition', $set, true) && in_array('retention', $set, true) && ! in_array('activation', $set, true)) {
            $perf -= 0.05;
            $risk += 0.04;
        }
        $ops = $g['mutations'] ?? [];
        if (in_array('AMPLIFY', $ops, true)) {
            $perf += 0.05;
            $cost += 0.04;
            $risk += 0.03;
        }
        if (in_array('MINIMIZE', $ops, true)) {
            $cost = max(0.01, $cost - 0.06);
            $perf -= 0.01;
        }
        if (in_array('DELAY', $ops, true)) {
            $perf -= 0.02;
            $risk += 0.05;
        }
        if (in_array('ACCELERATE', $ops, true)) {
            $perf += 0.03;
            $cost += 0.05;
        }
        if (in_array('PERSONALIZE', $ops, true)) {
            $perf += 0.04;
            $cost += 0.03;
        }
        if (in_array('CONDITION', $ops, true)) {
            $risk = max(0.02, $risk - 0.04);
        }
        $n = max(1, count($set));
        $perf = $perf / sqrt($n);
        $cost = min(0.9, $cost);
        $risk = min(0.9, $risk);
        $novelty = self::novelty($g, $memory);
        $fitness = round($perf + $novelty - $cost - $risk, 4);

        return [
            'performance' => round($perf, 4),
            'novelty' => round($novelty, 4),
            'cost' => round($cost, 4),
            'risk' => round($risk, 4),
            'fitness' => $fitness,
        ];
    }

    /**
     * @param  array<string, mixed>  $genome
     * @param  list<array<string, mixed>>  $memory
     */
    public static function novelty(array $genome, array $memory): float
    {
        $me = $genome['mechanisms'] ?? [];
        if ($memory === []) {
            return round(0.45 + 0.05 * min(4, count($me)), 4);
        }
        $best = 0.0;
        foreach ($memory as $row) {
            $them = $row['genome']['mechanisms'] ?? [];
            $inter = count(array_intersect($me, $them));
            $union = count(array_unique(array_merge($me, $them))) ?: 1;
            $best = max($best, $inter / $union);
        }

        $ops = $genome['mutations'] ?? [];
        $boost = (in_array('DELAY', $ops, true) || in_array('REVERSE', $ops, true)) ? 0.08 : 0;

        return round(max(0.05, min(0.95, 1 - $best + $boost)), 4);
    }

    /**
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    public static function mutate(array $s, string $op, ?array $other = null): array
    {
        $op = in_array($op, self::MUTATIONS, true) ? $op : 'ADD';
        $g = $s['genome'] ?? self::genome([]);
        $me = $g['mechanisms'];
        $seq = $g['sequence'];
        $unused = array_values(array_diff(self::LEVERS, $me));
        $target = $g['target'];
        switch ($op) {
            case 'REMOVE':
                if (count($me) > 1) {
                    array_pop($me);
                    $seq = array_values(array_intersect($seq, $me)) ?: $me;
                }
                break;
            case 'ADD':
                $pick = self::pickUnused($unused, $g);
                if ($pick) {
                    $me[] = $pick;
                    $seq[] = $pick;
                }
                break;
            case 'REVERSE':
                $seq = array_reverse($seq);
                break;
            case 'COMBINE':
                if ($other) {
                    foreach ($other['genome']['mechanisms'] ?? [] as $x) {
                        if (! in_array($x, $me, true)) {
                            $me[] = $x;
                            $seq[] = $x;
                        }
                    }
                } elseif ($unused) {
                    $pick = self::pickUnused($unused, $g);
                    if ($pick) {
                        $me[] = $pick;
                        $seq[] = $pick;
                    }
                }
                break;
            case 'SEQUENCE':
                if (count($seq) > 1) {
                    $first = array_shift($seq);
                    $seq[] = $first;
                }
                break;
            case 'CONDITION':
                $target = $target === 'all' ? 'new_users' : $target;
                break;
            case 'PERSONALIZE':
                if (! in_array('activation', $me, true)) {
                    $me[] = 'activation';
                    $seq[] = 'activation';
                }
                break;
            case 'DELAY':
            case 'ACCELERATE':
            case 'AMPLIFY':
            case 'MINIMIZE':
                break;
        }
        $child = self::make(
            (string) ($s['objective'] ?? ''),
            $me,
            $seq,
            $target,
            array_values(array_unique(array_merge($g['mutations'] ?? [], [$op])))
        );
        $child['parent'] = $s['code'] ?? null;
        $child['generation'] = ((int) ($s['generation'] ?? 0)) + 1;

        return $child;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    public static function recombine(array $a, array $b): array
    {
        return self::mutate($a, 'COMBINE', $b);
    }

    /**
     * Prior. Ce n’est PAS une observation du monde.
     *
     * @param  array<string, mixed>  $s
     * @param  list<array<string, mixed>>  $memory
     * @return array<string, mixed>
     */
    public static function estimate(array $s, array $memory = []): array
    {
        $sc = self::score($s, $memory);
        $s = array_merge($s, $sc);
        $s['expected_gain'] = $sc['performance'];
        $s['predicted_gain'] = $sc['performance'];
        $s['observed_gain'] = null;
        $s['kind'] = 'estimate';
        $s['status'] = 'untested';

        return $s;
    }

    /**
     * Alias historique. N’observe pas. Ne pas lire observed_gain ici.
     *
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    public static function simulate(array $s, array $memory = []): array
    {
        return self::estimate($s, $memory);
    }

    /**
     * @param  list<float>  $series
     */
    public static function plateau(array $series, float $eps = 0.015): bool
    {
        if (count($series) < 4) {
            return false;
        }
        $tail = array_slice($series, -4);

        return (max($tail) - min($tail)) < $eps;
    }

    /**
     * @param  list<float>  $series
     */
    public static function mode(array $series): string
    {
        return self::plateau($series) ? 'explore' : 'exploit';
    }

    /**
     * @param  array<string, mixed>  $s
     * @return list<array{id:string, attack:string, mode:string}>
     */
    public static function challenge(array $s): array
    {
        $g = $s['genome'] ?? self::genome([]);
        $attacks = [];
        if (in_array('reward', $g['mechanisms'] ?? [], true) && ! in_array('retention', $g['mechanisms'] ?? [], true)) {
            $attacks[] = [
                'id' => 'ATTACK-1',
                'attack' => 'La récompense attire. Sans rétention, ça s’évapore chez les avancés.',
                'mode' => 'context_dependent',
            ];
        }
        if (($s['cost'] ?? 0) > 0.28) {
            $attacks[] = [
                'id' => 'ATTACK-2',
                'attack' => 'Le coût dépasse le seuil. La maison refuse le budget.',
                'mode' => 'dangerous',
            ];
        }
        if (in_array('social', $g['mechanisms'] ?? [], true) && ! in_array('friction', $g['mechanisms'] ?? [], true)) {
            $attacks[] = [
                'id' => 'ATTACK-3',
                'attack' => 'Boucle sociale sans friction réduite : abandon au premier pas.',
                'mode' => 'loser',
            ];
        }
        if ($attacks === []) {
            $attacks[] = [
                'id' => 'ATTACK-1',
                'attack' => 'Marche chez les nouveaux, douteux chez les avancés.',
                'mode' => 'context_dependent',
            ];
        }

        return $attacks;
    }

    /**
     * @param  array<string, mixed>  $s
     * @param  list<array{id:string, attack:string, mode:string}>  $attacks
     * @return array<string, mixed>
     */
    public static function harden(array $s, array $attacks): array
    {
        $child = $s;
        foreach ($attacks as $a) {
            if (($a['mode'] ?? '') === 'dangerous') {
                $child = self::mutate($child, 'MINIMIZE');
            } elseif (($a['mode'] ?? '') === 'context_dependent') {
                $child = self::mutate($child, 'CONDITION');
            } elseif (($a['mode'] ?? '') === 'loser') {
                $child = self::mutate($child, 'ADD');
            }
        }

        return $child;
    }

    /**
     * @return array<string, mixed>
     */
    public static function lab(string $objective, string $nodeId = '', bool $persist = false): array
    {
        $world = GhostWorldObserver::of($nodeId);
        $problem = self::decompose($objective, $world);
        $hyps = GhostHypothesis::raise($problem, $world);
        $memory = $nodeId !== '' ? self::memory($nodeId) : [];
        $banned = GhostLearn::strategyBans($nodeId);
        $pool = self::generate($objective, 24, $problem['levers'], $problem['target'], $banned);
        $history = [];
        foreach ($pool as $i => $s) {
            $pool[$i] = self::estimate($s, $memory);
            $history[] = (float) $pool[$i]['expected_gain'];
        }
        usort($pool, fn ($a, $b) => ($b['fitness'] <=> $a['fitness']));
        $mode = self::mode($history);
        $ops = $mode === 'explore'
            ? ['DELAY', 'REVERSE', 'COMBINE', 'PERSONALIZE']
            : ['AMPLIFY', 'SEQUENCE', 'MINIMIZE'];
        $top = array_slice($pool, 0, 3);
        foreach ($top as $t) {
            foreach ($ops as $op) {
                $child = self::estimate(self::mutate($t, $op, $top[1] ?? null), $memory);
                $pool[] = $child;
                $history[] = (float) $child['expected_gain'];
            }
        }
        if (isset($top[0], $top[1])) {
            $pool[] = self::estimate(self::recombine($top[0], $top[1]), $memory);
        }
        $seen = [];
        $uniq = [];
        foreach ($pool as $s) {
            $k = implode(',', $s['genome']['mechanisms']).'|'.implode(',', $s['genome']['mutations'] ?? []);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $uniq[] = $s;
        }
        $pool = $uniq;
        usort($pool, fn ($a, $b) => ($b['fitness'] <=> $a['fitness']));

        $trials = [];
        $hyp = $hyps[0] ?? GhostHypothesis::raise($problem, $world)[0];
        foreach (array_slice($pool, 0, 6) as $s) {
            $trial = GhostExperiment::evaluate(
                GhostExperiment::observe(
                    GhostExperiment::design($s, $hyp, $world, $nodeId),
                    $world
                )
            );
            $trials[] = $trial;
            if (($trial['strategy']['observed_gain'] ?? null) !== null) {
                foreach ($pool as $i => $row) {
                    if ($row['code'] === $s['code']) {
                        $pool[$i] = $trial['strategy'];
                    }
                }
            }
            if ($persist) {
                GhostExperiment::store($trial);
                GhostLearn::afterExperiment($nodeId, $trial);
            }
        }

        $hyps[0] = $trials[0]['hypothesis'] ?? $hyp;
        usort($pool, function ($a, $b) {
            $ae = $a['observed_gain'] === null ? -1 : 1;
            $be = $b['observed_gain'] === null ? -1 : 1;
            if ($ae !== $be) {
                return $be <=> $ae;
            }
            $ad = $a['discovery_score'] ?? $a['fitness'];
            $bd = $b['discovery_score'] ?? $b['fitness'];

            return $bd <=> $ad;
        });
        $best = $pool[0];
        $attacks = self::challenge($best);
        $by = fn (string $st) => array_values(array_filter($pool, fn ($s) => $s['status'] === $st));
        $rejected = array_merge($by('loser'), $by('invalid'), $by('dangerous'));
        $observedTrials = array_values(array_filter($trials, fn ($t) => $t['status'] === GhostExperiment::OBSERVED));
        $board = [
            'objective' => $objective,
            'problem' => $problem,
            'world' => $world,
            'hypotheses' => $hyps,
            'mode' => self::mode($history),
            'explored' => count($pool) + count($memory),
            'experiments' => count($trials),
            'experiments_observed' => count($observedTrials),
            'rejected' => count($rejected),
            'active' => count($by('untested')) + count($by('partial')) + count($by('active')),
            'winners' => count($by('winner')),
            'best' => $best,
            'attacks' => $attacks,
            'trials' => $trials,
            'pool' => array_slice($pool, 0, 12),
            'honesty' => 'La prédiction n’est pas une observation. Une tenue exige le monde.',
            'signature' => 'Autonome sur la stratégie. Jamais sur l’autorité.',
            'deploy' => self::deploy($best),
        ];
        if ($persist && $nodeId !== '') {
            self::storeBoard($nodeId, $board);
        }

        return $board;
    }

    /**
     * PREPARE. Rien n’est écrit dans le monde.
     *
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    public static function promote(array $s): array
    {
        return GhostActionContract::draft([
            'id' => GhostAction::id(),
            'action' => 'strategy.promote',
            'level' => GhostAction::PREPARE,
            'autonomy' => GhostAction::AUTO,
            'status' => 'preview',
            'ops' => [['op' => 'strategy.promote', 'code' => $s['code'] ?? '']],
            'preview' => ['Stratégie tenue en brouillon. Pas de déploiement.'],
        ]);
    }

    /**
     * ACT + CONFIRM. Ghost ne déploie pas tout seul.
     *
     * @param  array<string, mixed>  $s
     * @return array<string, mixed>
     */
    public static function deploy(array $s): array
    {
        return GhostActionContract::draft([
            'id' => GhostAction::id(),
            'action' => 'strategy.deploy',
            'level' => GhostAction::ACT,
            'autonomy' => GhostAction::CONFIRM,
            'status' => 'preview',
            'ops' => [['op' => 'strategy.deploy', 'code' => $s['code'] ?? '']],
            'preview' => ['Rien n’est déployé. Confirmation humaine requise.'],
        ]);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function memory(string $nodeId): array
    {
        if (! Schema::hasTable('ghost_strategies')) {
            return [];
        }
        $rows = DB::table('ghost_strategies')->where('node_id', $nodeId)->orderByDesc('id')->limit(80)->get();
        $out = [];
        foreach ($rows as $r) {
            $g = json_decode((string) $r->genome, true) ?: [];
            $out[] = [
                'code' => $r->code,
                'status' => $r->status,
                'genome' => $g,
                'fitness' => (float) $r->fitness,
                'observed_gain' => (float) $r->observed_gain,
            ];
        }

        return $out;
    }

    /**
     * Une tenue exige une observation. Une estimation n’est jamais un winner.
     *
     * @param  array<string, mixed>  $s
     */
    public static function classify(array $s): string
    {
        if (($s['risk'] ?? 0) > 0.35) {
            return 'dangerous';
        }
        if (! array_key_exists('observed_gain', $s) || $s['observed_gain'] === null) {
            return 'untested';
        }
        $obs = (float) $s['observed_gain'];
        if ($obs < 0) {
            return 'loser';
        }
        if ($obs >= 0.05 && ($s['risk'] ?? 1) < 0.22 && ($s['evidence'] ?? '') === 'world') {
            return 'winner';
        }
        if (($s['genome']['target'] ?? 'all') !== 'all') {
            return 'context_dependent';
        }

        return 'partial';
    }

    public static function label(string $lever): string
    {
        return match ($lever) {
            'acquisition' => 'acquisition',
            'activation' => 'activation',
            'motivation' => 'motivation',
            'friction' => 'friction',
            'reward' => 'récompense',
            'social' => 'social',
            'timing' => 'timing',
            'content' => 'contenu',
            'retention' => 'rétention',
            default => $lever,
        };
    }

    /**
     * @param  array<string, mixed>  $board
     */
    public static function storeBoard(string $nodeId, array $board): void
    {
        if (! Schema::hasTable('ghost_strategies')) {
            return;
        }
        foreach ($board['pool'] ?? [] as $s) {
            DB::table('ghost_strategies')->updateOrInsert(
                ['node_id' => $nodeId, 'code' => $s['code']],
                [
                    'objective' => $board['objective'],
                    'genome' => json_encode($s['genome'], JSON_UNESCAPED_UNICODE),
                    'status' => $s['status'],
                    'parent_code' => $s['parent'] ?? null,
                    'generation' => (int) ($s['generation'] ?? 0),
                    'fitness' => $s['fitness'] ?? 0,
                    'performance' => $s['performance'] ?? 0,
                    'novelty' => $s['novelty'] ?? 0,
                    'cost' => $s['cost'] ?? 0,
                    'risk' => $s['risk'] ?? 0,
                    'expected_gain' => $s['expected_gain'] ?? 0,
                    'observed_gain' => $s['observed_gain'] ?? 0,
                    'confidence' => $s['confidence'] ?? 0,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        if (Schema::hasColumn('ghost_strategies', 'evidence')) {
            foreach ($board['pool'] ?? [] as $s) {
                DB::table('ghost_strategies')->where('node_id', $nodeId)->where('code', $s['code'])->update([
                    'evidence' => $s['evidence'] ?? '',
                    'hypothesis_code' => $board['hypotheses'][0]['code'] ?? '',
                    'surprise' => $s['surprise'] ?? null,
                    'innovation' => $s['innovation'] ?? null,
                ]);
            }
        }
    }

    /**
     * @param  list<string>  $unused
     * @param  array<string, mixed>  $genome
     */
    private static function pickUnused(array $unused, array $genome): ?string
    {
        if ($unused === []) {
            return null;
        }
        $sig = abs(crc32(implode(',', $genome['mechanisms'] ?? []).'|'.implode(',', $genome['mutations'] ?? [])));

        return $unused[$sig % count($unused)];
    }

    private static function objectiveKey(string $objective): string
    {
        $m = mb_strtolower($objective);
        if (preg_match('/particip/u', $m)) {
            return 'increase_participation';
        }
        if (preg_match('/r[eé]tent/u', $m)) {
            return 'increase_retention';
        }

        return 'increase_completion';
    }

    /**
     * @param  array<string, mixed>  $genome
     */
    private static function code(array $genome): string
    {
        $sig = abs(crc32(json_encode($genome, JSON_UNESCAPED_UNICODE) ?: ''));

        return 'STR-'.str_pad((string) ($sig % 10000), 4, '0', STR_PAD_LEFT);
    }
}
