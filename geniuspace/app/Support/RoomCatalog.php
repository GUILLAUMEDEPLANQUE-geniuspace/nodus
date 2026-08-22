<?php

namespace App\Support;

/** Salles du club. Accueil est toujours là. Le reste se coche. */
class RoomCatalog
{
    public static function groups(): array
    {
        return [
            'Vie du club' => [
                'forum' => ['Parler ensemble', 'Le garage : débats, entraide, comme un forum vivant'],
                'journal' => ['Le journal', 'Actus du club, sorties, récits'],
                'guilde' => ['Les membres', 'Trombinoscope, grades, qui est qui'],
                'agenda' => ['Agenda / sorties', 'Rassemblements, meets, dates'],
                'stories' => ['Stories', 'Bulles courtes, 15 secondes, le pulse du club'],
            ],
            'Fiches' => [
                'personnages' => ['Les fiches', 'Voitures, persos, pièces… une page chacun, graphe parent/enfant'],
                'collections' => ['Collections', 'Séries, gammes, flottes'],
            ],
            'Médias' => [
                'videos' => ['Vidéos', 'Essais, replays, holo-fiches SEO'],
                'audio' => ['Podcasts / radio', 'Émissions, interviews'],
                'gallery' => ['Galerie', 'Photos en grand, pas une grille molle'],
                'reliques' => ['Photos & fichiers', 'Le Drive du club'],
            ],
            'Savoir' => [
                'guides' => ['Guides', 'Tutos, mises à jour, wiki pratique'],
                'reviews' => ['Essais & avis', 'Ce qui est bon, ce qui casse'],
            ],
            'Commerce' => [
                'boutique' => ['Boutique', 'Pièces, prints, services'],
                'boutique_expert' => ['Boutique expert', 'Vitrine luxe : prix, stock, SEO Offer, paywall fichiers'],
                'classifieds' => ['Petites annonces', 'Entre membres, occasion'],
                'merch' => ['Merch', 'Tee-shirts, stickers, le club sur le capot'],
            ],
            'Lieu & jobs' => [
                'carte' => ['Carte', 'Garages, meets, points GPS'],
                'offres' => ['Offres / jobs', 'Si tu recrutes dans le club'],
                'epreuve' => ['Quêtes', 'Candidatures en scénario, pas un CV'],
            ],
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
}
