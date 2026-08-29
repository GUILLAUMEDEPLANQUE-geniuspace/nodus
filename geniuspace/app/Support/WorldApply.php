<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

/** Recoupe un template : salles choisies + kinds de fiches. Ne crée aucune fiche. */
class WorldApply
{
    public static function shape(GpNode $node, array $opts): void
    {
        $rooms = $opts['rooms'] ?? null;
        if (is_array($rooms) && $rooms !== []) {
            self::retainRooms($node, $rooms);
        }
        $entities = $opts['entities'] ?? [];
        if (is_array($entities)) {
            self::enableEntities($node, $entities);
        }
    }

    public static function retainRooms(GpNode $node, array $keys): void
    {
        $allowed = array_keys(RoomCatalog::all());
        $keep = array_values(array_unique(array_merge(
            ['vivre', 'carnet'],
            array_filter($keys, fn ($k) => in_array($k, $allowed, true))
        )));
        DB::table('node_tabs')->where('node_id', $node->id)->whereNotIn('key', $keep)->delete();
        $have = DB::table('node_tabs')->where('node_id', $node->id)->pluck('key')->all();
        $sort = (int) DB::table('node_tabs')->where('node_id', $node->id)->max('sort');
        foreach ($keep as $key) {
            if (in_array($key, $have, true)) {
                continue;
            }
            $meta = RoomCatalog::all()[$key] ?? ['label' => $key];
            $sort++;
            DB::table('node_tabs')->insert([
                'node_id' => $node->id,
                'key' => $key,
                'label' => $meta['label'] ?? $key,
                'icon' => 'spark',
                'sort' => $sort,
                'enabled' => 1,
            ]);
        }
    }

    public static function enableEntities(GpNode $node, array $ids): void
    {
        foreach ($ids as $id) {
            $meta = ElementCatalog::entities()[$id] ?? null;
            if (! $meta || empty($meta['template'])) {
                continue;
            }
            FieldTemplates::apply($node->id, $meta['template']);
        }
        $val = implode(',', array_values(array_intersect($ids, array_keys(ElementCatalog::entities()))));
        $have = DB::table('cck_fields')->where('node_id', $node->id)->where('field_key', 'element_kinds')->exists();
        if ($have) {
            DB::table('cck_fields')->where('node_id', $node->id)->where('field_key', 'element_kinds')->update(['value' => $val]);
        } else {
            $sort = (int) DB::table('cck_fields')->where('node_id', $node->id)->max('sort') + 1;
            DB::table('cck_fields')->insert([
                'node_id' => $node->id,
                'name' => 'Types de fiches',
                'type' => 'text',
                'value' => $val,
                'sort' => $sort,
                'field_key' => 'element_kinds',
                'seo_title' => 'Types de fiches',
            ]);
        }
    }
}
