<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Ghost propose une transition d’état. Le contrat la rend vérifiable.
 *
 * Produit (inchangé) : OBSERVE / PREPARE / ACT.
 * Technique          : OBSERVE / PROPOSE / AUTHORIZE / EXECUTE / VERIFY.
 *
 * Une action sans contrat autorisé n’écrit pas.
 */
class GhostActionContract
{
    /**
     * @param  array<string, mixed>  $action
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    public static function draft(array $action, array $blocks = [], array $ctx = []): array
    {
        $ops = $action['ops'] ?? [];
        $deny = GhostAction::DENIED;
        $allowed = array_values(array_filter(
            array_keys(GhostAction::PERMS),
            fn ($op) => ! in_array($op, $deny, true)
        ));

        $action['contract'] = [
            'intent' => $action['action'] ?? 'observe',
            'actor' => 'ghost',
            'world' => $ctx['world'] ?? null,
            'target' => $ops[0]['after'] ?? $ops[0]['target'] ?? ($ops[0]['key'] ?? null),
            'preconditions' => ['read_editor' => true, 'status' => $action['status'] ?? 'preview'],
            'authority' => $action['level'] ?? GhostAction::OBSERVE,
            'allowed_operations' => $allowed,
            'forbidden_operations' => $deny,
            'expected_state' => self::expect($ops),
            'evidence_requirements' => self::needs($ops),
            'autonomy' => $action['autonomy'] ?? GhostAction::CONFIRM,
            'confirmation_requirement' => ($action['autonomy'] ?? '') === GhostAction::CONFIRM,
            'before_state' => array_column($blocks, 'id') ?: ($action['before'] ?? []),
            'proposed_operations' => $ops,
            'observed_state' => $action['observed_state'] ?? null,
            'verification' => $action['verification'] ?? null,
            'result' => $action['result'] ?? null,
        ];
        $action['stage'] = GhostAction::stageOf($action);

        return $action;
    }

    /**
     * AUTHORIZE : permission + manifeste + confirmation. Pas d’écriture.
     *
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>|null  $manifest
     * @return array<string, mixed>
     */
    public static function authorize(array $action, ?array $manifest = null): array
    {
        $action = self::draft($action);
        $deny = $manifest['capabilities']['deny'] ?? GhostAction::DENIED;
        foreach ($action['ops'] ?? [] as $op) {
            $name = (string) ($op['op'] ?? '');
            if ($name === '') {
                continue;
            }
            if (in_array($name, $deny, true) || ! GhostManifest::allows($name)) {
                $blocked = GhostAction::blocked('Refusé par le contrat. Hors manifeste.');
                $blocked['contract'] = $action['contract'] ?? [];
                $blocked['contract']['verification'] = ['result' => GhostVerifier::FAIL, 'reason' => 'deny'];
                $blocked['stage'] = GhostAction::DENY;

                return $blocked;
            }
        }
        if (($action['status'] ?? '') === 'blocked') {
            $action['stage'] = GhostAction::DENY;

            return $action;
        }
        $action['stage'] = ($action['autonomy'] ?? '') === GhostAction::CONFIRM
            ? GhostAction::AUTHORIZE
            : (($action['level'] ?? '') === GhostAction::OBSERVE ? GhostAction::OBSERVE : GhostAction::PROPOSE);

        return $action;
    }

    /**
     * OBSERVE post-APPLY : l’état vu, pas l’état déclaré.
     *
     * @param  array<string, mixed>  $action
     * @param  list<array<string, mixed>>  $after
     * @return array<string, mixed>
     */
    public static function observe(array $action, array $after): array
    {
        $ids = array_column($after, 'id');
        $keys = array_column($after, 'key');
        $action['after'] = $action['after'] ?? $ids;
        $action['observed_state'] = $ids;
        $action['contract'] = $action['contract'] ?? [];
        $action['contract']['observed_state'] = ['ids' => $ids, 'keys' => $keys];
        $action['stage'] = GhostAction::EXECUTE;

        return $action;
    }

    /**
     * VERIFY : expected vs observed + claims vs evidence.
     * Ne change pas status (applied reste applied). stage = verify.
     *
     * @param  array<string, mixed>  $action
     * @param  list<array<string, mixed>>  $before
     * @param  list<array<string, mixed>>  $after
     * @return array<string, mixed>
     */
    public static function verifyTransition(array $action, array $before, array $after): array
    {
        $must = $action['contract']['expected_state']['must_contain'] ?? [];
        $seen = array_merge(array_column($after, 'key'), array_column($after, 'id'));
        $missing = [];
        foreach ($must as $key) {
            if ($key !== '' && ! in_array($key, $seen, true)) {
                $missing[] = $key;
            }
        }

        $claims = [];
        foreach ($action['preview'] ?? [] as $line) {
            $claims = array_merge($claims, GhostVerifier::claims((string) $line));
        }
        $evidence = GhostVerifier::evidenceFrom($after);
        $verdicts = array_map(fn ($c) => GhostVerifier::rule($c, $evidence), $claims);
        $result = GhostVerifier::overall($verdicts);
        if ($missing) {
            $result = GhostVerifier::FAIL;
        }

        $verification = [
            'result' => $result,
            'missing' => $missing,
            'claims' => $verdicts,
            'before_hash' => GhostProvenance::hash($before),
            'after_hash' => GhostProvenance::hash($after),
        ];
        $action['verification'] = $verification;
        $action['contract']['verification'] = $verification;
        $action['stage'] = GhostAction::VERIFY;

        return $action;
    }

    /**
     * @param  list<array<string, mixed>>  $ops
     * @return array{must_contain: list<string>}
     */
    private static function expect(array $ops): array
    {
        $must = [];
        foreach ($ops as $op) {
            $must[] = match ($op['op'] ?? '') {
                'field.add' => (string) ($op['key'] ?? Str::slug($op['name'] ?? '')),
                'media.insert' => (string) ($op['media_id'] ?? ''),
                'playlist.insert' => (string) ($op['playlist_id'] ?? ''),
                default => '',
            };
        }

        return ['must_contain' => array_values(array_filter($must))];
    }

    /**
     * @param  list<array<string, mixed>>  $ops
     * @return list<string>
     */
    private static function needs(array $ops): array
    {
        $needs = ['before_state'];
        foreach ($ops as $op) {
            if (in_array($op['op'] ?? '', ['campaign.create', 'campaign.launch', 'message.send'], true)) {
                $needs[] = 'volume';
            }
        }

        return array_values(array_unique($needs));
    }
}
