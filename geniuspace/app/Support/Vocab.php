<?php

namespace App\Support;

/**
 * Vocabulaire public. Les mots internes (Node, edge, CCK, parent_of, graphe)
 * restent dans le code. L’UI n’affiche que des lieux, des preuves, des décisions.
 */
class Vocab
{
    public static function kind(string $kind): string
    {
        return match ($kind) {
            'company' => 'Maison',
            'job' => 'Offre',
            'character' => 'Personnage',
            'product' => 'Produit',
            'series', 'world' => 'Univers',
            'boutique_expert' => 'Galerie',
            'car' => 'Véhicule',
            'part' => 'Pièce',
            'person' => 'Carnet',
            default => 'Fiche',
        };
    }

    public static function parentPhrase(string $parentKind): string
    {
        return match ($parentKind) {
            'company' => 'Proposée par',
            'series', 'world', 'boutique_expert' => 'Fait partie de',
            'job' => 'Liée à l’offre',
            default => 'Dans',
        };
    }

    public static function childPhrase(string $childKind): string
    {
        return match ($childKind) {
            'job' => 'Offres ouvertes',
            'character' => 'Personnages',
            'product' => 'Dans la boutique',
            'company' => 'Maisons',
            'person' => 'Carnets',
            default => 'Fiches liées',
        };
    }

    public static function edgePhrase(string $kind, string $label = ''): string
    {
        if ($label !== '') {
            return $label;
        }

        return match ($kind) {
            'parent_of' => 'Fait partie de',
            'offers' => 'Offres ouvertes',
            'validated' => 'Épreuve validée',
            default => 'Lié à',
        };
    }

    /** Mots interdits dans le copy produit (tests + revue). */
    public static function banned(): array
    {
        return ['CCK', 'parent_of', 'LivingWorld', 'VeraHouse', 'node_tabs', 'GpNode'];
    }
}
