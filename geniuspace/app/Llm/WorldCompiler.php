<?php

namespace App\Llm;

use App\Models\GpNode;

/**
 * Le monde naît VIDE. Aucun preset (pas de flotte Star Atlas, pas de Maya).
 * Le prompt est de l’intention. Les astres, le créateur (ou le LLM via propose) les pose un par un.
 */
class WorldCompiler
{
    public static function run(string $slug, string $prompt): array
    {
        $uni = GpNode::query()->where('slug', $slug)->firstOrFail();
        $uni->summary = $prompt;
        $uni->save();
        Toolbelt::seo([
            'node_id' => $uni->id,
            'title' => $uni->title.' | Geniuspace',
            'description' => $prompt ?: $uni->title,
        ]);
        return [
            'mode' => 'empty',
            'slug' => $slug,
            'spawned' => [],
            'hint' => 'Noyau seul. Dock = briques. LLM propose, ne remplit pas.',
        ];
    }

    /** Suggestions à partir DU texte du créateur — pas d’archétype figé. */
    public static function propose(string $slug, string $prompt): array
    {
        $bits = preg_split('/[,;\n]| et /u', $prompt) ?: [];
        $out = [];
        foreach ($bits as $raw) {
            $t = trim($raw);
            if (mb_strlen($t) < 3 || mb_strlen($t) > 48) {
                continue;
            }
            if (str_word_count($t) > 6) {
                continue;
            }
            $type = 'shop';
            $l = mb_strtolower($t);
            if (preg_match('/offre|job|poste|quête/u', $l)) {
                $type = 'job';
            } elseif (preg_match('/vidéo|fiche|film/u', $l)) {
                $type = 'video';
            } elseif (preg_match('/perso|character|héros/u', $l)) {
                $type = 'character';
            }
            $out[] = ['type' => $type, 'title' => $t];
        }
        return ['slug' => $slug, 'suggestions' => array_slice($out, 0, 12)];
    }
}
