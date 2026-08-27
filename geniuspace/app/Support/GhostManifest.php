<?php

namespace App\Support;

use App\Llm\CckCatalog;
use App\Models\GpNode;

/**
 * Ce que Ghost a le droit de faire ICI. Pas tout le système.
 * Ghost ne reçoit pas les opérations DENY : le manifeste les filtre avant la proposition.
 */
class GhostManifest
{
    /**
     * @param  list<array<string, mixed>>|null  $blocks
     * @return array<string, mixed>
     */
    public static function of(GpNode $node, ?array $blocks = null): array
    {
        $blocks = $blocks ?? GhostEdit::read($node);
        $gates = self::gates();

        return [
            'editor' => [
                'page' => $node->title,
                'capabilities' => array_merge($gates['read'], $gates['prepare'], $gates['act']),
                'catalog' => CckCatalog::capabilities(),
                'blocks' => array_map(fn ($b) => [
                    'id' => $b['id'],
                    'type' => $b['type'],
                    'field_key' => $b['key'] ?? null,
                    'label' => $b['label'],
                ], $blocks),
            ],
            'capabilities' => $gates,
        ];
    }

    /**
     * @return array{read: list<string>, prepare: list<string>, propose: list<string>, act: list<string>, deny: list<string>}
     */
    public static function gates(): array
    {
        $read = [];
        $prepare = [];
        $act = [];
        foreach (GhostAction::PERMS as $op => $meta) {
            if (in_array($op, GhostAction::DENIED, true)) {
                continue;
            }
            if ($meta['level'] === GhostAction::OBSERVE) {
                $read[] = $op;
            } elseif ($meta['level'] === GhostAction::PREPARE) {
                $prepare[] = $op;
            } else {
                $act[] = $op;
            }
        }

        return [
            'read' => $read,
            'prepare' => $prepare,
            'propose' => $prepare,
            'act' => $act,
            'deny' => GhostAction::DENIED,
        ];
    }

    public static function allows(string $op): bool
    {
        if ($op === '' || in_array($op, GhostAction::DENIED, true)) {
            return false;
        }

        return GhostAction::may($op);
    }
}
