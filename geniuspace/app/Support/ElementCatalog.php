<?php

namespace App\Support;

/**
 * Bibliothèque d’éléments. Pack = preset. Instance publiée = URL.
 * On partage des kinds (composants), jamais le lore d’un monde.
 */
class ElementCatalog
{
    public const SHARE_COMPONENTS = 'components';

    public static function rooms(): array
    {
        return RoomCatalog::all();
    }

    public static function entities(): array
    {
        return [
            'personnage' => [
                'label' => 'Personnage',
                'kind' => 'character',
                'schema' => 'Person',
                'index' => 'personnages',
                'template' => 'personnage',
                'plain' => 'Une fiche, une intention, une URL.',
            ],
            'organisation' => [
                'label' => 'Organisation',
                'kind' => 'organization',
                'schema' => 'Organization',
                'index' => 'personnages',
                'template' => 'organisation',
                'plain' => 'Clan, équipe, village administratif.',
            ],
            'lieu' => [
                'label' => 'Lieu',
                'kind' => 'place',
                'schema' => 'Place',
                'index' => 'carte',
                'template' => 'lieu',
                'plain' => 'Village, salle, territoire.',
            ],
            'jutsu' => [
                'label' => 'Technique',
                'kind' => 'technique',
                'schema' => 'CreativeWork',
                'index' => 'guides',
                'template' => 'jutsu',
                'plain' => 'Jutsu, sort, build, geste.',
            ],
            'arc' => [
                'label' => 'Arc',
                'kind' => 'arc',
                'schema' => 'CreativeWork',
                'index' => 'journal',
                'template' => 'arc',
                'plain' => 'Arc, saison, tome — curseur rideau.',
            ],
        ];
    }

    /** Slots Chrome : pas d’URL tant qu’ils n’ont pas d’intention propre. */
    public static function slots(): array
    {
        return [
            'gallery-block' => ['label' => 'Galerie', 'plain' => 'Images dans une fiche ou une salle.', 'url' => false],
            'video-block' => ['label' => 'Vidéo', 'plain' => 'Player ancré, pas une page vide.', 'url' => false],
            'html-block' => ['label' => 'Bloc HTML', 'plain' => 'Module custom, noindex tant que slot.', 'url' => false],
            'city-3d' => ['label' => 'Ville 3D', 'plain' => 'Expérience. URL seulement si visite nommée.', 'url' => false],
            'game' => ['label' => 'Jeu', 'plain' => 'Canvas / script dans la salle.', 'url' => false],
            'animation' => ['label' => 'Animation', 'plain' => 'Motion Chrome, pas une landing.', 'url' => false],
            'fields' => ['label' => 'Champs', 'plain' => 'Détails de fiche (cck_fields).', 'url' => false],
        ];
    }

    public static function packPreset(string $templateId): array
    {
        $t = WorldTemplates::get($templateId);
        $rooms = $t['rooms'] ?? ['forum', 'personnages', 'journal', 'videos'];
        $entities = match (true) {
            in_array($templateId, ['manga-hub', 'anime-cour', 'serie-tv', 'atelier-anime'], true) => ['personnage', 'organisation', 'lieu', 'jutsu', 'arc'],
            in_array($templateId, ['jrpg', 'mmorpg-guilde', 'gacha'], true) => ['personnage', 'lieu', 'jutsu', 'arc'],
            default => ['personnage'],
        };

        return [
            'rooms' => array_values(array_unique($rooms)),
            'entities' => $entities,
            'slots' => ['gallery-block', 'video-block', 'fields'],
        ];
    }

    public static function mayShare(string $elementId): bool
    {
        return isset(self::slots()[$elementId]) || isset(self::entities()[$elementId]);
    }

    public static function sharePolicy(): string
    {
        return self::SHARE_COMPONENTS;
    }
}
