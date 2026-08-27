<?php

namespace App\Support;

/**
 * Quinze règles du moteur. Pas un concept produit : un contrat.
 * Chaque règle a un test. Une PR qui les casse est refusée.
 *
 * Engine  → graphe + champs. N’appelle jamais Ghost / Grantor / Chrome.
 * Grantor → preuves. Peut lire Engine.
 * Chrome  → habillage. Peut lire Engine.
 * Ghost   → orchestre. Peut lire les trois. N’écrit pas un grant.
 */
final class Invariants
{
    public const AUTONOMY_CAP = 54;

    public const PLAY_TTL = 900;

    /** Colonnes métier interdites sur `nodes`. Le métier vit dans cck_fields. */
    public const BANNED_COLUMNS = ['salary', 'salaire', 'remote', 'fruit', 'wage', 'job_salary'];

    /** Outils ACT : GhostTools::call doit renvoyer null. */
    public const ACT_TOOLS = [
        'purchase', 'send_application', 'unlock_content', 'modify_graph',
        'create_field', 'update_field', 'delete_field', 'field.add', 'media.insert',
        'campaign.launch', 'message.send', 'order.refund', 'customer.delete', 'payment.modify',
    ];

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'I-READ' => 'Lectures publiques. Écritures (studio, drive, forum, magazine, builder) authentifiées.',
            'I-STAFF' => 'Sculpter un lieu exige node_staff. Jamais d’auto-promotion owner.',
            'I-NO-DEMO' => '/login/demo n’existe qu’en local / tests.',
            'I-GRANT' => 'Média gated : ligne grants, sinon 403. Teaser public distinct.',
            'I-PATH' => '/play ne sort pas du coffre (pas de ..).',
            'I-PREVIEW' => 'preview=1 ne sert jamais un fichier private/.',
            'I-JARGON' => 'Copy produit sans CCK, parent_of, GpNode, node_tabs.',
            'I-FIELDS' => 'Métier = cck_fields.field_key. Pas de colonne salary sur nodes.',
            'I-NO-ACT' => 'Ghost n’achète, n’embauche, n’ouvre, ne rembourse pas tout seul.',
            'I-PLAN' => 'PLAN n’écrit pas la fiche. APPLY exige staff admin.',
            'I-DENY' => 'Remboursement, suppression client, paiement : deny.',
            'I-FLOOR' => 'Le prix tenu n’est jamais sous le plancher.',
            'I-CRAWL' => 'Pas de crawl. Pack du lieu seulement.',
            'I-MEMORY' => 'Une hallucination n’entre pas en mémoire.',
            'I-CAP' => 'Autonomie Ghost plafonnée à '.self::AUTONOMY_CAP.'.',
        ];
    }

    /**
     * Cinq règles d’architecture. Pas un seizième concept produit :
     * elles rendent le pipeline INTENT→…→COMMIT in-cassable.
     *
     * @return array<string, string>
     */
    public static function architecture(): array
    {
        return [
            'I-TRUTH' => 'World truth (Engine) et agent belief (GhostMemory) ne se mélangent pas.',
            'I-CONTRACT' => 'Toute mutation passe par un contrat autorisé. PLAN ≠ APPLY.',
            'I-PROVENANCE' => 'Chaque transition appliquée a produced_by + verified_by.',
            'I-CLAIM' => 'Claim → evidence → PASS/FAIL/UNKNOWN. Pas un haystack aveugle.',
            'I-CAPABILITY' => 'Ghost ne reçoit pas les opérations DENY du manifeste.',
        ];
    }

    public static function contained(string $full, string $root): ?string
    {
        if (! is_file($full)) {
            return null;
        }
        $real = realpath($full);
        $base = realpath($root);
        if (! $real || ! $base) {
            return null;
        }
        if ($real === $base || str_starts_with($real, $base.DIRECTORY_SEPARATOR)) {
            return $real;
        }

        return null;
    }

    public static function safeRel(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return $path !== '' && ! str_contains($path, '..');
    }

    public static function heldCents(int $held, int $floor): int
    {
        $held = max(0, $held);
        if ($floor > 0 && $held < $floor) {
            return $floor;
        }

        return $held;
    }

    public static function demoLoginAllowed(): bool
    {
        return app()->environment('local', 'testing');
    }
}
