<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Str;

/**
 * Cycle visiteur. Grounded + tribunal factuel. Pas le Core V7.
 * I-NO-ACT : aucune écriture graphe / grant / mémoire lourde.
 */
class GhostLite
{
    /**
     * @param  list<array{role:string,content:string}>  $history
     * @param  array<string,mixed>  $opts
     * @return array<string,mixed>
     */
    public static function reply(GpNode $node, string $message, array $history = [], array $opts = []): array
    {
        unset($history, $opts);
        $message = trim(Str::limit($message, 800));
        $ctx = Ghost::context($node);

        if (preg_match('/wikip[eé]dia|fandom\\.|google\\.com|internet entier|wiki pirate|chatgpt/u', mb_strtolower($message))) {
            return self::pack($node, [
                'reply' => 'Je n’ai pas cette preuve dans le coffre. Je ne sors pas du pack de ce lieu — pas de crawl, pas de wiki.',
                'citations' => [['label' => 'Pack du lieu', 'url' => '/n/'.$node->slug]],
                'actions' => [['label' => 'Fiches du lieu', 'href' => '/n/'.$node->slug]],
            ]);
        }

        if (GhostTribunal::looksFactual($message)) {
            $tri = GhostTribunal::answer($node, $message);
            if ($tri['ok'] && ($tri['answer'] ?? '') !== '') {
                return self::pack($node, [
                    'reply' => $tri['answer'],
                    'citations' => [['label' => $node->title, 'url' => '/n/'.$node->slug]],
                    'actions' => [['label' => $node->title, 'href' => '/n/'.$node->slug]],
                ]);
            }
            if (! $tri['ok'] && ($tri['refusal'] ?? '') !== '') {
                return self::pack($node, [
                    'reply' => $tri['refusal'],
                    'citations' => [['label' => $node->title, 'url' => '/n/'.$node->slug]],
                    'actions' => [['label' => 'Fiches', 'href' => '/n/'.$node->slug.'/personnages']],
                ]);
            }
        }

        $intent = Ghost::intent($message, $ctx);
        $grounded = Ghost::groundedReply($node, $message, $intent, $ctx, null);

        return self::pack($node, $grounded);
    }

    /**
     * @param  array{reply?:string,citations?:array,actions?:array}  $grounded
     * @return array<string,mixed>
     */
    private static function pack(GpNode $node, array $grounded): array
    {
        return [
            'reply' => (string) ($grounded['reply'] ?? ''),
            'citations' => $grounded['citations'] ?? [],
            'tools' => [],
            'actions' => $grounded['actions'] ?? [],
            'profile' => Ghost::profile($node),
            'mode' => 'lite',
            'skill' => 'observe',
            'plan' => [],
            'verify' => ['valid' => true, 'status' => 'known'],
            'memory' => [],
        ];
    }
}
