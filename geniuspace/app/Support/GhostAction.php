<?php

namespace App\Support;

/**
 * GhostAction — le LLM propose, le moteur décide et exécute.
 *
 * Produit : observe / prepare / act, auto / confirm / deny.
 * Technique : observe / propose / authorize / execute / verify.
 */
class GhostAction
{
    public const OBSERVE = 'observe';

    public const PREPARE = 'prepare';

    public const ACT = 'act';

    public const AUTO = 'auto';

    public const CONFIRM = 'confirm';

    public const DENY = 'deny';

    /** Technique : candidate state transition (produit = PREPARE). */
    public const PROPOSE = 'propose';

    /** Technique : permission + policy + confirmation. */
    public const AUTHORIZE = 'authorize';

    /** Technique : state mutation. */
    public const EXECUTE = 'execute';

    /** Technique : observed transition validation. */
    public const VERIFY = 'verify';

    /**
     * @var list<string>
     */
    public const DENIED = ['order.refund', 'customer.delete', 'payment.modify'];

    /**
     * @var array<string, array{level:string, autonomy:string}>
     */
    public const PERMS = [
        'field.add' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'field.update' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'field.move' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'field.delete' => ['level' => self::ACT, 'autonomy' => self::CONFIRM],
        'media.insert' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'media.move' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'playlist.insert' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'tab.update' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'template.apply' => ['level' => self::PREPARE, 'autonomy' => self::CONFIRM],
        'campaign.create' => ['level' => self::PREPARE, 'autonomy' => self::AUTO],
        'campaign.launch' => ['level' => self::ACT, 'autonomy' => self::CONFIRM],
        'message.send' => ['level' => self::ACT, 'autonomy' => self::CONFIRM],
        'order.refund' => ['level' => self::ACT, 'autonomy' => self::DENY],
        'customer.delete' => ['level' => self::ACT, 'autonomy' => self::DENY],
        'payment.modify' => ['level' => self::ACT, 'autonomy' => self::DENY],
        'read_editor' => ['level' => self::OBSERVE, 'autonomy' => self::AUTO],
        'customers.segment' => ['level' => self::OBSERVE, 'autonomy' => self::AUTO],
        'orders.filter' => ['level' => self::OBSERVE, 'autonomy' => self::AUTO],
        'strategy.explore' => ['level' => self::OBSERVE, 'autonomy' => self::AUTO],
        'strategy.mutate' => ['level' => self::PREPARE, 'autonomy' => self::AUTO],
        'strategy.simulate' => ['level' => self::OBSERVE, 'autonomy' => self::AUTO],
        'strategy.promote' => ['level' => self::PREPARE, 'autonomy' => self::AUTO],
        'strategy.deploy' => ['level' => self::ACT, 'autonomy' => self::CONFIRM],
    ];

    /**
     * Produit → technique. PREPARE (produit) = PROPOSE (noyau).
     */
    public static function stageOf(array $action): string
    {
        $status = $action['status'] ?? 'preview';
        $autonomy = $action['autonomy'] ?? self::CONFIRM;
        $level = $action['level'] ?? self::OBSERVE;
        if ($autonomy === self::DENY || $status === 'blocked') {
            return self::DENY;
        }
        if ($status === 'undone') {
            return self::OBSERVE;
        }
        if (! empty($action['verification'])) {
            return self::VERIFY;
        }
        if ($status === 'applied') {
            return self::EXECUTE;
        }
        if ($level === self::OBSERVE) {
            return self::OBSERVE;
        }
        if ($autonomy === self::CONFIRM) {
            return self::AUTHORIZE;
        }
        if ($level === self::PREPARE) {
            return self::PROPOSE;
        }

        return self::AUTHORIZE;
    }

    public static function autonomyFor(string $op, array $extra = []): string
    {
        $base = self::PERMS[$op]['autonomy'] ?? self::CONFIRM;
        if ($base === self::DENY) {
            return self::DENY;
        }
        if (in_array($op, ['campaign.launch', 'message.send'], true)) {
            $vol = (int) ($extra['volume'] ?? 0);
            $disc = (int) ($extra['discount'] ?? 0);
            if ($vol < 100 && $disc <= 10) {
                return self::AUTO;
            }

            return self::CONFIRM;
        }

        return $base;
    }

    public static function levelFor(string $op): string
    {
        return self::PERMS[$op]['level'] ?? self::OBSERVE;
    }

    public static function may(string $op): bool
    {
        return self::autonomyFor($op) !== self::DENY;
    }

    public static function id(string $prefix = 'ga'): string
    {
        return $prefix.'-'.bin2hex(random_bytes(4));
    }

    /**
     * @param  list<array<string, mixed>>  $ops
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    public static function make(string $action, array $ops, array $blocks, string $line): array
    {
        $primary = $ops[0]['op'] ?? $action;
        $key = $action === 'template.apply' ? 'template.apply' : $primary;
        if (! self::may($key) && ! self::may($primary)) {
            return self::blocked('Refusé par le moteur.', $blocks);
        }

        return GhostActionContract::draft([
            'id' => self::id(),
            'action' => $action,
            'level' => self::levelFor($key),
            'autonomy' => self::autonomyFor($key),
            'ops' => $ops,
            'preview' => array_merge([$line], array_map([self::class, 'describe'], $ops)),
            'before' => array_column($blocks, 'id'),
            'status' => 'preview',
        ], $blocks);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    public static function blocked(string $why, array $blocks = []): array
    {
        return GhostActionContract::draft([
            'id' => self::id(),
            'action' => 'blocked',
            'level' => self::ACT,
            'autonomy' => self::DENY,
            'ops' => [],
            'preview' => [$why],
            'before' => array_column($blocks, 'id'),
            'status' => 'blocked',
            'result' => $why,
        ], $blocks);
    }

    /**
     * @param  array<string, mixed>  $op
     */
    public static function describe(array $op): string
    {
        $name = $op['op'] ?? '';

        return match ($name) {
            'field.add' => '+ '.($op['name'] ?? '').' ('.($op['type'] ?? '').')'.(isset($op['after']) ? ' après '.$op['after'] : ''),
            'field.move' => '↦ '.($op['target'] ?? '').(isset($op['after']) ? ' après '.$op['after'] : ''),
            'field.delete' => '− '.($op['target'] ?? ''),
            'field.update' => '~ '.($op['target'] ?? '').' = '.($op['value'] ?? ''),
            'media.insert' => '+ image '.($op['media_id'] ?? '').(isset($op['after']) ? ' après '.$op['after'] : ''),
            'playlist.insert' => '+ playlist '.($op['playlist_id'] ?? '').(isset($op['after']) ? ' après '.$op['after'] : ''),
            'campaign.create' => 'campagne '.($op['offer'] ?? '').' −'.($op['discount'] ?? 0).' %',
            'campaign.launch' => 'envoi '.($op['campaign_id'] ?? ''),
            'message.send' => 'message '.($op['kind'] ?? '').' × '.($op['volume'] ?? 0),
            default => $name,
        };
    }
}
