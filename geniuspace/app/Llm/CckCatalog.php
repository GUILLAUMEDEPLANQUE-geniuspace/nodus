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
            'child' => ['g' => 'Essentiel', 'label' => 'Enfant (graphe)', 'seo' => 'haspart', 'pro' => false],
            'geo' => ['g' => 'Essentiel', 'label' => 'Lieu', 'seo' => 'place', 'pro' => false],
            'textarea' => ['g' => 'Texte', 'label' => 'Textarea', 'seo' => 'article', 'pro' => true],
            'password' => ['g' => 'Texte', 'label' => 'Password', 'seo' => 'none', 'pro' => true],
            'email' => ['g' => 'Texte', 'label' => 'Email', 'seo' => 'contact', 'pro' => true],
            'url' => ['g' => 'Texte', 'label' => 'URL', 'seo' => 'sameas', 'pro' => true],
            'telephone' => ['g' => 'Texte', 'label' => 'Telephone', 'seo' => 'contact', 'pro' => true],
            'checkbox' => ['g' => 'Sélection', 'label' => 'Checkbox', 'seo' => 'prop', 'pro' => true],
            'radio' => ['g' => 'Sélection', 'label' => 'Radio', 'seo' => 'prop', 'pro' => true],
            'select' => ['g' => 'Sélection', 'label' => 'Select', 'seo' => 'prop', 'pro' => true],
            'multiselect' => ['g' => 'Sélection', 'label' => 'Multiselect', 'seo' => 'keywords', 'pro' => true],
            'boolean' => ['g' => 'Sélection', 'label' => 'Boolean', 'seo' => 'prop', 'pro' => true],
            'autocomplete' => ['g' => 'Sélection', 'label' => 'List Autocomplete', 'seo' => 'prop', 'pro' => true],
            'datetime' => ['g' => 'Date', 'label' => 'DateTime', 'seo' => 'date', 'pro' => true],
            'gallery' => ['g' => 'Média', 'label' => 'Gallery', 'seo' => 'image', 'pro' => true],
            'audio' => ['g' => 'Média', 'label' => 'Audio', 'seo' => 'audio', 'pro' => true],
            'pay_download' => ['g' => 'Commerce', 'label' => 'Pay To Download', 'seo' => 'offer', 'pro' => true],
            'og' => ['g' => 'SEO', 'label' => 'Open Graph', 'seo' => 'og', 'pro' => true],
            'auto_meta' => ['g' => 'SEO', 'label' => 'Auto Metadata', 'seo' => 'meta', 'pro' => true],
            'records' => ['g' => 'Relations', 'label' => 'Records', 'seo' => 'mentions', 'pro' => true],
            'status' => ['g' => 'Display', 'label' => 'Status', 'seo' => 'prop', 'pro' => true],
            'readmore' => ['g' => 'Display', 'label' => 'ReadMore', 'seo' => 'article', 'pro' => true],
            'parent' => ['g' => 'Graphe', 'label' => 'Parent', 'seo' => 'ispartof', 'pro' => true],
            'drip' => ['g' => 'Temps', 'label' => 'Drip content', 'seo' => 'none', 'pro' => true],
            'signature' => ['g' => 'Identité', 'label' => 'Signature', 'seo' => 'none', 'pro' => true],
            'multilevel' => ['g' => 'Sélection', 'label' => 'Multilevel select', 'seo' => 'prop', 'pro' => true],
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
}
