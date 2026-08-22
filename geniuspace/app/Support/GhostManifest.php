<?php

namespace App\Support;

use App\Llm\CckCatalog;
use App\Models\GpNode;

/**
 * Ce que Ghost a le droit de faire ICI. Pas tout le système.
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

        return [
            'editor' => [
                'page' => $node->title,
                'capabilities' => [
                    'field.add',
                    'field.update',
                    'field.move',
                    'media.insert',
                    'playlist.insert',
                    'template.apply',
                    'campaign.create',
                    'campaign.launch',
                ],
                'catalog' => CckCatalog::capabilities(),
                'blocks' => array_map(fn ($b) => [
                    'id' => $b['id'],
                    'type' => $b['type'],
                    'field_key' => $b['key'] ?? null,
                    'label' => $b['label'],
                ], $blocks),
            ],
        ];
    }
}
