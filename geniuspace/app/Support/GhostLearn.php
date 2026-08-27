<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Apprentissage opérationnel. Pas de mémoire d'hallucination.
 * Faits = mémoire. Stratégies = apprentissage. Une hypothèse n'est pas un fait.
 */
class GhostLearn
{
    public const LADDER = ['unknown', 'observed', 'inferred', 'supported', 'verified', 'trusted'];

    public static function promote(string $life, string $event): string
    {
        if ($event === 'revoke') {
            return 'revoked';
        }
        if ($event === 'conflict') {
            return in_array($life, ['trusted', 'verified'], true) ? 'contradicted' : 'revoked';
        }
        if ($event === 'age') {
            return in_array($life, ['trusted', 'verified'], true) ? 'stale' : $life;
        }
        $i = array_search($life, self::LADDER, true);
        if ($i === false) {
            return $life;
        }
        $want = match ($event) {
            'see' => 1,
            'repeat' => 3,
            'prove' => 4,
            'confirm' => 5,
            default => $i,
        };

        return $i < $want ? self::LADDER[$want] : $life;
    }

    public static function mayRemember(string $source): bool
    {
        return match ($source) {
            'hallucination', 'hypothesis' => false,
            'inferred' => true,
            'explicit', 'system', 'human', 'verified' => true,
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $exec
     * @param  array<string, mixed>  $check
     */
    public static function afterTurn(GpNode $node, string $message, array $plan, array $exec, array $check, string $mode): void
    {
        $skill = (string) ($plan['skill'] ?? '');
        $ok = (bool) ($check['valid'] ?? true) && $mode !== 'verified-block';
        self::touchSkill($skill, $ok);

        $error = null;
        $correction = null;
        if ($mode === 'verified-block' || ! $ok) {
            $error = str_contains(implode(' ', $check['unsupported_claims'] ?? []), 'act_')
                ? 'act'
                : 'grounding';
            $correction = $error === 'act'
                ? 'Ne jamais écrire un grant. Orienter vers l’action humaine.'
                : 'Avant de citer un prix, le relire dans le coffre.';
        }
        $low = mb_strtolower($message);
        if (preg_match('/wikip[eé]dia|fandom\.|google|wiki pirate/u', $low)) {
            $error = 'crawl';
            $correction = 'Rester dans le pack du lieu. Jamais un wiki.';
        }

        if ($error) {
            self::fail($node, $message, $skill, $error, $correction ?? '');
            self::rule($error, $correction ?? '');
        }

        self::maybeCandidate($skill, $ok);
    }

    /**
     * FAILURE → CLASSIFY → GENERALIZE → RULE CANDIDATE → TEST AGAINST HISTORY → VALIDATE.
     *
     * @return list<array{when:string, then:string, from:int, validated:bool}>
     */
    public static function generalize(?GpNode $node = null): array
    {
        if (! Schema::hasTable('ghost_failures')) {
            return [];
        }
        $q = DB::table('ghost_failures');
        if ($node) {
            $q->where('node_id', $node->id);
        }
        $rows = $q->select('error_type', 'correction', DB::raw('count(*) as n'))
            ->groupBy('error_type', 'correction')
            ->havingRaw('count(*) >= 3')
            ->get();
        $out = [];
        foreach ($rows as $row) {
            $when = (string) $row->error_type;
            $then = (string) $row->correction;
            $validated = self::holdsAgainstHistory($when, $node);
            if ($validated) {
                self::rule($when, $then);
            }
            $out[] = [
                'when' => $when,
                'then' => $then,
                'from' => (int) $row->n,
                'validated' => $validated,
            ];
        }

        return $out;
    }

    private static function holdsAgainstHistory(string $when, ?GpNode $node): bool
    {
        if (! Schema::hasTable('ghost_experiences')) {
            return true;
        }
        $q = DB::table('ghost_experiences')->orderByDesc('id')->limit(40);
        if ($node) {
            $q->where('node_id', $node->id);
        }
        $ok = 0;
        $ko = 0;
        foreach ($q->get() as $r) {
            $payload = json_decode((string) $r->payload, true) ?: [];
            $valid = (bool) ($payload['valid'] ?? true);
            $valid ? $ok++ : $ko++;
        }
        if ($ok + $ko === 0) {
            return true;
        }

        return $ko >= $ok || $when === 'strategy_refuted' || $when === 'act' || $when === 'crawl';
    }

    /**
     * strategy → outcome → leçon → prochaine génération.
     *
     * @param  array<string, mixed>  $trial
     */
    public static function afterExperiment(string $nodeId, array $trial): void
    {
        $h = $trial['hypothesis'] ?? [];
        $status = $h['status'] ?? '';
        $node = GpNode::query()->find($nodeId);
        if (! $node) {
            return;
        }
        if ($status === GhostHypothesis::REFUTED) {
            $cause = $h['levers'][0] ?? 'unknown';
            self::fail(
                $node,
                (string) ($h['hypothesis'] ?? 'hypothèse'),
                'discover_strategy',
                'strategy_refuted',
                'Ne plus proposer '.$cause.' comme cause unique dans ce contexte.'
            );
            self::rule('strategy:'.$cause, 'Écarter '.$cause.' tant que la réfutation tient.');
        }
        if (($trial['surprise'] ?? 0) >= 0.08 && ($trial['observed'] ?? null) !== null) {
            self::rule(
                'strategy:surprise',
                'Écart prédiction/monde : relancer une hypothèse, ne pas amplifier la même.'
            );
        }
    }

    /**
     * @return list<string>
     */
    public static function strategyBans(string $nodeId): array
    {
        if ($nodeId === '' || ! Schema::hasTable('ghost_rules')) {
            return [];
        }
        $rows = DB::table('ghost_rules')->where('when_error', 'like', 'strategy:%')->get();
        $out = [];
        foreach ($rows as $r) {
            $lever = substr((string) $r->when_error, strlen('strategy:'));
            if ($lever !== '' && $lever !== 'surprise') {
                $out[] = $lever;
            }
        }

        return array_values(array_unique($out));
    }

    public static function fail(GpNode $node, string $task, string $skill, string $error, string $correction): void
    {
        if (! Schema::hasTable('ghost_failures')) {
            return;
        }
        try {
            DB::table('ghost_failures')->insert([
                'node_id' => $node->id,
                'session_id' => Grantor::guestId(),
                'task' => Str::limit($task, 240),
                'skill' => Str::limit($skill, 64),
                'error_type' => $error,
                'correction' => Str::limit($correction, 240),
                'severity' => $error === 'act' || $error === 'crawl' ? 'high' : 'medium',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // non bloquant
        }
    }

    private static function rule(string $when, string $then): void
    {
        if (! Schema::hasTable('ghost_rules') || $when === '') {
            return;
        }
        try {
            $id = 'R-'.$when;
            $row = DB::table('ghost_rules')->where('id', $id)->first();
            DB::table('ghost_rules')->updateOrInsert(
                ['id' => $id],
                [
                    'when_error' => $when,
                    'then_do' => $then,
                    'from_failures' => (int) ($row->from_failures ?? 0) + 1,
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // non bloquant
        }
    }

    private static function touchSkill(string $name, bool $ok): void
    {
        if (! Schema::hasTable('ghost_skill_stats') || $name === '') {
            return;
        }
        try {
            $row = DB::table('ghost_skill_stats')->where('name', $name)->first();
            $runs = (int) ($row->runs ?? 0) + 1;
            $version = (int) ($row->version ?? 1);
            if ($runs >= 50) {
                $version = max($version, 2);
            }
            if ($runs >= 200) {
                $version = max($version, 3);
            }
            if ($runs >= 500) {
                $version = max($version, 4);
            }
            DB::table('ghost_skill_stats')->updateOrInsert(
                ['name' => $name],
                [
                    'runs' => $runs,
                    'wins' => (int) ($row->wins ?? 0) + ($ok ? 1 : 0),
                    'version' => $version,
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // non bloquant
        }
    }

    private static function maybeCandidate(string $skill, bool $ok): void
    {
        if (! $ok || $skill !== 'match_job' || ! Schema::hasTable('ghost_skill_candidates')) {
            return;
        }
        try {
            $row = DB::table('ghost_skill_stats')->where('name', 'match_job')->first();
            $wins = (int) ($row->wins ?? 0);
            $runs = max(1, (int) ($row->runs ?? 0));
            if ($wins < 2) {
                return;
            }
            $existing = DB::table('ghost_skill_candidates')->where('name', 'verified_job_matching')->first();
            if ($existing && $existing->status !== 'pending') {
                return;
            }
            $proc = json_encode(['check_grants', 'list_neighbors', 'match_user_job', 'rank'], JSON_UNESCAPED_UNICODE);
            DB::table('ghost_skill_candidates')->updateOrInsert(
                ['name' => 'verified_job_matching'],
                [
                    'procedure' => $proc,
                    'rate' => $wins / $runs,
                    'samples' => $wins,
                    'status' => 'pending',
                    'updated_at' => now(),
                    'created_at' => $existing->created_at ?? now(),
                ]
            );
        } catch (\Throwable $e) {
            // non bloquant
        }
    }

    public static function approve(string $name): bool
    {
        if (! Schema::hasTable('ghost_skill_candidates')) {
            return false;
        }
        $row = DB::table('ghost_skill_candidates')->where('name', $name)->where('status', 'pending')->first();
        if (! $row) {
            return false;
        }
        DB::table('ghost_skill_candidates')->where('name', $name)->update([
            'status' => 'approved',
            'updated_at' => now(),
        ]);
        if (Schema::hasTable('ghost_skill_stats')) {
            DB::table('ghost_skill_stats')->updateOrInsert(
                ['name' => $name],
                ['runs' => $row->samples, 'wins' => $row->samples, 'version' => 1, 'updated_at' => now()]
            );
        }

        return true;
    }
}
