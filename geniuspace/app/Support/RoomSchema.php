<?php

namespace App\Support;

use App\Models\GpNode;

/** JSON-LD par salle — Google et les LLM ne voient pas un onglet, ils voient un type. */
class RoomSchema
{
    public static function graph(GpNode $node, string $tab, $tabs): array
    {
        $label = optional($tabs->firstWhere('key', $tab))->label ?? $tab;
        $url = url('/n/'.$node->slug.($tab && $tab !== 'vivre' ? '/'.$tab : ''));
        $type = match ($tab) {
            'forum' => 'DiscussionForumPosting',
            'classifieds', 'boutique', 'boutique_expert' => 'OfferCatalog',
            'videos' => 'ItemList',
            'guides' => 'Article',
            'agenda' => 'Event',
            'carte' => 'Place',
            default => 'CollectionPage',
        };
        $item = [
            '@'.'type' => $type,
            'name' => $label.' — '.$node->title,
            'url' => $url,
            'isPartOf' => ['@'.'type' => 'CreativeWork', 'name' => $node->title, 'url' => url('/n/'.$node->slug)],
        ];
        if ($tab === 'forum') {
            $item['headline'] = $node->title.' — forum';
            $item['about'] = $node->summary;
        }
        return $item;
    }
}
