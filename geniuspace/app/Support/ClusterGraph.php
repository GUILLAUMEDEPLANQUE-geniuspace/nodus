<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;

/** Hub / spoke depuis le graphe Engine. */
class ClusterGraph
{
    public static function of(GpNode $world): array
    {
        $hubUrl = url('/n/'.$world->slug);
        $indexes = [];
        $byKind = [
            'person' => 'personnage',
            'character' => 'personnage',
            'organization' => 'organisation',
            'place' => 'lieu',
            'technique' => 'jutsu',
            'arc' => 'arc',
        ];
        foreach (ElementCatalog::entities() as $id => $meta) {
            $byKind[$meta['kind']] = $id;
            $room = $meta['index'];
            $indexes[$room] = [
                'key' => $room,
                'label' => RoomCatalog::label($room),
                'url' => url('/n/'.$world->slug.'/'.$room),
                'indexable' => EntityFloor::roomHasContent($world, $room),
            ];
        }

        $spokes = [];
        $links = [];
        foreach (EntityFloor::children($world) as $child) {
            $ok = EntityFloor::indexable($world, $child);
            $entity = $byKind[$child->kind] ?? 'personnage';
            $meta = ElementCatalog::entities()[$entity] ?? ElementCatalog::entities()['personnage'];
            $url = url('/n/'.$world->slug.'/f/'.$child->slug);
            $spokes[] = [
                'id' => $child->id,
                'slug' => $child->slug,
                'title' => $child->title,
                'kind' => $child->kind,
                'entity' => $entity,
                'schema' => $meta['schema'],
                'url' => $url,
                'indexable' => $ok,
                'index' => $meta['index'],
            ];
            if ($ok) {
                $links[] = ['from' => $hubUrl, 'to' => $url, 'rel' => 'spoke'];
                $links[] = ['from' => $url, 'to' => $hubUrl, 'rel' => 'hub'];
                $idx = url('/n/'.$world->slug.'/'.$meta['index']);
                $links[] = ['from' => $url, 'to' => $idx, 'rel' => 'index'];
            }
        }

        $ids = collect($spokes)->pluck('id');
        if ($ids->isNotEmpty()) {
            $edges = Edge::query()->whereIn('from_id', $ids)->whereIn('to_id', $ids)->get();
            $byId = collect($spokes)->keyBy('id');
            foreach ($edges as $e) {
                $a = $byId[$e->from_id] ?? null;
                $b = $byId[$e->to_id] ?? null;
                if ($a && $b && $a['indexable'] && $b['indexable']) {
                    $links[] = ['from' => $a['url'], 'to' => $b['url'], 'rel' => $e->kind ?: 'related'];
                }
            }
        }

        return [
            'hub' => [
                'title' => $world->title,
                'url' => $hubUrl,
                'summary' => (string) ($world->summary ?: $world->subtitle),
            ],
            'indexes' => array_values($indexes),
            'spokes' => $spokes,
            'links' => $links,
        ];
    }
}
