<?php

namespace App\Support;

/** Salles = produit. Chaque clé : label, hint, wow (ce que personne n’a), schema.org. */
class RoomCatalog
{
    public static function groups(): array
    {
        $g = [
            'Vie du club' => [
                'vivre' => ['Entrée', 'Le lieu, pas une home. Hero, hôte, passages.', 'CreativeWork'],
                'forum' => ['Parler ensemble', 'Holo-forum : Dive + Legacy SEO + live + relique in-thread', 'DiscussionForumPosting'],
                'journal' => ['Magazine', 'Moule chef-de-secteur : résumé, FAQPage, speakable, cluster', 'Blog'],
                'guilde' => ['Les membres', 'Passport cross-node : grades voyagent d’un univers à l’autre', 'Organization'],
                'agenda' => ['Agenda / sorties', 'Chaque date = Event + geo, indexable, pas un Facebook', 'Event'],
                'stories' => ['Stories', '15s indexées en Clip, digest Legacy le lendemain', 'VideoObject'],
            ],
            'Fiches' => [
                'personnages' => ['Les fiches', 'Fiches parent / enfant, publiques — l’encyclopédie du club', 'ItemList'],
                'collections' => ['Collections', 'Série → volumes enfants, une URL par gamme', 'CollectionPage'],
            ],
            'Médias' => [
                'videos' => ['Vidéos', 'Holo-fiche : chapitres, produits, Drive, auteur — YouTube n’a pas ça', 'VideoObject'],
                'audio' => ['Podcasts / radio', 'PodcastEpisode + chapitres, transcript indexé', 'PodcastSeries'],
                'gallery' => ['Galerie', 'ImageObject EXIF + maillage fiche, pas une grille Drive', 'ImageGallery'],
                'reliques' => ['Photos & fichiers', 'Drive signé, paywall, jeton — le fichier a une URL SEO', 'DataDownload'],
            ],
            'Savoir' => [
                'guides' => ['Guides', 'Wiki HowTo + bounty SEO : la guilde écrit, Google rank', 'HowTo'],
                'reviews' => ['Essais & avis', 'Review + note, liée à la fiche et à l’offre', 'Review'],
                'carnet' => ['Carnet', 'Preuves tenues, export JSON. Ça voyage d’un lieu à l’autre.', 'ProfilePage'],
            ],
            'Commerce' => [
                'boutique' => ['Boutique', 'Offer + split auteurs, 1-tap depuis le forum', 'OfferCatalog'],
                'boutique_expert' => ['Boutique expert', 'Vitrine luxe, RWA, crowd-goal, paywall fichiers', 'Store'],
                'classifieds' => ['Petites annonces', 'Offer + geo (Reims). Leboncoin sans le SEO, Facebook sans l’URL', 'Offer'],
                'merch' => ['Merch', 'Print + split 70/30 natif, Product group', 'Product'],
            ],
            'Lieu & jobs' => [
                'carte' => ['Carte', 'Place + Offer locaux, meets = Event géolocalisé', 'Place'],
                'offres' => ['Offres / jobs', 'JobPosting transparent + skill tree, pas une grille Indeed', 'JobPosting'],
                'epreuve' => ['Quêtes', 'Candidature = scénario 7 étapes, sac à dos = CV', 'AskAction'],
            ],
        ];
        $out = [];
        foreach ($g as $name => $rooms) {
            $out[$name] = [];
            foreach ($rooms as $key => $row) {
                $out[$name][$key] = [
                    'label' => $row[0],
                    'hint' => $row[1],
                    'wow' => $row[1],
                    'schema' => $row[2],
                ];
            }
        }
        return $out;
    }

    public static function packs(): array
    {
        return [
            'auto' => ['label' => 'Club auto', 'rooms' => ['forum', 'personnages', 'classifieds', 'carte', 'agenda', 'videos', 'gallery', 'journal']],
            'manga' => ['label' => 'Hub manga / série', 'rooms' => ['forum', 'personnages', 'videos', 'journal', 'guides', 'stories', 'boutique']],
            'boutique_expert' => ['label' => 'Boutique expert', 'rooms' => ['boutique_expert', 'gallery', 'videos', 'journal', 'classifieds', 'guides']],
            'jobs' => ['label' => 'Maison / jobs', 'rooms' => ['offres', 'epreuve', 'forum', 'videos', 'guides', 'guilde', 'journal']],
        ];
    }

    public static function all(): array
    {
        $out = [];
        foreach (self::groups() as $rooms) {
            $out += $rooms;
        }
        return $out;
    }

    public static function label(string $key): string
    {
        return self::all()[$key]['label'] ?? $key;
    }

    public static function keys(): string
    {
        return implode('|', array_merge(array_keys(self::all()), [
            'vivre', 'maison', 'salon', 'arbre', 'academie', 'blog', 'home',
            'savoirs', 'lexique', 'viviers', 'europe', 'passport', 'preuve',
            'pacte', 'ppqc', 'tarif', 'delais', 'marches', 'entreprises', 'maisons', 'metiers', 'apprendre',
        ]));
    }
}
