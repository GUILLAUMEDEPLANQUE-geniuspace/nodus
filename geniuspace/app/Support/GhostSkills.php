<?php

namespace App\Support;

/**
 * Catalogue de compétences. Ghost n'improvise pas une stratégie :
 * il choisit une skill versionnée, avec outils et plafond d'autorisation.
 *
 * OBSERVE  — lire le monde
 * SUGGEST  — proposer
 * PREPARE  — préparer (brouillon, panier) sans engager
 * ACT      — jamais tout seul (achat, candidature, unlock, graphe)
 */
class GhostSkills
{
    public const OBSERVE = 'observe';

    public const SUGGEST = 'suggest';

    public const PREPARE = 'prepare';

    public const ACT = 'act';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'greet' => [
                'name' => 'greet',
                'description' => 'Se présenter, poser le cadre du lieu.',
                'level' => self::OBSERVE,
                'required_tools' => [],
                'procedure' => [],
            ],
            'find_product' => [
                'name' => 'find_product',
                'description' => 'Trouver une œuvre sous contraintes (prix, style, dispo).',
                'level' => self::OBSERVE,
                'required_tools' => ['list_products', 'compare_products', 'rank'],
                'procedure' => [
                    ['tool' => 'list_products', 'level' => self::OBSERVE],
                    ['tool' => 'compare_products', 'level' => self::OBSERVE],
                    ['tool' => 'rank', 'level' => self::OBSERVE],
                ],
            ],
            'negotiate' => [
                'name' => 'negotiate',
                'description' => 'Tenir une fourchette. Panier = PREPARE, paiement = ACT.',
                'level' => self::PREPARE,
                'required_tools' => ['list_products', 'compare_products'],
                'procedure' => [
                    ['tool' => 'list_products', 'level' => self::OBSERVE],
                    ['tool' => 'compare_products', 'level' => self::OBSERVE],
                ],
            ],
            'match_job' => [
                'name' => 'match_job',
                'description' => 'Comparer preuves du carnet et exigences des missions.',
                'level' => self::OBSERVE,
                'required_tools' => ['check_grants', 'list_neighbors', 'match_user_job', 'rank'],
                'procedure' => [
                    ['tool' => 'check_grants', 'level' => self::OBSERVE],
                    ['tool' => 'list_neighbors', 'level' => self::OBSERVE],
                    ['tool' => 'match_user_job', 'level' => self::OBSERVE],
                    ['tool' => 'rank', 'level' => self::OBSERVE],
                ],
            ],
            'verify_claim' => [
                'name' => 'verify_claim',
                'description' => 'Certificat, unlock, paywall : orienter, ne jamais ouvrir.',
                'level' => self::OBSERVE,
                'required_tools' => ['list_media', 'check_grants'],
                'procedure' => [
                    ['tool' => 'list_media', 'level' => self::OBSERVE],
                    ['tool' => 'check_grants', 'level' => self::OBSERVE],
                ],
            ],
            'inspect_carnet' => [
                'name' => 'inspect_carnet',
                'description' => 'Lire les preuves déjà tenues.',
                'level' => self::OBSERVE,
                'required_tools' => ['check_grants'],
                'procedure' => [
                    ['tool' => 'check_grants', 'level' => self::OBSERVE],
                ],
            ],
            'investigate_place' => [
                'name' => 'investigate_place',
                'description' => 'Explorer salles, fiches, graphe du lieu.',
                'level' => self::OBSERVE,
                'required_tools' => ['search_graph', 'list_rooms', 'list_fiches'],
                'procedure' => [
                    ['tool' => 'search_graph', 'level' => self::OBSERVE],
                    ['tool' => 'list_rooms', 'level' => self::OBSERVE],
                    ['tool' => 'list_fiches', 'level' => self::OBSERVE],
                ],
            ],
            'build_application' => [
                'name' => 'build_application',
                'description' => 'Préparer un dossier. Envoi = ACT, confirmation humaine.',
                'level' => self::PREPARE,
                'required_tools' => ['check_grants', 'match_user_job'],
                'procedure' => [
                    ['tool' => 'check_grants', 'level' => self::OBSERVE],
                    ['tool' => 'list_neighbors', 'level' => self::OBSERVE],
                    ['tool' => 'match_user_job', 'level' => self::OBSERVE],
                ],
            ],
            'edit_page' => [
                'name' => 'edit_page',
                'description' => 'Observer la fiche, proposer un DSL, preview. APPLY ailleurs.',
                'level' => self::PREPARE,
                'required_tools' => ['read_editor', 'list_field_types', 'search_media', 'search_playlist'],
                'procedure' => [
                    ['tool' => 'read_editor', 'level' => self::OBSERVE],
                    ['tool' => 'list_field_types', 'level' => self::OBSERVE],
                ],
            ],
            'segment_customers' => [
                'name' => 'segment_customers',
                'description' => 'Compter, filtrer. Ne pas écrire.',
                'level' => self::OBSERVE,
                'required_tools' => ['customers.segment', 'orders.filter'],
                'procedure' => [
                    ['tool' => 'customers.segment', 'level' => self::OBSERVE],
                ],
            ],
            'run_campaign' => [
                'name' => 'run_campaign',
                'description' => 'Préparer une campagne. Envoi = ACT.',
                'level' => self::PREPARE,
                'required_tools' => ['customers.segment', 'campaign.preview'],
                'procedure' => [
                    ['tool' => 'customers.segment', 'level' => self::OBSERVE],
                    ['tool' => 'campaign.preview', 'level' => self::PREPARE],
                ],
            ],
            'send_tracking' => [
                'name' => 'send_tracking',
                'description' => 'Préparer le suivi. Volume ≥ 100 = confirmation.',
                'level' => self::PREPARE,
                'required_tools' => ['orders.filter'],
                'procedure' => [
                    ['tool' => 'orders.filter', 'level' => self::OBSERVE],
                ],
            ],
        ];
    }

    public static function get(string $name): array
    {
        $all = self::all();

        return $all[$name] ?? $all['investigate_place'];
    }

    public static function toolLevel(string $tool): string
    {
        return match ($tool) {
            'purchase', 'send_application', 'unlock_content', 'modify_graph',
            'campaign.launch', 'message.send', 'order.refund', 'customer.delete', 'payment.modify' => self::ACT,
            'build_cart', 'create_draft', 'field.add', 'media.insert', 'playlist.insert',
            'template.apply', 'campaign.create' => self::PREPARE,
            default => self::OBSERVE,
        };
    }

    public static function may(string $tool, string $ceiling): bool
    {
        $rank = [self::OBSERVE => 1, self::SUGGEST => 2, self::PREPARE => 3, self::ACT => 4];
        $need = $rank[self::toolLevel($tool)] ?? 1;
        $have = $rank[$ceiling] ?? 1;

        return $need <= $have;
    }
}
