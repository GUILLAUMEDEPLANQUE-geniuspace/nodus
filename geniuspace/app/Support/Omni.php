<?php

namespace App\Support;

use App\Models\GpNode;
use App\Models\Media;

/**
 * Omni-média : à un chapitre (prod 03:15, démo 0:03 si le fichier est court)
 * le cadre du player s’efface et le labo / mixer / tableau prend la place.
 * C’est le moat Labo / Scène / Arène — pas un timecode mort.
 */
class Omni
{
    /** @return list<array{at:int,kind:string,label:string,title:string,body:string,prompt:string}> */
    public static function beats(?Media $media, ?GpNode $node = null): array
    {
        if (! $media) {
            return [];
        }
        $canvas = $node ? Flagships::canvas($node) : '';
        $out = [];
        foreach (Chapters::parse($media->chapters) as $c) {
            $kind = self::kindFromName($c['name'], $canvas);
            if (! $kind) {
                continue;
            }
            $copy = self::copy($kind);
            $out[] = [
                'at' => (int) $c['startOffset'],
                'kind' => $kind,
                'label' => $c['name'],
                'title' => $copy['title'],
                'body' => $copy['body'],
                'prompt' => $copy['prompt'],
            ];
        }
        if (! $out && self::canvasKind($canvas)) {
            $kind = self::canvasKind($canvas);
            $copy = self::copy($kind);
            $out[] = [
                'at' => 195,
                'kind' => $kind,
                'label' => $copy['title'],
                'title' => $copy['title'],
                'body' => $copy['body'],
                'prompt' => $copy['prompt'],
            ];
        }

        return $out;
    }

    public static function kindFromName(string $name, string $canvas = ''): ?string
    {
        $n = mb_strtolower($name);
        foreach ([
            'labo' => 'labo|exo|éditeur|editeur|simulateur',
            'mixer' => 'stem|mixer|waveform|pressage',
            'tactique' => 'tactique|compos|tableau',
            'route' => 'route|arbre|patch',
            'cel' => 'cel|opening',
            'rush' => 'rush|daily|multi-cam|multicam',
            'sim' => 'épreuve|epreuve|consigne',
        ] as $kind => $pat) {
            if (preg_match('/'.$pat.'/u', $n)) {
                return $kind;
            }
        }

        return null;
    }

    public static function canvasKind(string $canvas): ?string
    {
        return match ($canvas) {
            'labo' => 'labo',
            'scene' => 'mixer',
            'arene' => 'tactique',
            'terrain' => 'route',
            'atelier' => 'cel',
            'plateau' => 'rush',
            'maison' => 'sim',
            default => null,
        };
    }

    /** @return array{title: string, body: string, prompt: string} */
    public static function copy(string $kind): array
    {
        return match ($kind) {
            'labo' => [
                'title' => 'Tu ne regardes plus. Tu fais.',
                'body' => 'Le cadre s’ouvre. Colle l’erreur, tiens l’exo, le tuteur tamponne.',
                'prompt' => 'Colle l’erreur (ex. undefined $node).',
            ],
            'mixer' => [
                'title' => 'Stems lockés',
                'body' => 'Le clip s’efface. Le mixer prend le cadre. Chaque fader est un fichier signé.',
                'prompt' => 'Règle les quatre pistes, puis reprends.',
            ],
            'tactique' => [
                'title' => 'Tableau tactique',
                'body' => 'Pause = compos. Clique un joueur, ouvre sa fiche. Le speaker commente.',
                'prompt' => 'Clique un numéro.',
            ],
            'route' => [
                'title' => 'Labo de route',
                'body' => 'La VOD s’efface. L’arbre et la fenêtre boss restent.',
                'prompt' => 'Note la fenêtre (ex. 0:42).',
            ],
            'cel' => [
                'title' => 'Couches du cel',
                'body' => 'L’opening s’efface. Le viewer pose les couches de peinture.',
                'prompt' => 'Nomme une couche.',
            ],
            'rush' => [
                'title' => 'Rushes lockés',
                'body' => 'Le daily s’efface. Multi-cam, rôle par rôle. L’AD ouvre.',
                'prompt' => 'Choisis un rôle.',
            ],
            'sim' => [
                'title' => 'Simulateur d’épreuve',
                'body' => 'La vidéo s’efface. Consigne, geste, preuve.',
                'prompt' => 'Décris le geste.',
            ],
            default => [
                'title' => 'Cadre ouvert',
                'body' => 'La vidéo s’efface. Le lieu prend le cadre.',
                'prompt' => '',
            ],
        };
    }
}
