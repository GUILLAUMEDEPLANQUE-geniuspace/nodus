<?php

namespace App\Support;

/**
 * Bibliothèque d’éléments. Pack = preset. Instance publiée = URL.
 * On partage des kinds (composants), jamais le lore d’un monde.
 *
 * Le catalogue n’est pas un hub manga. Il couvre les 10 flagships,
 * les ~50 germes et les métiers absents du germe (crypto, immo, banque, droit).
 */
class ElementCatalog
{
    public const SHARE_COMPONENTS = 'components';

    public static function rooms(): array
    {
        return RoomCatalog::all();
    }

    /** Familles visibles dans /create — alignées sur WorldTemplates + Finance. */
    public static function families(): array
    {
        return [
            'fandom' => 'Fandom',
            'jeux' => 'Jeux',
            'collection' => 'Collection',
            'emploi' => 'Emploi',
            'formation' => 'Formation',
            'commerce' => 'Commerce',
            'pays' => 'Pays',
            'business' => 'Business',
            'creation' => 'Création',
            'finance' => 'Finance',
            'flagship' => 'Flagship',
        ];
    }

    /**
     * Kinds de fiches. Un kind peut servir plusieurs germes
     * (personnage = ninja, candidat, joueur, auteur, associé).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function entities(): array
    {
        return [
            'personnage' => self::kind('Personnage', 'character', 'Person', 'personnages', 'personnage', 'Fiche d’humain : perso, candidat, joueur, auteur, associé.', ['fandom', 'jeux', 'emploi', 'creation', 'flagship']),
            'organisation' => self::kind('Organisation', 'organization', 'Organization', 'personnages', 'organisation', 'Clan, label, cabinet, enseigne, protocole DAO, barreau.', ['fandom', 'emploi', 'business', 'finance']),
            'lieu' => self::kind('Lieu', 'place', 'Place', 'carte', 'lieu', 'Village, agence, PDV, préfecture, salle, territoire.', ['fandom', 'pays', 'commerce', 'finance']),
            'jutsu' => self::kind('Technique', 'technique', 'CreativeWork', 'guides', 'jutsu', 'Jutsu, sort, build, geste métier, méthode.', ['fandom', 'jeux', 'formation']),
            'arc' => self::kind('Arc', 'arc', 'CreativeWork', 'journal', 'arc', 'Arc, saison, tome, patch, promotion — curseur rideau.', ['fandom', 'jeux', 'flagship']),
            'produit' => self::kind('Produit', 'product', 'Product', 'boutique', 'produit', 'SKU, merch, pièce, AOP, photocards, print.', ['commerce', 'collection', 'creation']),
            'relique' => self::kind('Relique', 'product', 'Product', 'reliques', 'relique', 'Oeuvre unique, cel, certificat, RWA. Plancher tenu.', ['commerce', 'flagship']),
            'oeuvre' => self::kind('Oeuvre', 'creative', 'CreativeWork', 'gallery', 'oeuvre', 'Album, film, série photo, cel, livrable signé.', ['creation', 'flagship', 'fandom']),
            'offre' => self::kind('Offre / mission', 'job', 'JobPosting', 'offres', 'offre-tech', 'Mission chiffrée. Salaire en clair. Pas une grille Indeed.', ['emploi', 'business', 'formation']),
            'competence' => self::kind('Compétence', 'skill', 'Occupation', 'guides', 'competence', 'Geste tenu, stack, habilitation. Voyage avec le carnet.', ['emploi', 'formation']),
            'cours' => self::kind('Cours', 'course', 'Course', 'guides', 'cours', 'Module RNCP, promo, épreuve. Hub formation.', ['formation', 'emploi']),
            'evenement' => self::kind('Evénement', 'event', 'Event', 'agenda', 'evenement', 'Match, visite, drop, AG, concert, tournée.', ['creation', 'pays', 'collection', 'fandom']),
            'vehicule' => self::kind('Véhicule', 'auto', 'Vehicle', 'personnages', 'vehicule', 'Fiche auto / moto. Offre pièce à côté.', ['collection']),
            'rituel' => self::kind('Rituel / pratique', 'rite', 'HowTo', 'guides', 'rituel', 'Tirage, protocole ésotérique, geste tenu. HowTo indexable.', ['commerce']),
            'compte' => self::kind('Compte / ICP', 'account', 'Organization', 'personnages', 'compte', 'Compte cible B2B, enseigne, franchisee.', ['business']),
            'playbook' => self::kind('Playbook', 'playbook', 'HowTo', 'guides', 'playbook', 'Méthode commerciale, standard réseau, visite GMS.', ['business', 'formation']),
            'livrable' => self::kind('Livrable', 'deliverable', 'CreativeWork', 'guides', 'livrable', 'Mémo, audit, dossier conseil. CreativeWork daté.', ['business']),
            'actif' => self::kind('Actif / produit financier', 'asset', 'FinancialProduct', 'boutique', 'actif', 'Token, livret, assurance, fonds. Risque écrit.', ['finance']),
            'protocole' => self::kind('Protocole', 'protocol', 'SoftwareApplication', 'guides', 'protocole', 'Chaîne, contrat, audit, TVL. Pas un ticker orphelin.', ['finance']),
            'bien' => self::kind('Bien immobilier', 'listing', 'Accommodation', 'classifieds', 'bien', 'Mandat, surface, DPE, visite. URL par bien.', ['finance']),
            'mandat' => self::kind('Mandat', 'mandate', 'Contract', 'guides', 'mandat', 'Simple, exclusif, dates. Pas une page vide « mandats ».', ['finance']),
            'dossier' => self::kind('Dossier', 'case', 'LegalService', 'guides', 'dossier', 'Affaire, matière, barreau. Contenu tenu, pas un listing.', ['finance', 'business']),
            'acte' => self::kind('Acte', 'instrument', 'CreativeWork', 'guides', 'acte', 'Acte, contrat, statut. CreativeWork versionné.', ['finance']),
            'jurisprudence' => self::kind('Jurisprudence', 'ruling', 'Article', 'journal', 'jurisprudence', 'Décision commentée. Spoke du cabinet.', ['finance']),
        ];
    }

    /** Slots Chrome : pas d’URL tant qu’ils n’ont pas d’intention propre. */
    public static function slots(): array
    {
        return [
            'gallery-block' => ['label' => 'Galerie', 'plain' => 'Images dans une fiche ou une salle.', 'url' => false],
            'video-block' => ['label' => 'Vidéo', 'plain' => 'Player ancré, pas une page vide.', 'url' => false],
            'audio-block' => ['label' => 'Audio', 'plain' => 'Podcast / stems dans la fiche.', 'url' => false],
            'html-block' => ['label' => 'Bloc HTML', 'plain' => 'Module custom, noindex tant que slot.', 'url' => false],
            'city-3d' => ['label' => 'Ville 3D', 'plain' => 'Expérience. URL seulement si visite nommée.', 'url' => false],
            'game' => ['label' => 'Jeu', 'plain' => 'Canvas / script dans la salle.', 'url' => false],
            'animation' => ['label' => 'Animation', 'plain' => 'Motion Chrome, pas une landing.', 'url' => false],
            'fields' => ['label' => 'Champs', 'plain' => 'Détails de fiche (cck_fields).', 'url' => false],
            'map-block' => ['label' => 'Carte', 'plain' => 'Geo dans une fiche, pas /carte vide.', 'url' => false],
            'paywall' => ['label' => 'Paywall', 'plain' => 'Grant / plancher. Jamais une URL orpheline.', 'url' => false],
        ];
    }

    public static function packPreset(string $templateId): array
    {
        $t = WorldTemplates::get($templateId) ?? [];
        $group = (string) ($t['group'] ?? '');
        $rooms = $t['rooms'] ?? ['forum', 'personnages', 'journal', 'videos'];

        return [
            'rooms' => array_values(array_unique($rooms)),
            'entities' => self::entitiesFor($templateId, $group),
            'slots' => self::slotsFor($templateId, $group),
        ];
    }

    /** @return list<string> */
    public static function entitiesFor(string $templateId, string $group = ''): array
    {
        if ($group === '' && ($t = WorldTemplates::get($templateId))) {
            $group = (string) ($t['group'] ?? '');
        }

        return match (true) {
            in_array($templateId, ['manga-hub', 'anime-cour', 'serie-tv', 'atelier-anime', 'light-novel', 'webtoon', 'tokusatsu'], true) => ['personnage', 'organisation', 'lieu', 'jutsu', 'arc'],
            in_array($templateId, ['comics-bd', 'kpop', 'cine-club', 'club-lecture', 'plateau', 'scene'], true) => ['personnage', 'organisation', 'oeuvre', 'evenement', 'produit'],
            in_array($templateId, ['jrpg', 'mmorpg-guilde', 'gacha', 'speedrun', 'terrain'], true) => ['personnage', 'lieu', 'jutsu', 'arc', 'produit'],
            in_array($templateId, ['fps-esport', 'indie-dev', 'retro-pixel', 'sim-tycoon'], true) => ['personnage', 'lieu', 'playbook', 'produit'],
            in_array($templateId, ['club-auto', 'moto-cafe'], true) => ['vehicule', 'produit', 'lieu', 'personnage', 'evenement'],
            in_array($templateId, ['montres', 'sneakers', 'lego-afol', 'vinyle'], true) => ['produit', 'oeuvre', 'evenement', 'personnage'],
            in_array($templateId, ['vera-tech', 'vera-creative', 'maison-rh', 'vivier', 'freelance'], true) => ['offre', 'personnage', 'organisation', 'competence', 'lieu'],
            in_array($templateId, ['campus', 'bts-com', 'chef-secteur', 'labo'], true) => ['cours', 'competence', 'personnage', 'offre', 'playbook'],
            $templateId === 'esoterique' || $templateId === 'vault' => ['relique', 'rituel', 'produit', 'lieu', 'personnage'],
            in_array($templateId, ['pieces', 'leboncoin-club', 'merch-fan', 'galerie-rwa', 'pod-print', 'table'], true) => ['produit', 'relique', 'lieu', 'organisation'],
            $group === 'Pays' || $templateId === 'territoire' => ['lieu', 'organisation', 'personnage', 'evenement', 'produit'],
            in_array($templateId, ['b2b-sales', 'retail-gms', 'cabinet', 'saas-docs', 'asso'], true) => ['organisation', 'compte', 'playbook', 'livrable', 'offre', 'personnage'],
            in_array($templateId, ['photo-club', 'cuisine', 'club-sport', 'arene'], true) => ['personnage', 'oeuvre', 'evenement', 'lieu', 'produit'],
            $templateId === 'crypto-onchain' => ['actif', 'protocole', 'organisation', 'livrable', 'personnage'],
            $templateId === 'immo-agence' => ['bien', 'mandat', 'lieu', 'personnage', 'evenement'],
            $templateId === 'banque-fintech' => ['actif', 'produit', 'organisation', 'lieu', 'offre'],
            $templateId === 'cabinet-droit' => ['dossier', 'acte', 'jurisprudence', 'organisation', 'personnage'],
            $group === 'Finance' => ['actif', 'organisation', 'lieu', 'personnage'],
            $group === 'Flagship' => ['personnage', 'organisation', 'lieu', 'produit'],
            default => ['personnage', 'organisation', 'lieu'],
        };
    }

    /** @return list<string> */
    public static function slotsFor(string $templateId, string $group = ''): array
    {
        $base = ['gallery-block', 'video-block', 'fields'];

        return match (true) {
            in_array($templateId, ['manga-hub', 'atelier-anime', 'jrpg', 'terrain'], true) => array_merge($base, ['city-3d', 'animation']),
            $group === 'Pays' || $templateId === 'territoire' || $templateId === 'immo-agence' => array_merge($base, ['map-block']),
            in_array($templateId, ['vault', 'galerie-rwa', 'esoterique'], true) => array_merge($base, ['paywall', 'animation']),
            $templateId === 'scene' => array_merge($base, ['audio-block']),
            default => $base,
        };
    }

    public static function mayShare(string $elementId): bool
    {
        return isset(self::slots()[$elementId]) || isset(self::entities()[$elementId]);
    }

    public static function sharePolicy(): string
    {
        return self::SHARE_COMPONENTS;
    }

    public static function byFamily(string $family): array
    {
        return array_filter(
            self::entities(),
            fn (array $e) => in_array($family, $e['families'] ?? [], true)
        );
    }

    /** @return array<string, mixed> */
    private static function kind(string $label, string $kind, string $schema, string $index, string $template, string $plain, array $families): array
    {
        return compact('label', 'kind', 'schema', 'index', 'template', 'plain', 'families');
    }
}
