<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Chrome d’un univers : thème, scène, boutons.
 * Custom = présentation (label, ordre, présence, tokens).
 * addToCart / unlock / grants restent dans le code — pas de JS libre.
 */
class Chrome
{
    public static function presetFor(GpNode $node): string
    {
        if ($node->slug === 'vera' || $node->id === 'vera' || $node->template === 'vera-tech') {
            return 'vera';
        }
        if ($node->template) {
            return self::presetFromTemplate($node->template);
        }
        if (in_array($node->kind, ['boutique_expert', 'product'], true)) {
            return 'merch';
        }

        return 'living';
    }

    public static function presetFromTemplate(string $id): string
    {
        if (str_contains($id, 'vera') || str_contains($id, 'recrut') || str_contains($id, 'job')) {
            return 'vera';
        }
        if (str_contains($id, 'galerie') || str_contains($id, 'merch') || str_contains($id, 'boutique') || str_contains($id, 'rwa')) {
            return 'merch';
        }

        return 'living';
    }

    public static function ensure(string $nodeId, ?string $preset = null): void
    {
        $node = GpNode::query()->find($nodeId);
        if (! $node) {
            return;
        }
        $preset = $preset ?: self::presetFor($node);
        if (! DB::table('node_theme')->where('node_id', $nodeId)->exists()) {
            self::seedTheme($node, $preset);
        }
        if (! DB::table('node_actions')->where('node_id', $nodeId)->exists()) {
            self::seedActions($node, $preset);
        }
        if (! DB::table('node_scene_layers')->where('node_id', $nodeId)->exists()) {
            self::seedLayers($node, $preset);
        }
    }

    public static function applyPreset(GpNode $node, string $preset): void
    {
        DB::table('node_theme')->where('node_id', $node->id)->delete();
        DB::table('node_actions')->where('node_id', $node->id)->delete();
        DB::table('node_scene_layers')->where('node_id', $node->id)->delete();
        self::seedTheme($node, $preset);
        self::seedActions($node, $preset);
        self::seedLayers($node, $preset);
        $theme = self::theme($node->id);
        if ($theme->skin && $node->skin !== $theme->skin) {
            $node->skin = $theme->skin;
        }
        if ($theme->hero) {
            $node->hero = $theme->hero;
        }
        $node->save();
    }

    public static function theme(string $nodeId): object
    {
        $row = DB::table('node_theme')->where('node_id', $nodeId)->first();

        return $row ?: (object) [
            'node_id' => $nodeId, 'primary' => '#c9a36a', 'bg' => '#07080c', 'fg' => '#f3eadc',
            'muted' => '#8d8794', 'logo' => '', 'favicon' => '', 'hero' => '', 'hero_video' => '',
            'poster' => '', 'skin' => '', 'dock' => 'bottom', 'display_font' => '',
        ];
    }

    public static function actions(string $nodeId, string $scope): Collection
    {
        return DB::table('node_actions')
            ->where('node_id', $nodeId)
            ->where('scope', $scope)
            ->where('enabled', 1)
            ->orderBy('sort')
            ->get();
    }

    public static function allActions(string $nodeId): Collection
    {
        return DB::table('node_actions')->where('node_id', $nodeId)->orderBy('scope')->orderBy('sort')->get();
    }

    public static function layers(string $nodeId, string $tab = ''): Collection
    {
        return DB::table('node_scene_layers')
            ->where('node_id', $nodeId)
            ->where('tab', $tab)
            ->where('visible', 1)
            ->orderBy('z')
            ->get();
    }

    public static function allLayers(string $nodeId): Collection
    {
        return DB::table('node_scene_layers')->where('node_id', $nodeId)->orderBy('z')->get();
    }

    public static function extra(object $action): array
    {
        $raw = $action->extra_json ?? '';
        if ($raw === '' || $raw === '[]' || $raw === '{}') {
            return [];
        }
        $j = json_decode($raw, true);

        return is_array($j) ? $j : [];
    }

    public static function href(GpNode $node, object $action, $product = null): string
    {
        if (($action->href ?? '') !== '') {
            return $action->href;
        }
        $slug = $node->slug;

        return match ($action->action_key) {
            'view_product' => $product ? '/n/'.$slug.'/p/'.$product->id : self::shopPath($node),
            'share' => 'https://twitter.com/intent/tweet?url='.urlencode(url('/n/'.$slug)),
            'open_forum' => '/n/'.$slug.'/forum',
            'open_shop' => self::shopPath($node),
            'open_videos' => '/n/'.$slug.'/videos',
            'open_jobs', 'open_offres' => '/n/'.$slug.'/offres',
            'open_test', 'open_preuve' => '/n/'.$slug.'/epreuve',
            'open_roster' => '/n/'.$slug.'/personnages',
            'open_carnet' => '/n/'.$slug.'/carnet',
            default => '/n/'.$slug,
        };
    }

    public static function shopPath(GpNode $node): string
    {
        $tab = DB::table('node_tabs')
            ->where('node_id', $node->id)
            ->whereIn('key', ['boutique_expert', 'boutique', 'classifieds', 'merch'])
            ->orderBy('sort')
            ->value('key');

        return '/n/'.$node->slug.'/'.($tab ?: 'boutique');
    }

    public static function layerHref(GpNode $node, object $layer): string
    {
        $t = (string) ($layer->action_target ?? '');
        if ($t !== '') {
            if (str_starts_with($t, 'http') || str_starts_with($t, '/')) {
                return $t;
            }

            return '/n/'.$node->slug.'/'.$t;
        }
        if (($layer->action_key ?? '') === '') {
            return '';
        }
        $fake = (object) ['action_key' => $layer->action_key, 'href' => ''];

        return self::href($node, $fake);
    }

    public static function cssClass(object $action): string
    {
        return match ($action->variant ?? 'primary') {
            'ghost' => 'btn-ghost',
            'line' => 'btn-line',
            'icon-only' => 'btn-ghost',
            default => 'btn',
        };
    }

    public static function scopes(): array
    {
        return [
            'hero' => 'Hero',
            'shop_card' => 'Carte boutique',
            'player_bar' => 'Barre player',
            'player_paywall' => 'Paywall',
            'player_overlay' => 'Overlay play',
            'player_shop_panel' => 'Panneau shop',
            'cart' => 'Panier',
        ];
    }

    public static function bag(GpNode $node): array
    {
        self::ensure($node->id);

        return [
            'theme' => self::theme($node->id),
            'heroActions' => self::actions($node->id, 'hero'),
            'shopCardActions' => self::actions($node->id, 'shop_card'),
            'playerBar' => self::actions($node->id, 'player_bar'),
            'playerPaywall' => self::actions($node->id, 'player_paywall')->first(),
            'playerOverlay' => self::actions($node->id, 'player_overlay'),
            'playerShop' => self::actions($node->id, 'player_shop_panel'),
            'cartActions' => self::actions($node->id, 'cart'),
            'heroLayers' => self::layers($node->id, ''),
        ];
    }

    private static function seedTheme(GpNode $node, string $preset): void
    {
        $p = self::presets()[$preset]['theme'];
        DB::table('node_theme')->insert([
            'node_id' => $node->id,
            'primary' => $p['primary'],
            'bg' => $p['bg'],
            'fg' => $p['fg'],
            'muted' => $p['muted'],
            'logo' => '',
            'favicon' => '',
            'hero' => $node->hero ?: $p['hero'],
            'hero_video' => $p['hero_video'] ?? '',
            'poster' => $node->hero ?: '',
            'skin' => $p['skin'],
            'dock' => $p['dock'],
            'display_font' => '',
            'extra_json' => '',
        ]);
    }

    private static function seedActions(GpNode $node, string $preset): void
    {
        $sort = 0;
        foreach (self::presets()[$preset]['actions'] as $scope => $rows) {
            foreach ($rows as $r) {
                DB::table('node_actions')->insert([
                    'node_id' => $node->id,
                    'scope' => $scope,
                    'action_key' => $r[0],
                    'label' => $r[1],
                    'icon' => $r[3] ?? '',
                    'variant' => $r[2] ?? 'primary',
                    'sort' => $sort++,
                    'enabled' => ($r[4] ?? true) ? 1 : 0,
                    'href' => $r[5] ?? '',
                    'extra_json' => isset($r[6]) ? json_encode($r[6], JSON_UNESCAPED_UNICODE) : '',
                ]);
            }
        }
    }

    private static function seedLayers(GpNode $node, string $preset): void
    {
        foreach (self::presets()[$preset]['layers'] as $i => $l) {
            DB::table('node_scene_layers')->insert([
                'node_id' => $node->id,
                'tab' => $l['tab'] ?? '',
                'kind' => $l['kind'],
                'label' => $l['label'],
                'src' => $l['src'] ?? '',
                'body' => $l['body'] ?? '',
                'x' => $l['x'], 'y' => $l['y'], 'w' => $l['w'], 'h' => $l['h'],
                'z' => $i + 1,
                'opacity' => $l['opacity'] ?? 1,
                'action_key' => $l['action'] ?? '',
                'action_target' => $l['target'] ?? '',
                'visible' => 1, 'locked' => 0,
                'motion' => $l['motion'] ?? 'fade',
                'delay_ms' => $l['delay'] ?? 0,
            ]);
        }
    }

    public static function presets(): array
    {
        return [
            'living' => [
                'theme' => ['primary' => '#c9a36a', 'bg' => '#07080c', 'fg' => '#f3eadc', 'muted' => '#8d8794', 'hero' => '/realms/sea-hero.jpg', 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [
                        ['open_forum', 'Forum', 'primary'],
                        ['open_roster', 'Les fiches', 'line'],
                    ],
                    'shop_card' => [
                        ['add_cart', 'Ajouter au panier', 'primary'],
                        ['view_product', 'Fiche produit', 'line'],
                        ['share', 'Partager', 'ghost', '', false],
                    ],
                    'player_bar' => [
                        ['desc', 'Contexte', 'ghost'],
                        ['chap', 'Chapitres', 'ghost'],
                        ['graph', 'Connexions', 'ghost'],
                        ['shop', 'Boutique', 'ghost'],
                        ['share', 'Partager', 'ghost'],
                    ],
                    'player_paywall' => [
                        ['unlock', 'Débloquer', 'primary', '', true, '', ['paywall_title' => 'Suite premium', 'paywall_body' => 'Le teaser est fini. Les fichiers restent lockés jusqu’au déblocage.']],
                    ],
                    'player_overlay' => [
                        ['play', 'Lecture', 'primary'],
                    ],
                    'player_shop_panel' => [
                        ['add_cart', 'Ajouter au panier', 'primary'],
                        ['view_product', 'Fiche', 'line'],
                    ],
                    'cart' => [
                        ['pay_card', 'Carte', 'primary'],
                        ['pay_crypto', 'Crypto', 'line'],
                    ],
                ],
                'layers' => [],
            ],
            'merch' => [
                'theme' => ['primary' => '#c4b5fd', 'bg' => '#07080c', 'fg' => '#f3eadc', 'muted' => '#8d8794', 'hero' => '/realms/actor-hero.jpg', 'hero_video' => '/media/atelier.mp4', 'skin' => 'living', 'dock' => 'bottom'],
                'actions' => [
                    'hero' => [
                        ['open_shop', 'Voir la vitrine', 'primary'],
                        ['open_videos', 'Making-of', 'line'],
                    ],
                    'shop_card' => [
                        ['add_cart', 'Acquérir l’œuvre', 'primary'],
                        ['view_product', 'Certificat', 'line'],
                    ],
                    'player_bar' => [
                        ['desc', 'Contexte', 'ghost'],
                        ['shop', 'Merch', 'ghost'],
                        ['share', 'Partager', 'ghost'],
                        ['graph', 'Connexions', 'ghost', '', false],
                    ],
                    'player_paywall' => [
                        ['unlock', 'Débloquer · 15 €', 'primary', '', true, '', ['paywall_title' => 'La relique est lockée', 'paywall_body' => 'Le making-of complet et le certificat s’ouvrent ici.']],
                    ],
                    'player_overlay' => [
                        ['play', 'Regarder', 'primary'],
                    ],
                    'player_shop_panel' => [
                        ['add_cart', 'Acquérir l’œuvre', 'primary'],
                        ['view_product', 'Certificat', 'line'],
                    ],
                    'cart' => [
                        ['pay_card', 'CB', 'primary'],
                        ['pay_crypto', 'Crypto', 'ghost', '', false],
                    ],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Accroche', 'body' => 'Œuvre unique. Certificat. Making-of.', 'x' => 8, 'y' => 62, 'w' => 42, 'h' => 12, 'motion' => 'fade'],
                    ['kind' => 'button', 'label' => 'Vitrine', 'body' => 'Entrer dans la cimaise', 'x' => 8, 'y' => 78, 'w' => 22, 'h' => 8, 'action' => 'open_shop', 'motion' => 'slide'],
                ],
            ],
            'vera' => [
                'theme' => ['primary' => '#1b4332', 'bg' => '#f2efe6', 'fg' => '#161614', 'muted' => '#5c5a52', 'hero' => '/offer/releve-atelier.jpg', 'skin' => 'vera', 'dock' => 'top'],
                'actions' => [
                    'hero' => [
                        ['open_jobs', 'Voir les missions', 'primary'],
                        ['open_test', 'Tenter l’épreuve', 'line'],
                    ],
                    'shop_card' => [
                        ['open_test', 'Débloquer l’épreuve', 'primary'],
                        ['view_product', 'Lire l’offre', 'line'],
                    ],
                    'player_bar' => [
                        ['desc', 'Dossier', 'ghost'],
                        ['shop', 'L’offre', 'ghost'],
                        ['graph', 'Connexions', 'ghost', '', false],
                    ],
                    'player_paywall' => [
                        ['unlock', 'Continuer l’épreuve', 'primary', '', true, '', ['paywall_title' => 'Suite du cas · étape 2', 'paywall_body' => 'Le teaser pose le geste. La suite est l’épreuve.']],
                    ],
                    'player_overlay' => [
                        ['play', 'Lancer l’épreuve', 'primary'],
                    ],
                    'player_shop_panel' => [
                        ['open_jobs', 'Voir les missions', 'primary'],
                    ],
                    'cart' => [
                        ['pay_card', 'Carte', 'primary'],
                    ],
                ],
                'layers' => [
                    ['kind' => 'text', 'label' => 'Accroche', 'body' => 'Le salaire est écrit. Le délai aussi.', 'x' => 8, 'y' => 68, 'w' => 50, 'h' => 10, 'motion' => 'fade'],
                ],
            ],
        ];
    }
}
