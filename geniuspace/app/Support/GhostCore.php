<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Boucle cognitive v7. Orchestrateur. Pas un god object métier.
 *
 * OBSERVE → SITUATION → MEMORY → HYPOTHESIS → PLAN → CRITIC → SIMULATE
 * → DECIDE → CONTRACT → AUTHORIZE → (EXECUTE jamais seul) → VERIFY → REFLECT → LEARN.
 *
 * Autonome sur la stratégie. Jamais sur l’autorité.
 */
class GhostCore
{
    public const LOOP = [
        'observe', 'situation', 'memory', 'hypothesize', 'plan', 'critic',
        'simulate', 'decide', 'authorize', 'verify', 'reflect', 'learn',
    ];

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    public static function think(GpNode $node, string $message, array $history = [], array $opts = []): array
    {
        $world = GhostWorldObserver::of((string) $node->id);
        $ctx = Ghost::context($node);
        $sit = GhostSituation::parse($message, $ctx);
        if (isset($opts['situation']) && is_array($opts['situation'])) {
            $sit = GhostSituation::ingest($opts['situation'], $sit);
        }
        $wm = GhostWorkingMemory::open($node, $sit, $world);
        $wm['episodes'] = GhostMemory::similarEpisodes($node, (string) $sit['goal']);
        $hyps = GhostHypothesis::raise(GhostHypothesis::problem($message, $world), $world);
        if (count($hyps) < 2) {
            $hyps = array_merge($hyps, GhostHypothesis::ingestConcepts(['friction', 'motivation', 'contenu'], GhostHypothesis::problem($message, $world)));
        }
        $rivals = GhostHypothesis::rivals((string) ($sit['goal'] ?? $message));
        $wm['hypotheses'] = $hyps;
        $wm['distinguish'] = GhostCritic::distinguish($hyps);
        $sitCritic = GhostCritic::situation($sit, $wm);

        $bag = GhostMemory::load($node);
        GhostMemory::ingest($node, $message, $bag);
        $plan = GhostPlanner::plan($node, $message, $ctx, $bag);
        $plan['situation'] = $sit;
        $attack = GhostCritic::plan($plan, $sit, $world);
        $replanned = false;
        if ($attack['severe'] || $attack['problems'] !== []) {
            $plan = GhostPlanner::replan($plan, $attack);
            $replanned = true;
        }
        $sims = GhostSimulator::of($plan, $sit, $wm['episodes']);
        $pickSim = GhostSimulator::recommend($sims);
        $self = GhostSelfModel::of($node);
        $advise = GhostSelfModel::advise($self, (string) ($plan['skill'] ?? ''), $pickSim);

        $out = self::act($node, $message, $history, $opts, $plan, $bag, $ctx);
        $trial = $out['lab']['trials'][0] ?? null;
        $reflection = GhostReflector::turn($node, $plan, [
            'tools' => $out['tools'] ?? [],
        ], $out['verify'] ?? ['valid' => true], $trial);
        $polarity = 'unknown';
        if (is_array($trial) && ($trial['observed'] ?? null) !== null) {
            $polarity = ($out['verify']['valid'] ?? true) ? 'support' : 'contradict';
        }
        GhostBelief::observe(
            $node,
            'skill:'.($plan['skill'] ?? 'unknown'),
            $polarity,
            (string) ($sit['goal'] ?? '')
        );
        $rules = GhostLearn::generalize($node);
        GhostMemory::save($node, array_merge($bag, [
            'goal' => $plan['goal'] ?? null,
            'constraints' => $plan['constraints'] ?? [],
            'hypotheses' => $hyps,
            'plan' => $plan['steps'] ?? [],
        ]));

        $out['situation'] = $sit;
        $out['working'] = [
            'goal' => $wm['goal'],
            'subgoals' => $wm['subgoals'],
            'known' => $wm['known'],
            'unknowns' => $wm['unknowns'],
            'stance' => $sit['stance'],
        ];
        $out['critic'] = $attack + ['situation' => $sitCritic, 'replanned' => $replanned];
        $out['simulations'] = $sims;
        $out['decision'] = $pickSim + ['advise' => $advise];
        $out['reflection'] = $reflection;
        $out['self'] = [
            'matrix' => $self['matrix'],
            'reliability' => $self['reliability'],
            'advise' => $advise,
            'cap' => $self['cap'],
        ];
        $out['belief'] = GhostBelief::get($node, 'skill:'.($plan['skill'] ?? 'unknown'));
        $out['layers'] = GhostMemory::layers($node);
        $out['distinguish'] = $wm['distinguish'];
        $out['rivals'] = $rivals;
        $out['rules_learned'] = $rules;
        $out['tempo'] = self::tempo();
        $out['loop'] = implode('.', self::LOOP);
        $out['growth'] = GhostGrowth::after($node, [
            'expected' => is_array($trial) ? ($trial['prediction'] ?? null) : null,
            'actual' => is_array($trial) ? ($trial['observed'] ?? null) : null,
            'trial' => $trial,
            'reflection' => $reflection,
        ]);
        if (! str_contains((string) $out['reply'], 'fiabilité') && in_array($sit['goal'] ?? '', ['discover_strategy', 'recover_failed_campaign'], true)) {
            $out['reply'] = rtrim((string) $out['reply']).' '.$advise;
        }

        return $out;
    }

    /**
     * Quatre temporalités. Ghost ne pense pas seulement 5 secondes.
     *
     * @return array<string, array{window:string, does:list<string>}>
     */
    public static function tempo(): array
    {
        return [
            'fast' => ['window' => '100ms–5s', 'does' => ['observe', 'reason', 'act-prepare']],
            'episode' => ['window' => 'minutes–hours', 'does' => ['task', 'experiment', 'result', 'reflect']],
            'learning' => ['window' => 'days', 'does' => ['aggregate', 'patterns', 'update-skills']],
            'strategic' => ['window' => 'weeks–months', 'does' => ['discover', 'retire-rules', 'new-capabilities']],
        ];
    }

    /**
     * Chemins existants (stratégie, éditeur, outils). Le Core ne les réécrit pas.
     *
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $working
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $opts
     * @return array<string, mixed>
     */
    private static function act(GpNode $node, string $message, array $history, array $opts, array $plan, array $working, array $ctx): array
    {
        if (($plan['skill'] ?? '') === 'recover_failed_campaign') {
            return self::recoverTurn($node, $message, $plan);
        }
        $factual = GhostTribunal::looksFactual($message)
            && ! GhostEdit::looksLike($message)
            && ! GhostBiz::route($message);
        if (! $factual && (GhostStrategy::looksLike($message) || ($plan['skill'] ?? '') === 'discover_strategy')) {
            return Ghost::strategyTurn($node, $message);
        }
        $editorCtx = $opts['editor_context'] ?? [];
        if (GhostBiz::route($message) || GhostEdit::looksLike($message)) {
            return Ghost::editorTurn($node, $message, is_array($editorCtx) ? $editorCtx : []);
        }
        $exec = GhostExecutor::run($node, $plan, $message, $ctx, $working);
        $ctx = GhostExecutor::merge($ctx, $exec);
        $intent = $plan['intent'] ?? Ghost::intent($message, $ctx);
        $grounded = Ghost::groundedReply($node, $message, $intent, $ctx, $exec['primary'] ?? null);
        $grounded = Ghost::withPick($grounded, $exec['pick'] ?? null, $working, $plan);
        $mode = 'grounded';
        $tribunal = null;
        if (GhostTribunal::looksFactual($message)) {
            $tribunal = GhostTribunal::answer($node, $message);
            if (! $tribunal['ok']) {
                $grounded['reply'] = $tribunal['refusal'];
                $grounded['citations'] = $tribunal['evidence'];
                $mode = 'tribunal-refuse';
            } else {
                $grounded['reply'] = $tribunal['answer'];
                $grounded['citations'] = $tribunal['evidence'];
                $mode = 'tribunal';
            }
        } else {
            $llm = Ghost::maybeLlm($node, $message, $history, $ctx, $grounded);
            if ($llm !== null) {
                $grounded['reply'] = $llm;
                $mode = 'llm+grounded';
            }
        }
        $exec['citations'] = array_values(array_merge($exec['citations'] ?? [], $grounded['citations'] ?? []));
        $check = GhostVerifier::check($grounded['reply'], $exec, $ctx);
        if (! $check['valid'] && $mode !== 'tribunal-refuse') {
            $jargon = array_filter(
                $check['unsupported_claims'] ?? [],
                fn ($c) => str_starts_with((string) $c, 'jargon:') || str_starts_with((string) $c, 'act_')
            );
            if ($mode !== 'tribunal' || $jargon !== []) {
                $grounded['reply'] = $check['safe_reply'];
                $mode = 'verified-block';
            }
        }
        GhostMemory::rememberTurn($node, $plan, $exec, $check);
        GhostLearn::afterTurn($node, $message, $plan, $exec, $check, $mode);
        Ghost::logTurn($node, $message, $grounded['reply'], $exec['tools'] ?? [], $mode);
        $citations = array_values(array_unique(array_merge($exec['citations'] ?? [], $grounded['citations'] ?? []), SORT_REGULAR));
        $actions = $grounded['actions'] ?? [];
        if ($actions === []) {
            $actions = $exec['actions'] ?? [];
        }
        if ($exec['pending'] ?? []) {
            $actions[] = ['label' => 'Confirmer l’action', 'href' => $ctx['lieu']['url'] ?? '/'];
        }

        return [
            'reply' => $grounded['reply'],
            'citations' => $citations,
            'tools' => $exec['tools'] ?? [],
            'actions' => $actions,
            'profile' => Ghost::profile($node),
            'mode' => $mode,
            'goal' => $plan['goal'] ?? null,
            'skill' => $plan['skill'] ?? null,
            'plan' => $plan['steps'] ?? [],
            'verify' => ['valid' => $check['valid'], 'status' => $check['status']],
            'memory' => GhostMemory::publicFacts($node),
            'permission' => $exec['ceiling'] ?? GhostSkills::OBSERVE,
            'maturity' => GhostMaturity::of($node),
            'tribunal' => $tribunal,
        ];
    }

    /**
     * Reprise de campagne. Inspecter, classer, simuler, préparer. Jamais envoyer.
     *
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private static function recoverTurn(GpNode $node, string $message, array $plan): array
    {
        $fails = [];
        if (Schema::hasTable('ghost_failures')) {
            $fails = DB::table('ghost_failures')
                ->where('node_id', $node->id)
                ->where(function ($q) {
                    $q->where('skill', 'like', '%campaign%')->orWhere('error_type', 'like', 'strategy%');
                })
                ->orderByDesc('id')
                ->limit(8)
                ->get()
                ->map(fn ($r) => ['skill' => $r->skill, 'error' => $r->error_type, 'correction' => $r->correction])
                ->all();
        }
        $episodes = GhostMemory::similarEpisodes($node, 'run_campaign');
        $sit = $plan['situation'] ?? GhostSituation::parse($message);
        $sims = GhostSimulator::of($plan, $sit, $episodes);
        $pick = GhostSimulator::recommend($sims);
        $ready = GhostSkills::ready('recover_failed_campaign', [
            'failed_campaign' => $fails !== [] ? true : null,
            'campaign' => $fails !== [] ? true : null,
        ]);
        $n = count($fails);
        $reply = $ready
            ? 'Campagne en échec lue ('.$n.' trace'.($n > 1 ? 's' : '').'). J’ai classé l’erreur, relu les épisodes, simulé trois reprises. Rien n’est renvoyé — confirmation humaine. '.$pick['why']
            : 'Je ne vois pas de campagne en échec dans ce lieu. Je prépare le diagnostic, je ne lance rien.';
        $exec = ['tools' => ['campaign.inspect', 'campaign.preview'], 'pending' => [['tool' => 'campaign.launch', 'level' => GhostSkills::ACT]]];
        $check = ['valid' => true, 'status' => 'known'];
        GhostMemory::rememberTurn($node, $plan, $exec, $check);
        GhostLearn::afterTurn($node, $message, $plan, $exec, $check, 'grounded');
        Ghost::logTurn($node, $message, $reply, $exec['tools'], 'grounded');

        return [
            'reply' => $reply,
            'citations' => [['label' => 'Salle d’épreuve', 'url' => '/n/'.$node->slug.'/ghost/gym']],
            'tools' => $exec['tools'],
            'actions' => [
                ['label' => 'Préparer la reprise', 'href' => '/n/'.$node->slug.'/ghost/lab'],
            ],
            'profile' => Ghost::profile($node),
            'mode' => 'grounded',
            'goal' => 'recover_failed_campaign',
            'skill' => 'recover_failed_campaign',
            'plan' => $plan['steps'] ?? [],
            'verify' => ['valid' => true, 'status' => 'known'],
            'memory' => GhostMemory::publicFacts($node),
            'permission' => GhostSkills::PREPARE,
            'maturity' => GhostMaturity::of($node),
            'simulations' => $sims,
            'decision' => $pick,
        ];
    }
}
