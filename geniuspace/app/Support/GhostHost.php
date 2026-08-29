<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * Surface publique de Ghost. Le Core (V7) orchestre et reste entier.
 * L’orbe visiteur ne reçoit que ce payload. Aucune écriture graphe / grant.
 */
class GhostHost
{
    /**
     * Payload visiteur. Le Core reste entier dans Ghost::reply().
     *
     * @param  array<string, mixed>  $out
     * @return array<string, mixed>
     */
    public static function publicSurface(array $out, GpNode $node): array
    {
        $keep = [
            'reply', 'citations', 'tools', 'actions', 'profile', 'mode',
            'goal', 'skill', 'plan', 'verify', 'memory', 'permission', 'maturity',
        ];
        $surface = [];
        foreach ($keep as $k) {
            if (array_key_exists($k, $out)) {
                $surface[$k] = $out[$k];
            }
        }
        $surface['host'] = Ghost::hostName($node);
        $surface['lieu'] = ['titre' => $node->title, 'slug' => $node->slug];

        return $surface;
    }
}
