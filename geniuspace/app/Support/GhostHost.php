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
     * Payload visiteur. Ghost::reply() / GhostCore::think() restent entiers.
     * growth, belief, situation, critic, simulations : staff seulement.
     *
     * @param  array<string, mixed>  $out
     * @return array<string, mixed>
     */
    public static function publicSurface(array $out, GpNode $node): array
    {
        $keep = [
            'reply', 'citations', 'tools', 'actions', 'profile', 'mode',
            'skill', 'plan', 'verify', 'memory',
        ];
        $surface = [];
        foreach ($keep as $k) {
            if (array_key_exists($k, $out)) {
                $surface[$k] = $out[$k];
            }
        }
        $surface['host'] = Ghost::hostName($node);
        $surface['lieu'] = ['titre' => $node->title, 'slug' => $node->slug];

        if (isset($surface['memory']) && is_array($surface['memory'])) {
            $surface['memory'] = array_values(array_filter(array_map(function ($f) {
                if (! is_array($f)) {
                    return null;
                }
                $label = trim((string) ($f['label'] ?? ''));
                if ($label === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'value' => (string) ($f['value'] ?? ''),
                ];
            }, $surface['memory'])));
        }

        if (isset($surface['verify']) && is_array($surface['verify'])) {
            $surface['verify'] = [
                'valid' => (bool) ($surface['verify']['valid'] ?? true),
                'status' => (string) ($surface['verify']['status'] ?? 'known'),
            ];
        }

        return $surface;
    }
}
