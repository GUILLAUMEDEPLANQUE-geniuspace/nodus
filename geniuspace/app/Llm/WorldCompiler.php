<?php

namespace App\Llm;

use App\Models\GpNode;

/**
 * Compilateur de monde — les outils LLM dans l’ordre Star Atlas :
 * 1. archétype  2. schéma CCK  3. flotte d’astres  4. SEO sur chaque nœud.
 * Le créateur voit le log des tools. Grok appellera le même pipeline.
 */
class WorldCompiler
{
    public static function run(string $slug, string $prompt): array
    {
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $p = mb_strtolower($prompt);
        $arch = 'anime';
        if (str_contains($p, 'job') || str_contains($p, 'recrut') || str_contains($p, 'vera')) {
            $arch = 'vera';
        } elseif (str_contains($p, 'rwa') || str_contains($p, 'crypto') || str_contains($p, 'vaisseau') || str_contains($p, 'flotte') || str_contains($p, 'atlas') || str_contains($p, 'luxe')) {
            $arch = 'fleet';
        }
        $log = [];
        $uni->summary = $prompt;
        $uni->skin = $arch === 'vera' ? 'vera' : 'living';
        $uni->save();
        $log[] = ['tool' => 'seed_cck_schema', 'result' => Toolbelt::seedSchema($uni->id, $arch)];
        $log[] = ['tool' => 'write_seo', 'result' => Toolbelt::seo([
            'node_id' => $uni->id,
            'title' => $uni->title.' — univers spatial | Geniuspace',
            'description' => $prompt,
            'keywords' => $arch.', spatial, rwa, seo',
        ])];
        $fleet = match ($arch) {
            'vera' => [
                ['job', 'Lead Game Designer'], ['job', 'Ingénieur lore'], ['shop', 'Campus virtuel'],
                ['video', 'Brief culture-fit'], ['character', 'Recruteur Maya'],
            ],
            'fleet' => [
                ['crypto', 'Croiseur Orichalque'], ['crypto', 'Frégate Lumen'], ['shop', 'Hangar premium'],
                ['video', 'Teaser flotte 4K'], ['crypto', 'Blueprint tokenisé'], ['shop', 'Marketplace luxe'],
            ],
            default => [
                ['character', 'Capitaine'], ['character', 'Navigatrice'], ['shop', 'Relique'],
                ['video', 'Holo-fiche arc'], ['shop', 'Print limité'],
            ],
        };
        foreach ($fleet as $i => [$type, $title]) {
            $log[] = ['tool' => 'spawn_spatial_node', 'result' => Toolbelt::spawn([
                'slug' => $slug, 'type' => $type, 'title' => $title,
                'angle' => $i * 0.9, 'radius' => 55 + $i * 12,
            ])];
        }
        return ['archetype' => $arch, 'log' => $log, 'slug' => $slug];
    }
}
