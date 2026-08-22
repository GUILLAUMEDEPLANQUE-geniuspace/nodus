<?php

namespace App\Llm;

/** 8 essentiels. Le reste = mode avancé (pro). */
class CckCatalog
{
    public static function all(): array
    {
        return [
            'text' => ['g' => 'Essentiel', 'label' => 'Texte', 'seo' => 'heading', 'pro' => false],
            'html' => ['g' => 'Essentiel', 'label' => 'HTML', 'seo' => 'articlebody', 'pro' => false],
            'image' => ['g' => 'Essentiel', 'label' => 'Image', 'seo' => 'image', 'pro' => false],
            'video' => ['g' => 'Essentiel', 'label' => 'Vidéo', 'seo' => 'video', 'pro' => false],
            'uploads' => ['g' => 'Essentiel', 'label' => 'Fichier', 'seo' => 'download', 'pro' => false],
            'digits' => ['g' => 'Essentiel', 'label' => 'Prix / nombre', 'seo' => 'prop', 'pro' => false],
            'child' => ['g' => 'Essentiel', 'label' => 'Fiche enfant', 'seo' => 'haspart', 'pro' => false],
            'geo' => ['g' => 'Essentiel', 'label' => 'Lieu', 'seo' => 'place', 'pro' => false],
            'textarea' => ['g' => 'Texte', 'label' => 'Long texte', 'seo' => 'article', 'pro' => true],
            'password' => ['g' => 'Texte', 'label' => 'Mot de passe', 'seo' => 'none', 'pro' => true],
            'email' => ['g' => 'Texte', 'label' => 'Email', 'seo' => 'contact', 'pro' => true],
            'url' => ['g' => 'Texte', 'label' => 'Lien', 'seo' => 'sameas', 'pro' => true],
            'telephone' => ['g' => 'Texte', 'label' => 'Téléphone', 'seo' => 'contact', 'pro' => true],
            'checkbox' => ['g' => 'Sélection', 'label' => 'Case à cocher', 'seo' => 'prop', 'pro' => true],
            'radio' => ['g' => 'Sélection', 'label' => 'Choix unique', 'seo' => 'prop', 'pro' => true],
            'select' => ['g' => 'Sélection', 'label' => 'Liste', 'seo' => 'prop', 'pro' => true],
            'multiselect' => ['g' => 'Sélection', 'label' => 'Choix multiple', 'seo' => 'keywords', 'pro' => true],
            'boolean' => ['g' => 'Sélection', 'label' => 'Oui / non', 'seo' => 'prop', 'pro' => true],
            'autocomplete' => ['g' => 'Sélection', 'label' => 'Liste auto', 'seo' => 'prop', 'pro' => true],
            'datetime' => ['g' => 'Date', 'label' => 'Date et heure', 'seo' => 'date', 'pro' => true],
            'gallery' => ['g' => 'Média', 'label' => 'Galerie', 'seo' => 'image', 'pro' => true],
            'audio' => ['g' => 'Média', 'label' => 'Audio', 'seo' => 'audio', 'pro' => true],
            'pay_download' => ['g' => 'Commerce', 'label' => 'Payant à télécharger', 'seo' => 'offer', 'pro' => true],
            'og' => ['g' => 'SEO', 'label' => 'Open Graph', 'seo' => 'og', 'pro' => true],
            'auto_meta' => ['g' => 'SEO', 'label' => 'Métadonnées auto', 'seo' => 'meta', 'pro' => true],
            'records' => ['g' => 'Relations', 'label' => 'Fiches liées', 'seo' => 'mentions', 'pro' => true],
            'status' => ['g' => 'Affichage', 'label' => 'Statut', 'seo' => 'prop', 'pro' => true],
            'readmore' => ['g' => 'Affichage', 'label' => 'Lire la suite', 'seo' => 'article', 'pro' => true],
            'parent' => ['g' => 'Relations', 'label' => 'Fiche parent', 'seo' => 'ispartof', 'pro' => true],
            'drip' => ['g' => 'Temps', 'label' => 'Contenu programmé', 'seo' => 'none', 'pro' => true],
            'signature' => ['g' => 'Identité', 'label' => 'Signature', 'seo' => 'none', 'pro' => true],
            'multilevel' => ['g' => 'Sélection', 'label' => 'Liste à niveaux', 'seo' => 'prop', 'pro' => true],
        ];
    }

    public static function simple(): array
    {
        return array_filter(self::all(), fn ($m) => empty($m['pro']));
    }

    public static function proposeFrom(string $text): array
    {
        $l = mb_strtolower($text);
        $map = [
            'video' => '/vidéo|video|film|holo/u',
            'image' => '/image|photo|portrait|cover/u',
            'digits' => '/prix|salaire|prime|€|usd/u',
            'geo' => '/lieu|adresse|ville|geo|carte/u',
            'uploads' => '/pdf|fichier|zip|blueprint/u',
            'email' => '/email|mail/u',
            'url' => '/https?:|site|url/u',
            'html' => '/wiki|lore|article/u',
            'child' => '/enfant|personnage|rôle/u',
        ];
        $out = [];
        foreach ($map as $type => $rx) {
            if (preg_match($rx, $l)) {
                $out[] = ['type' => $type, 'name' => self::all()[$type]['label']];
            }
        }
        if (! $out) {
            $out[] = ['type' => 'text', 'name' => 'Texte'];
        }
        return $out;
    }

    /**
     * Contrat de capacité pour Ghost. Pas un dump SQL.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function capabilities(): array
    {
        $out = [];
        foreach (self::all() as $type => $meta) {
            $fieldish = in_array($type, ['digits', 'select', 'email', 'checkbox', 'radio', 'boolean', 'datetime', 'telephone'], true);
            $needsMedia = in_array($type, ['image', 'video', 'gallery', 'audio'], true);
            $out[$type] = [
                'label' => $meta['label'],
                'can_insert' => true,
                'can_move' => true,
                'can_delete' => true,
                'requires' => $needsMedia ? ['media'] : [],
                'placement' => $fieldish ? 'field' : 'block',
                'value_type' => $type === 'digits' ? 'number' : null,
                'seo' => $meta['seo'] ?? 'none',
                'pro' => ! empty($meta['pro']),
            ];
        }

        return $out;
    }
}
