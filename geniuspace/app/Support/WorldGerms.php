<?php

namespace App\Support;

/**
 * Cinq germes d’accueil. Le catalogue (10 flagships + ~50 démarrages) reste entier.
 */
class WorldGerms
{
    public const IDS = ['atelier-anime', 'terrain', 'vault', 'maison-rh', 'manga-hub'];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $out = [];
        foreach (self::IDS as $id) {
            $t = WorldTemplates::get($id);
            if ($t) {
                $out[] = $t;
            }
        }

        return $out;
    }
}
