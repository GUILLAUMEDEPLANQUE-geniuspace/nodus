<?php

namespace App\Llm;

/**
 * Catalogue CCK — les types que le God Canvas, le Studio et le LLM partagent.
 * Un type = rendu public SEO + formulaire créateur + outil LLM.
 */
class CckCatalog
{
    public static function all(): array
    {
        return [
            'text' => ['g' => 'Texte', 'label' => 'Text', 'seo' => 'heading'],
            'textarea' => ['g' => 'Texte', 'label' => 'Textarea', 'seo' => 'article'],
            'html' => ['g' => 'Texte', 'label' => 'HTML', 'seo' => 'articlebody'],
            'password' => ['g' => 'Texte', 'label' => 'Password', 'seo' => 'none'],
            'email' => ['g' => 'Texte', 'label' => 'Email', 'seo' => 'contact'],
            'url' => ['g' => 'Texte', 'label' => 'URL', 'seo' => 'sameas'],
            'telephone' => ['g' => 'Texte', 'label' => 'Telephone', 'seo' => 'contact'],
            'checkbox' => ['g' => 'Sélection', 'label' => 'Checkbox', 'seo' => 'prop'],
            'radio' => ['g' => 'Sélection', 'label' => 'Radio', 'seo' => 'prop'],
            'select' => ['g' => 'Sélection', 'label' => 'Select', 'seo' => 'prop'],
            'multiselect' => ['g' => 'Sélection', 'label' => 'Multiselect', 'seo' => 'keywords'],
            'boolean' => ['g' => 'Sélection', 'label' => 'Boolean', 'seo' => 'prop'],
            'autocomplete' => ['g' => 'Sélection', 'label' => 'List Autocomplete', 'seo' => 'prop'],
            'datetime' => ['g' => 'Date', 'label' => 'DateTime', 'seo' => 'date'],
            'digits' => ['g' => 'Date', 'label' => 'Digits', 'seo' => 'prop'],
            'image' => ['g' => 'Média', 'label' => 'Image', 'seo' => 'image'],
            'gallery' => ['g' => 'Média', 'label' => 'Gallery', 'seo' => 'image'],
            'video' => ['g' => 'Média', 'label' => 'Video', 'seo' => 'video'],
            'audio' => ['g' => 'Média', 'label' => 'Audio', 'seo' => 'audio'],
            'uploads' => ['g' => 'Média', 'label' => 'Uploads', 'seo' => 'download'],
            'pay_download' => ['g' => 'Commerce', 'label' => 'Pay To Download', 'seo' => 'offer'],
            'og' => ['g' => 'SEO', 'label' => 'Open Graph', 'seo' => 'og'],
            'auto_meta' => ['g' => 'SEO', 'label' => 'Auto Metadata', 'seo' => 'meta'],
            'records' => ['g' => 'Relations', 'label' => 'Records', 'seo' => 'mentions'],
            'status' => ['g' => 'Display', 'label' => 'Status', 'seo' => 'prop'],
            'readmore' => ['g' => 'Display', 'label' => 'ReadMore', 'seo' => 'article'],
            'child' => ['g' => 'Graphe', 'label' => 'Child', 'seo' => 'haspart'],
            'parent' => ['g' => 'Graphe', 'label' => 'Parent', 'seo' => 'ispartof'],
            'drip' => ['g' => 'Temps', 'label' => 'Drip content', 'seo' => 'none'],
            'geo' => ['g' => 'Lieu', 'label' => 'Geolocation', 'seo' => 'place'],
            'signature' => ['g' => 'Identité', 'label' => 'Signature', 'seo' => 'none'],
            'multilevel' => ['g' => 'Sélection', 'label' => 'Multilevel select', 'seo' => 'prop'],
        ];
    }

    /** Schémas que le LLM pose selon l'archétype (Star Atlas / Vera / manga). */
    public static function schemaFor(string $archetype): array
    {
        return match ($archetype) {
            'vera' => [
                ['Titre du poste', 'text'], ['Salaire', 'digits'], ['Remote', 'boolean'],
                ['Épreuve', 'uploads'], ['Geo campus', 'geo'], ['Drip onboarding', 'drip'],
                ['OG Offre', 'og'], ['Parent maison', 'parent'], ['Quête enfant', 'child'],
            ],
            'rwa', 'fleet' => [
                ['Nom de l’actif', 'text'], ['Prix', 'digits'], ['Contrat', 'url'],
                ['Galerie vaisseau', 'gallery'], ['Teaser 4K', 'video'], ['OST', 'audio'],
                ['Pay to download blueprint', 'pay_download'], ['OG luxe', 'og'],
                ['Auto meta', 'auto_meta'], ['Geo hangar', 'geo'], ['Statut flotte', 'status'],
            ],
            default => [
                ['Arc', 'text'], ['Prime', 'digits'], ['Wiki HTML', 'html'],
                ['Portrait', 'image'], ['Galerie', 'gallery'], ['Holo-fiche', 'video'],
                ['Parent univers', 'parent'], ['Enfants', 'child'], ['OG', 'og'],
                ['Auto meta', 'auto_meta'], ['Geo', 'geo'],
            ],
        };
    }
}
