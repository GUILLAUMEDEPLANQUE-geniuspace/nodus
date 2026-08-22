<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * Les 10 templates flagship. Ce n’est pas une grille de skins.
 * Chaque id a : un secret (moteur), une surface (jamais le jargon),
 * un hôte (Ghost), des passages (neighbors), une preuve, un schema.org.
 */
class Flagships
{
    public static function has(?string $id): bool
    {
        return $id && isset(self::all()[$id]);
    }

    public static function of(?GpNode $node): ?array
    {
        if (! $node) {
            return null;
        }
        $id = (string) ($node->template ?? '');
        if (self::has($id)) {
            return self::all()[$id];
        }
        // Vera flagship historique.
        if (($node->slug ?? '') === 'vera' || $id === 'vera-tech') {
            return self::all()['maison-rh'];
        }
        if ($id === 'galerie-rwa' || ($node->slug ?? '') === 'lumen') {
            return self::all()['vault'];
        }

        return null;
    }

    public static function canvas(?GpNode $node): string
    {
        return self::of($node)['canvas'] ?? '';
    }

    public static function preset(string $id): string
    {
        return self::all()[$id]['preset'] ?? 'living';
    }

    /** Forme WorldTemplates (id, group, label, rooms, cck…). */
    public static function asTemplates(): array
    {
        $out = [];
        foreach (self::all() as $id => $f) {
            $out[] = [
                'id' => $id,
                'group' => 'Flagship',
                'label' => $f['label'],
                'pitch' => $f['pitch'],
                'innovation' => $f['innovation'],
                'kind' => $f['kind'],
                'skin' => $f['skin'],
                'primary' => $f['primary'],
                'hero' => $f['hero'],
                'schema' => $f['schema'],
                'rooms' => $f['rooms'],
                'cck' => $f['cck'],
                'arcs' => $f['arcs'],
                'title' => $f['title'],
                'seo' => $f['seo'],
                'flagship' => true,
            ];
        }

        return $out;
    }

    public static function chromePresets(): array
    {
        $out = [];
        foreach (self::all() as $id => $f) {
            $out[$id] = $f['chrome'];
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        $h = [
            'sea' => '/realms/sea-hero.jpg',
            's' => '/realms/studio-hero.jpg',
            'p' => '/realms/portal-hero.jpg',
            'm' => '/realms/205-meet.jpg',
            'd' => '/realms/205-dash.jpg',
            'g' => '/realms/205-garage.jpg',
            'a' => '/realms/actor-hero.jpg',
        ];

        return [
            'vault' => self::vault($h),
            'terrain' => self::terrain($h),
            'atelier-anime' => self::atelier($h),
            'territoire' => self::territoire($h),
            'maison-rh' => self::maison($h),
            'scene' => self::scene($h),
            'arene' => self::arene($h),
            'labo' => self::labo($h),
            'plateau' => self::plateau($h),
            'table' => self::table($h),
        ];
    }

    private static function vault(array $h): array
    {
        return [
            'id' => 'vault',
            'label' => 'Le Coffre',
            'vertical' => 'Boutique physique / reliques',
            'pitch' => 'La relique flotte. L’hôte négocie. Le passage mène à l’univers.',
            'innovation' => 'Ghost marchand + toile narrative + passages, pas une grille Shopify.',
            'secret' => 'Produit + champs + voisins + grant. L’hôte lit le lore, jamais un script FAQ.',
            'mechanic' => 'Canvas infini, scroll qui pivote l’œuvre, hotspots posés sur les champs, checkout sans quitter l’écrin. L’hôte a un plancher. Un passage (voisin) aspire vers l’univers parent. Une preuve stakée corrige une métadonnée.',
            'moat' => 'Shopify vend un SKU. Ici l’objet est un lieu : regarder / cliquer / débloquer / prouver / acquérir, même coffre. Google lit Product + isRelatedTo. L’acheteur parle à l’hôte à 3 h du matin.',
            'ghost' => [
                'name' => 'L’hôte',
                'role' => 'Agent-marchand',
                'wake' => 'L’auteur source au Japon. Je tiens le coffre. Offre dans la fourchette, je clos. Hors fourchette, je raconte pourquoi l’œuvre vaut ce prix.',
                'floor_key' => 'prix_plancher',
                'flex' => 0.10,
            ],
            'wormhole' => ['label' => 'Passage', 'edge' => 'parent_of', 'phrase' => 'Mène à'],
            'proof' => ['label' => 'Corriger une preuve', 'stake' => 50, 'what' => 'Métadonnée d’œuvre (épisode, douga, certificat)'],
            'omni' => 'À un chapitre, le making-of s’efface : viewer des couches de peinture / certificat.',
            'canvas' => 'vault',
            'kind' => 'product',
            'skin' => 'living',
            'preset' => 'vault',
            'primary' => '#d4af37',
            'hero' => $h['a'],
            'schema' => 'Product',
            'rooms' => ['boutique_expert', 'videos', 'gallery', 'journal', 'forum', 'reliques', 'personnages', 'carnet'],
            'cck' => [['Format', 'text'], ['Épisode', 'text'], ['Certificat', 'text'], ['Plancher', 'digits'], ['Rareté', 'select']],
            'arcs' => ['Sourcing', 'Cimaise', 'Collection'],
            'title' => '{name} — coffre, reliques, preuves | Geniuspace',
            'seo' => 'Œuvre unique, certificat, making-of. Fiche Product, relations, preuves communautaires.',
            'jsonld' => 'Product + Offer + isRelatedTo Person/CreativeWork. Jamais de page panier orpheline.',
            'chrome' => [
                'theme' => ['primary' => '#d4af37', 'bg' => '#050508', 'fg' => '#f7f1e1', 'muted' => '#a0aabf', 'hero' => $h['a'], 'hero_video' => '/media/atelier.mp4', 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_shop', 'Entrer dans le coffre', 'primary'], ['open_videos', 'Making-of', 'line']],
                    'shop_card' => [['add_cart', 'Acquérir l’œuvre', 'primary'], ['view_product', 'Voir l’écrin', 'line']],
                    'player_bar' => [['desc', 'Contexte', 'ghost'], ['shop', 'Coffre', 'ghost'], ['share', 'Partager', 'ghost']],
                    'player_paywall' => [['unlock', 'Ouvrir le coffre', 'primary', '', true, '', ['paywall_title' => 'La relique est lockée', 'paywall_body' => 'Le making-of et le certificat s’ouvrent ici.']]],
                    'player_overlay' => [['play', 'Regarder', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Acquérir l’œuvre', 'primary']],
                    'cart' => [['pay_card', 'Acquérir', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Accroche', 'body' => 'Pièce unique. Certificat. Making-of.', 'x' => 8, 'y' => 64, 'w' => 44, 'h' => 10, 'motion' => 'fade'],
                    ['kind' => 'image', 'label' => 'Relique', 'src' => $h['a'], 'x' => 58, 'y' => 18, 'w' => 30, 'h' => 48, 'target' => '', 'motion' => 'fade', 'delay' => 180],
                ],
            ],
        ];
    }

    private static function terrain(array $h): array
    {
        return [
            'id' => 'terrain',
            'label' => 'Le Terrain',
            'vertical' => 'Jeux vidéo',
            'pitch' => 'Le patch est un curseur. Le build est une épreuve. Le loot est une relique.',
            'innovation' => 'Wiki vivant + labo in-player. Pas un Fextralife figé au patch d’il y a six mois.',
            'secret' => 'VideoGame + quêtes + skill tree (champs) + voisins (boss → arme → zone). Le patch est un arc.',
            'mechanic' => 'HUD de terrain : carte, build, raid. Un chapitre de VOD pause et ouvre le labo (simulateur, arbre, route speedrun). L’hôte « maître du donjon » répond hors-ligne sur les rates et le meta. Un warp mène de l’arme à la boutique d’un autre créateur.',
            'moat' => 'Fandom dump. Ici chaque quête a une URL, chaque build une preuve, chaque loot un coffre. Google lit VideoGame + HowTo + VideoObject chapitré. Les guildes quittent Discord parce que le terrain est indexable.',
            'ghost' => [
                'name' => 'Maître du donjon',
                'role' => 'Meta-guide',
                'wake' => 'Le patch a bougé. Je tiens les rates et les routes. Pose le boss, je te sors la fenêtre et le loot.',
                'floor_key' => '',
                'flex' => 0,
            ],
            'wormhole' => ['label' => 'Warp', 'edge' => 'parent_of', 'phrase' => 'Mène à la zone'],
            'proof' => ['label' => 'Valider un drop', 'stake' => 25, 'what' => 'Rate, patch, loot de boss'],
            'omni' => 'VOD → pause → labo (arbre, éditeur de route, simulateur).',
            'canvas' => 'terrain',
            'kind' => 'series',
            'skin' => 'living',
            'preset' => 'terrain',
            'primary' => '#00ffc0',
            'hero' => $h['p'],
            'schema' => 'VideoGame',
            'rooms' => ['vivre', 'forum', 'personnages', 'videos', 'guides', 'reliques', 'guilde', 'journal', 'agenda', 'carnet'],
            'cck' => [['Plateforme', 'text'], ['Patch', 'text'], ['Classe', 'text'], ['Difficulté', 'select']],
            'arcs' => ['Prologue', 'Mid-game', 'Endgame', 'Post-game'],
            'title' => '{name} — builds, quêtes, terrain | Geniuspace',
            'seo' => 'VideoGame vivant : quêtes, builds, VOD chapitrées, loot comme reliques.',
            'jsonld' => 'VideoGame + HowTo (build) + VideoObject + ItemList loot.',
            'chrome' => [
                'theme' => ['primary' => '#00ffc0', 'bg' => '#04110c', 'fg' => '#e8fff6', 'muted' => '#7aa394', 'hero' => $h['p'], 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_roster', 'Le roster', 'primary'], ['open_videos', 'VOD', 'line']],
                    'shop_card' => [['add_cart', 'Prendre le loot', 'primary'], ['view_product', 'Fiche', 'line']],
                    'player_bar' => [['chap', 'Chapitres', 'ghost'], ['desc', 'Build', 'ghost'], ['shop', 'Loot', 'ghost']],
                    'player_paywall' => [['unlock', 'Entrer en raid', 'primary', '', true, '', ['paywall_title' => 'Le raid est locké', 'paywall_body' => 'La route et le loot s’ouvrent après l’épreuve.']]],
                    'player_overlay' => [['play', 'Lancer', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Prendre le loot', 'primary']],
                    'cart' => [['pay_card', 'Valider', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Patch', 'body' => 'Patch vivant. Builds prouvés.', 'x' => 8, 'y' => 70, 'w' => 40, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function atelier(array $h): array
    {
        return [
            'id' => 'atelier-anime',
            'label' => 'L’Atelier',
            'vertical' => 'Anime / manga',
            'pitch' => 'L’arc est un rideau. Le perso est un lieu. Le cel est une relique.',
            'innovation' => 'Anti-spoiler spatial : ce que tu n’as pas vu n’existe pas encore dans le lieu.',
            'secret' => 'TVSeries + curseur d’arc + fiches parent/enfant + merch cel en coffre. Jamais un dump wiki.',
            'mechanic' => 'Scène : le personnage au centre, l’arc en rideau. Avancer le curseur révèle salles et fiches. Un passage relie un perso à un cel vendu ailleurs. Le concierge répond sans spoiler au-delà de ton arc.',
            'moat' => 'Fandom est une guerre d’édition. Ici le lore est staké, l’arc est un droit, le cel a un certificat. Google lit TVSeries + Person + Product liés. Le fan voyage, il ne clique pas un lien bleu.',
            'ghost' => [
                'name' => 'Le concierge',
                'role' => 'Anti-spoiler',
                'wake' => 'Dis-moi jusqu’où tu en es. Je ne raconte rien après. Les cels, je les tiens.',
                'floor_key' => 'prix_plancher',
                'flex' => 0.08,
            ],
            'wormhole' => ['label' => 'Déchirure', 'edge' => 'parent_of', 'phrase' => 'Appartient à'],
            'proof' => ['label' => 'Corriger une fiche', 'stake' => 30, 'what' => 'Affiliation, fruit, apparition'],
            'omni' => 'Opening → pause → fiche perso 3D / cel viewer.',
            'canvas' => 'atelier',
            'kind' => 'series',
            'skin' => 'living',
            'preset' => 'atelier-anime',
            'primary' => '#ff4d6d',
            'hero' => $h['sea'],
            'schema' => 'TVSeries',
            'rooms' => ['vivre', 'forum', 'personnages', 'videos', 'journal', 'stories', 'boutique', 'guides', 'carnet'],
            'cck' => [['Studio', 'text'], ['Saison', 'digits'], ['Arc actuel', 'text'], ['Anti-spoiler', 'boolean']],
            'arcs' => ['Cour 1', 'Cour 2', 'Film', 'Finale'],
            'title' => '{name} — arcs, fiches, atelier | Geniuspace',
            'seo' => 'Série vivante : fiches, magazine, cels. Sans spoiler au-delà de ton arc.',
            'jsonld' => 'TVSeries + Person + Product (cel) + FAQPage anti-spoiler.',
            'chrome' => [
                'theme' => ['primary' => '#ff4d6d', 'bg' => '#14040a', 'fg' => '#ffe8ee', 'muted' => '#c4899a', 'hero' => $h['sea'], 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_roster', 'Les fiches', 'primary'], ['open_forum', 'Le salon', 'line']],
                    'shop_card' => [['add_cart', 'Acquérir le cel', 'primary'], ['view_product', 'Écrin', 'line']],
                    'player_bar' => [['chap', 'Arcs', 'ghost'], ['graph', 'Liens', 'ghost'], ['shop', 'Cels', 'ghost']],
                    'player_paywall' => [['unlock', 'Voir la suite', 'primary', '', true, '', ['paywall_title' => 'Rideau baissé', 'paywall_body' => 'La suite s’ouvre à l’arc suivant.']]],
                    'player_overlay' => [['play', 'Opening', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Acquérir le cel', 'primary']],
                    'cart' => [['pay_card', 'Acquérir', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Rideau', 'body' => 'L’arc que tu n’as pas vu n’existe pas encore.', 'x' => 8, 'y' => 68, 'w' => 50, 'h' => 12, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function territoire(array $h): array
    {
        return [
            'id' => 'territoire',
            'label' => 'Le Territoire',
            'vertical' => 'Pays / destination',
            'pitch' => 'Un pays n’est pas un article. C’est un atlas habité.',
            'innovation' => 'Corridors : chaque lieu est une salle, chaque route un passage, hreflang natif.',
            'secret' => 'Country + Place enfants + Article + Offer locaux. Les corridors sont des arêtes, pas des liens footer.',
            'mechanic' => 'Atlas : tu entres par une préfecture, tu sors par un producteur, un guide, une offre. Le guide (hôte) parle la langue du visiteur. Un passage « corridor » t’aspire vers un atelier, une table, un coffre liés au lieu.',
            'moat' => 'TripAdvisor liste. Ici le lieu a un magazine, des preuves, des œuvres, des emplois. Google lit Country + Place + Product + JobPosting maillés. Le SEO local n’est plus une fiche GMB orpheline.',
            'ghost' => [
                'name' => 'Le guide',
                'role' => 'Concierge territorial',
                'wake' => 'Dis la ville, le goût, le visa. Je t’ouvre le corridor — pas un listing.',
                'floor_key' => '',
                'flex' => 0,
            ],
            'wormhole' => ['label' => 'Corridor', 'edge' => 'parent_of', 'phrase' => 'Se trouve à'],
            'proof' => ['label' => 'Attester un lieu', 'stake' => 20, 'what' => 'Adresse, saison, horaires'],
            'omni' => 'Reportage → pause → carte + producteur + recette.',
            'canvas' => 'territoire',
            'kind' => 'series',
            'skin' => 'living',
            'preset' => 'territoire',
            'primary' => '#b91c1c',
            'hero' => $h['p'],
            'schema' => 'Country',
            'rooms' => ['vivre', 'journal', 'personnages', 'videos', 'carte', 'agenda', 'boutique', 'guides', 'forum', 'carnet'],
            'cck' => [['Pays', 'text'], ['Langue', 'text'], ['Fuseau', 'text'], ['Monnaie', 'text']],
            'arcs' => ['Saison sèche', 'Saison des pluies', 'Festivals'],
            'title' => '{name} — atlas, lieux, corridors | Geniuspace',
            'seo' => 'Territoire vivant : lieux, magazine, producteurs, passages. Hreflang.',
            'jsonld' => 'Country + Place + Article + Offer. Hreflang JA/FR/EN.',
            'chrome' => [
                'theme' => ['primary' => '#e11d48', 'bg' => '#140508', 'fg' => '#fde8ea', 'muted' => '#c48990', 'hero' => $h['p'], 'skin' => 'living', 'dock' => 'top'],
                'actions' => [
                    'hero' => [['open_roster', 'Les lieux', 'primary'], ['open_shop', 'Producteurs', 'line']],
                    'shop_card' => [['add_cart', 'Ramener', 'primary'], ['view_product', 'Producteur', 'line']],
                    'player_bar' => [['desc', 'Lieu', 'ghost'], ['chap', 'Itinéraire', 'ghost'], ['shop', 'Goût', 'ghost']],
                    'player_paywall' => [['unlock', 'Ouvrir le corridor', 'primary', '', true, '', ['paywall_title' => 'Carnet de route', 'paywall_body' => 'Les adresses et preuves s’ouvrent ici.']]],
                    'player_overlay' => [['play', 'Voir le lieu', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Ramener', 'primary']],
                    'cart' => [['pay_card', 'Commander', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Atlas', 'body' => 'Un pays. Des corridors. Pas un listing.', 'x' => 8, 'y' => 68, 'w' => 48, 'h' => 12, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function maison(array $h): array
    {
        return [
            'id' => 'maison-rh',
            'label' => 'La Maison',
            'vertical' => 'Recrutement / RH',
            'pitch' => 'L’offre est une mission. L’accueil fait le premier entretien. L’épreuve se mérite.',
            'innovation' => 'Ghost RH + épreuve in-situ. Pas une grille Indeed, pas un chatbot FAQ.',
            'secret' => 'JobPosting + champs (salaire, remote, stack) + épreuve (média grant) + carnet. L’accueil lit la fiche, pose 3 questions, débloque la suite.',
            'mechanic' => 'Papier Vera : missions, honneur, délais. L’accueil (hôte) présélectionne en live, dans la fourchette de la maison. Réponses justes → preuve « étape tenue » → l’épreuve s’ouvre. Un passage relie la maison à son univers (produit, jeu, pays).',
            'moat' => 'LinkedIn stocke un PDF. Ici le candidat a un carnet qui voyage de maison en maison. Google lit JobPosting + Occupation + Review honneur. Le Ghost travaille la nuit. L’épreuve n’est pas un formulaire Typeform orphelin.',
            'ghost' => [
                'name' => 'L’accueil',
                'role' => 'Présélection',
                'wake' => 'Je tiens la maison. Trois questions, pas un CV. Si tu tiens, j’ouvre l’épreuve.',
                'floor_key' => 'salaire',
                'flex' => 0,
            ],
            'wormhole' => ['label' => 'Vers la mission', 'edge' => 'offers', 'phrase' => 'Propose'],
            'proof' => ['label' => 'Attester une étape', 'stake' => 0, 'what' => 'Épreuve tenue, délai de réponse'],
            'omni' => 'Épreuve vidéo → pause → simulateur (consigne, circuit, soin, code).',
            'canvas' => 'maison',
            'kind' => 'company',
            'skin' => 'vera',
            'preset' => 'vera',
            'primary' => '#1b4332',
            'hero' => '/offer/releve-atelier.jpg',
            'schema' => 'Organization',
            'rooms' => ['offres', 'epreuve', 'journal', 'videos', 'guides', 'forum', 'guilde', 'carnet'],
            'cck' => [['Industrie', 'text'], ['Délai de réponse', 'digits'], ['Ville', 'geo'], ['Télétravail', 'select']],
            'arcs' => ['Candidature', 'Épreuve', 'Mission'],
            'title' => '{name} — missions, épreuves, honneur | Geniuspace',
            'seo' => 'Maison : missions lisibles, épreuves, honneur. JobPosting transparent.',
            'jsonld' => 'Organization + JobPosting + Occupation. Salaire en clair.',
            'chrome' => [
                'theme' => ['primary' => '#1b4332', 'bg' => '#f2efe6', 'fg' => '#161614', 'muted' => '#5c5a52', 'hero' => '/offer/releve-atelier.jpg', 'skin' => 'vera', 'dock' => 'top'],
                'actions' => [
                    'hero' => [['open_jobs', 'Voir les missions', 'primary'], ['open_test', 'Tenter l’épreuve', 'line']],
                    'shop_card' => [['view_product', 'Fiche', 'line']],
                    'player_bar' => [['desc', 'Contexte', 'ghost'], ['chap', 'Étapes', 'ghost']],
                    'player_paywall' => [['unlock', 'Valider l’étape', 'primary', '', true, '', ['paywall_title' => 'Épreuve en cours', 'paywall_body' => 'La suite s’ouvre quand l’étape est tenue.']]],
                    'player_overlay' => [['play', 'Commencer', 'primary']],
                    'player_shop_panel' => [],
                    'cart' => [],
                ],
                'layers' => [],
            ],
        ];
    }

    private static function scene(array $h): array
    {
        return [
            'id' => 'scene',
            'label' => 'La Scène',
            'vertical' => 'Musique / label',
            'pitch' => 'L’album est un lieu. Les stems sont des reliques. Le drop est une date.',
            'innovation' => 'Canvas waveform : scroller = avancer dans le morceau. Un hotspot = un stem, un featuring, un vinyl.',
            'secret' => 'MusicAlbum + MusicRecording enfants + Event drop + Product vinyl. Les stems lockés sont des fichiers signés.',
            'mechanic' => 'La scène : pochette au centre, waveform autour. Scroll = timecode. L’hôte régisseur vend le pressage, ouvre le stem si grant. Un passage mène de l’featuring à l’univers de l’invité.',
            'moat' => 'Spotify streame. Bandcamp vend. Ici le label habite : magazine, forum, vinyl certifié, stems pour les KOC. Google lit MusicAlbum + Event + Product. Le drop n’est plus un lien Instagram mort le lendemain.',
            'ghost' => [
                'name' => 'Le régisseur',
                'role' => 'Label',
                'wake' => 'Le drop est cette nuit. Je tiens les pressages. Propose, ou écoute d’abord.',
                'floor_key' => 'prix_plancher',
                'flex' => 0.05,
            ],
            'wormhole' => ['label' => 'Featuring', 'edge' => 'parent_of', 'phrase' => 'Joue avec'],
            'proof' => ['label' => 'Attester un crédit', 'stake' => 20, 'what' => 'Featuring, sample, pressage'],
            'omni' => 'Clip → pause → mixer (stems) / partition.',
            'canvas' => 'scene',
            'kind' => 'series',
            'skin' => 'living',
            'preset' => 'scene',
            'primary' => '#c084fc',
            'hero' => $h['s'],
            'schema' => 'MusicAlbum',
            'rooms' => ['vivre', 'audio', 'videos', 'agenda', 'boutique', 'journal', 'forum', 'gallery', 'personnages', 'carnet'],
            'cck' => [['Label', 'text'], ['Année', 'digits'], ['Pressage', 'text'], ['ISRC', 'text']],
            'arcs' => ['Single', 'Drop', 'Tournée'],
            'title' => '{name} — scène, pressages, drops | Geniuspace',
            'seo' => 'Label vivant : albums, dates, vinyls, stems. MusicAlbum + Event.',
            'jsonld' => 'MusicAlbum + MusicRecording + Event + Product vinyl.',
            'chrome' => [
                'theme' => ['primary' => '#c084fc', 'bg' => '#0b0714', 'fg' => '#f3e8ff', 'muted' => '#a78bc8', 'hero' => $h['s'], 'hero_video' => '/media/atelier.mp4', 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_videos', 'Écouter', 'primary'], ['open_shop', 'Pressages', 'line']],
                    'shop_card' => [['add_cart', 'Prendre le pressage', 'primary'], ['view_product', 'Fiche', 'line']],
                    'player_bar' => [['chap', 'Stems', 'ghost'], ['shop', 'Vinyl', 'ghost'], ['share', 'Partager', 'ghost']],
                    'player_paywall' => [['unlock', 'Ouvrir les stems', 'primary', '', true, '', ['paywall_title' => 'Stems lockés', 'paywall_body' => 'Le mixer s’ouvre après le drop ou l’achat.']]],
                    'player_overlay' => [['play', 'Play', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Prendre le pressage', 'primary']],
                    'cart' => [['pay_card', 'Payer', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Drop', 'body' => 'Le pressage est une relique. Le stem se mérite.', 'x' => 8, 'y' => 70, 'w' => 50, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function arene(array $h): array
    {
        return [
            'id' => 'arene',
            'label' => 'L’Arène',
            'vertical' => 'Sport / club',
            'pitch' => 'Le match est un lieu live. Le joueur est une fiche. La tactique s’ouvre dans le player.',
            'innovation' => 'HUD stade : score, compos, tactique. Pas un site de club WordPress 2014.',
            'secret' => 'SportsTeam + SportsEvent + Person. La compos est un graphe. L’honneur du club = délais / présence, comme une maison.',
            'mechanic' => 'Jour de match : le canvas est le rectangle. Cliquer un joueur = fiche + stats champs. Un chapitre VOD pause et pose le tableau tactique. Le speaker commente. Un passage mène du joueur à sa ville, son école, son merch.',
            'moat' => 'L’Équipe raconte. Instagram disparaît. Ici chaque match a une URL Event, chaque joueur une fiche Person durable, chaque action une preuve. Google + billets + sponsors tiennent parce que le club est une encyclopédie vivante, pas un Facebook.',
            'ghost' => [
                'name' => 'Le speaker',
                'role' => 'Voix du club',
                'wake' => 'Compos à 18 h. Pose le match, je te sors la peau et les absents.',
                'floor_key' => '',
                'flex' => 0,
            ],
            'wormhole' => ['label' => 'Sortie joueur', 'edge' => 'parent_of', 'phrase' => 'Joue pour'],
            'proof' => ['label' => 'Attester un score', 'stake' => 15, 'what' => 'Buteur, minute, carton'],
            'omni' => 'VOD match → pause → tableau tactique cliquable.',
            'canvas' => 'arene',
            'kind' => 'series',
            'skin' => 'living',
            'preset' => 'arene',
            'primary' => '#16a34a',
            'hero' => $h['m'],
            'schema' => 'SportsTeam',
            'rooms' => ['vivre', 'agenda', 'personnages', 'videos', 'journal', 'forum', 'gallery', 'boutique', 'guilde', 'carnet'],
            'cck' => [['Division', 'text'], ['Stade', 'geo'], ['Entraîneur', 'text'], ['Couleurs', 'text']],
            'arcs' => ['Aller', 'Retour', 'Coupe'],
            'title' => '{name} — club, matchs, compos | Geniuspace',
            'seo' => 'Club vivant : matchs Event, joueurs, VOD tactique, merch.',
            'jsonld' => 'SportsTeam + SportsEvent + Person. Stade Place.',
            'chrome' => [
                'theme' => ['primary' => '#16a34a', 'bg' => '#06140b', 'fg' => '#e8f8ec', 'muted' => '#7aa384', 'hero' => $h['m'], 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_roster', 'La compos', 'primary'], ['open_videos', 'Les matchs', 'line']],
                    'shop_card' => [['add_cart', 'Prendre le maillot', 'primary'], ['view_product', 'Fiche', 'line']],
                    'player_bar' => [['chap', 'Actions', 'ghost'], ['graph', 'Compos', 'ghost'], ['shop', 'Maillot', 'ghost']],
                    'player_paywall' => [['unlock', 'Voir la tactique', 'primary', '', true, '', ['paywall_title' => 'Tactique lockée', 'paywall_body' => 'Le tableau s’ouvre après le coup d’envoi ou l’abonnement.']]],
                    'player_overlay' => [['play', 'Coup d’envoi', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Prendre le maillot', 'primary']],
                    'cart' => [['pay_card', 'Payer', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'HUD', 'body' => 'Jour de match. La compos est un lieu.', 'x' => 8, 'y' => 70, 'w' => 46, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function labo(array $h): array
    {
        return [
            'id' => 'labo',
            'label' => 'Le Labo',
            'vertical' => 'Formation / masterclass',
            'pitch' => 'La leçon s’arrête. Le labo s’ouvre. Tu ne regardes pas : tu prouves.',
            'innovation' => 'Omni-média : à 03:15 la vidéo s’efface, l’éditeur / le simulateur / le modèle 3D prend le cadre.',
            'secret' => 'Course + HowTo + épreuve grant. Chaque palier = preuve dans le carnet. Le diplôme est une projection, pas un PDF.',
            'mechanic' => 'Player labo. Chapitre = exercice. Réussite = grant « étape tenue ». L’hôte tuteur répond hors-cours, dans le périmètre du module. Un passage mène de l’exo à l’offre d’une maison qui recrute cette compétence.',
            'moat' => 'YouTube = vue. Udemy = quiz. Ici le labo est un lieu d’examen, maillé aux maisons qui embauchent. Google lit Course + HowTo + VideoObject. Les formateurs partent de YouTube parce que le temps de rétention et la preuve sont le produit.',
            'ghost' => [
                'name' => 'Le tuteur',
                'role' => 'Labo',
                'wake' => 'Bloqué à l’exo 3 ? Colle l’erreur. Je ne fais pas le TP à ta place — je débloque la porte.',
                'floor_key' => '',
                'flex' => 0,
            ],
            'wormhole' => ['label' => 'Vers la mission', 'edge' => 'offers', 'phrase' => 'Prépare à'],
            'proof' => ['label' => 'Valider un exo', 'stake' => 0, 'what' => 'Exercice tenu, badge module'],
            'omni' => 'Cœur du template : pause → éditeur / simulateur / 3D, puis reprise.',
            'canvas' => 'labo',
            'kind' => 'company',
            'skin' => 'living',
            'preset' => 'labo',
            'primary' => '#38bdf8',
            'hero' => $h['s'],
            'schema' => 'Course',
            'rooms' => ['vivre', 'videos', 'guides', 'epreuve', 'journal', 'forum', 'personnages', 'offres', 'agenda', 'carnet'],
            'cck' => [['Diplôme', 'text'], ['Durée', 'text'], ['Niveau', 'select'], ['Langage', 'text']],
            'arcs' => ['Module 1', 'Module 2', 'Examen'],
            'title' => '{name} — labo, épreuves, preuves | Geniuspace',
            'seo' => 'Masterclass vivante : leçons, labo in-player, preuves, débouchés.',
            'jsonld' => 'Course + HowTo + VideoObject + Occupation liée.',
            'chrome' => [
                'theme' => ['primary' => '#38bdf8', 'bg' => '#061018', 'fg' => '#e6f6ff', 'muted' => '#7aa4b8', 'hero' => $h['s'], 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_videos', 'Entrer au labo', 'primary'], ['open_test', 'L’examen', 'line']],
                    'shop_card' => [['add_cart', 'Prendre le module', 'primary'], ['view_product', 'Programme', 'line']],
                    'player_bar' => [['chap', 'Exos', 'ghost'], ['desc', 'Énoncé', 'ghost'], ['graph', 'Débouchés', 'ghost']],
                    'player_paywall' => [['unlock', 'Ouvrir l’exo', 'primary', '', true, '', ['paywall_title' => 'Labo locké', 'paywall_body' => 'L’éditeur s’ouvre après le teaser ou l’inscription.']]],
                    'player_overlay' => [['play', 'Démarrer', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Prendre le module', 'primary']],
                    'cart' => [['pay_card', 'S’inscrire', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Labo', 'body' => 'À 03:15, tu ne regardes plus. Tu fais.', 'x' => 8, 'y' => 70, 'w' => 48, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function plateau(array $h): array
    {
        return [
            'id' => 'plateau',
            'label' => 'Le Plateau',
            'vertical' => 'Cinéma / studio',
            'pitch' => 'Les dailies sont lockées. La feuille de service est une fiche. La salle est un lieu.',
            'innovation' => 'Call sheet vivant + dailies signées. Pas un Drive + un PDF.',
            'secret' => 'Movie + VideoObject gated + Person (équipe) + Place (plateau). Les dailies = grant rôle.',
            'mechanic' => 'Mur de dailies. L’AD (hôte) ouvre les rushes au bon rôle. Un hotspot sur le cadre ouvre la fiche déco / costume. Un passage mène de l’acteur à tous ses rôles (comme RDA / Jack).',
            'moat' => 'Frame.io est un tuyau. IMDb est une base. Ici le film est un univers : magazine making-of, reliques, casting, salles. Google lit Movie + Person + VideoObject. Les prod gardent la souveraineté des rushes (MP4 local, URL signée).',
            'ghost' => [
                'name' => 'L’AD',
                'role' => 'Plateau',
                'wake' => 'Feuille de service du jour. Dis ton rôle, j’ouvre tes rushes. Pas les autres.',
                'floor_key' => '',
                'flex' => 0,
            ],
            'wormhole' => ['label' => 'Rôle', 'edge' => 'parent_of', 'phrase' => 'Interprété par'],
            'proof' => ['label' => 'Valider un crédit', 'stake' => 40, 'what' => 'Poste, jour, scène'],
            'omni' => 'Daily → pause → viewer rush multi-cam / script.',
            'canvas' => 'plateau',
            'kind' => 'series',
            'skin' => 'living',
            'preset' => 'plateau',
            'primary' => '#eab308',
            'hero' => $h['s'],
            'schema' => 'Movie',
            'rooms' => ['vivre', 'videos', 'personnages', 'journal', 'gallery', 'forum', 'guides', 'reliques', 'agenda', 'carnet'],
            'cck' => [['Réalisateur', 'text'], ['Année', 'digits'], ['Format', 'text'], ['Visa CNC', 'text']],
            'arcs' => ['Préprod', 'Tournage', 'Post'],
            'title' => '{name} — plateau, dailies, équipe | Geniuspace',
            'seo' => 'Studio vivant : film, équipe, dailies lockées, making-of.',
            'jsonld' => 'Movie + Person + VideoObject. Dailies jamais en clair.',
            'chrome' => [
                'theme' => ['primary' => '#eab308', 'bg' => '#120e04', 'fg' => '#fff6d8', 'muted' => '#b7a56a', 'hero' => $h['s'], 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_videos', 'Les dailies', 'primary'], ['open_roster', 'L’équipe', 'line']],
                    'shop_card' => [['add_cart', 'Le making-of', 'primary'], ['view_product', 'Fiche', 'line']],
                    'player_bar' => [['chap', 'Scènes', 'ghost'], ['desc', 'Feuille', 'ghost'], ['share', 'Partager', 'ghost']],
                    'player_paywall' => [['unlock', 'Ouvrir les rushes', 'primary', '', true, '', ['paywall_title' => 'Rushes lockés', 'paywall_body' => 'Réservés au rôle. L’AD ouvre.']]],
                    'player_overlay' => [['play', 'Daily', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Making-of', 'primary']],
                    'cart' => [['pay_card', 'Payer', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Call', 'body' => 'Feuille de service. Rushes signés.', 'x' => 8, 'y' => 70, 'w' => 46, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }

    private static function table(array $h): array
    {
        return [
            'id' => 'table',
            'label' => 'La Table',
            'vertical' => 'Gastronomie / terroir',
            'pitch' => 'Le plat est une fiche. Le producteur est un lieu. Le millésime est une relique.',
            'innovation' => 'Table narrative : scroller = service. Chaque plat ouvre producteur, AOP, recette HowTo.',
            'secret' => 'Recipe + Place producteur + Product AOP. Les millésimes lockés = grant / achat.',
            'mechanic' => 'Nappe canvas. Le sommelier (hôte) accorde, vend la caisse dans la fourchette. Un passage mène du plat au territoire, du producteur à la cave. Preuve : un convive atteste un millésime, stake son badge.',
            'moat' => 'TheFork réserve. Instagram disparaît. Ici la maison de goût a un magazine, des recettes indexées, des millésimes certifiés, un atlas. Google lit Recipe + Place + Product. Le SEO local n’est plus un avis Google orphelin.',
            'ghost' => [
                'name' => 'Le sommelier',
                'role' => 'Cave',
                'wake' => 'Dis le plat, l’accord, le budget. Je sors la caisse. Hors fourchette, je raconte le millésime.',
                'floor_key' => 'prix_plancher',
                'flex' => 0.12,
            ],
            'wormhole' => ['label' => 'Vers le producteur', 'edge' => 'parent_of', 'phrase' => 'Vient de'],
            'proof' => ['label' => 'Attester un millésime', 'stake' => 25, 'what' => 'Année, AOP, cave'],
            'omni' => 'Service filmé → pause → recette HowTo + cave.',
            'canvas' => 'table',
            'kind' => 'product',
            'skin' => 'living',
            'preset' => 'table',
            'primary' => '#b45309',
            'hero' => $h['m'],
            'schema' => 'Recipe',
            'rooms' => ['guides', 'boutique_expert', 'journal', 'videos', 'carte', 'forum', 'gallery', 'agenda', 'carnet'],
            'cck' => [['AOP', 'text'], ['Région', 'geo'], ['Allergènes', 'text'], ['Millésime', 'digits']],
            'arcs' => ['Entrée', 'Plat', 'Dessert', 'Cave'],
            'title' => '{name} — table, recettes, producteurs | Geniuspace',
            'seo' => 'Maison de goût : recettes, AOP, millésimes, atlas producteurs.',
            'jsonld' => 'Recipe + Place + Product AOP. Offre cave.',
            'chrome' => [
                'theme' => ['primary' => '#b45309', 'bg' => '#140a04', 'fg' => '#f8eadc', 'muted' => '#b3947a', 'hero' => $h['m'], 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [['open_shop', 'La cave', 'primary'], ['open_videos', 'Le service', 'line']],
                    'shop_card' => [['add_cart', 'Prendre la caisse', 'primary'], ['view_product', 'Millésime', 'line']],
                    'player_bar' => [['chap', 'Service', 'ghost'], ['shop', 'Cave', 'ghost'], ['desc', 'Accord', 'ghost']],
                    'player_paywall' => [['unlock', 'Ouvrir la cave', 'primary', '', true, '', ['paywall_title' => 'Cave lockée', 'paywall_body' => 'Les millésimes s’ouvrent après le service ou l’achat.']]],
                    'player_overlay' => [['play', 'Service', 'primary']],
                    'player_shop_panel' => [['add_cart', 'Prendre la caisse', 'primary']],
                    'cart' => [['pay_card', 'Commander', 'primary']],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Nappe', 'body' => 'Le plat est un lieu. Le millésime, une relique.', 'x' => 8, 'y' => 70, 'w' => 50, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }
}
