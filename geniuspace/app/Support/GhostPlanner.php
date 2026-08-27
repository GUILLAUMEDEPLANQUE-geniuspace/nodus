<?php

namespace App\Support;

use App\Models\GpNode;

/**
 * Understand → Plan. Pas de regex unique = pas un cerveau.
 * Le planner produit un but, des contraintes, une skill, des étapes.
 * Le LLM (optionnel) peut proposer un JSON ; à défaut, le plan déterministe.
 */
class GhostPlanner
{
    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $working
     * @return array{goal:string, intent:string, skill:string, constraints:array, steps:list<array>, uncertainty:string}
     */
    public static function plan(GpNode $node, string $message, array $ctx, array $working): array
    {
        $goal = self::goal($message, $ctx, $working);
        $constraints = array_merge($working['constraints'] ?? [], $goal['constraints']);
        $style = GhostMemory::fact($node, 'prefers_style');
        $budget = GhostMemory::fact($node, 'budget_max');
        if ($style && ! isset($constraints['style'])) {
            $constraints['style'] = $style;
        }
        if ($budget && ! isset($constraints['max_price'])) {
            $constraints['max_price'] = (int) $budget;
        }

        $skill = GhostSkills::get($goal['skill']);
        $steps = $skill['procedure'] ?? [];

        return [
            'goal' => $goal['name'],
            'intent' => $goal['intent'],
            'skill' => $skill['name'],
            'constraints' => $constraints,
            'steps' => $steps,
            'uncertainty' => ($goal['confidence'] ?? 1) < 0.55 ? GhostMemory::UNCERTAIN : GhostMemory::KNOWN,
            'ceiling' => $skill['level'] ?? GhostSkills::OBSERVE,
        ];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @param  array<string, mixed>  $working
     * @return array{name:string, skill:string, intent:string, constraints:array, confidence:float}
     */
    public static function goal(string $message, array $ctx, array $working): array
    {
        $m = mb_strtolower($message);
        $constraints = [];

        if (preg_match('/(\d{2,5})/u', $m, $hit)) {
            $n = (int) $hit[1];
            if ($n >= 20) {
                $constraints['amount'] = $n;
            }
        }

        if (preg_match('/bonjour|salut|hello|hey|qui es-tu/u', $m)) {
            return ['name' => 'greet', 'skill' => 'greet', 'intent' => 'hello', 'constraints' => [], 'confidence' => 0.95];
        }

        $hasOffer = isset($constraints['amount']) && preg_match('/offre|propose|prend|n[eé]goc|rabais|r[eé]duc/u', $m);
        $hasPriceTalk = preg_match('/prix|co[uû]t|combien|€|euro/u', $m);
        if ($hasOffer) {
            $constraints['offer'] = $constraints['amount'];

            return ['name' => 'negotiate', 'skill' => 'negotiate', 'intent' => 'price', 'constraints' => $constraints, 'confidence' => 0.9];
        }
        if ($hasPriceTalk || isset($constraints['amount']) && preg_match('/cherche|trouve|autour|moins de|budget/u', $m)) {
            if (isset($constraints['amount'])) {
                $constraints['max_price'] = $constraints['amount'];
            }

            return ['name' => 'find_product', 'skill' => 'find_product', 'intent' => 'price', 'constraints' => $constraints, 'confidence' => 0.86];
        }

        if (\App\Support\GhostStrategy::looksLike($message)) {
            return ['name' => 'discover_strategy', 'skill' => 'discover_strategy', 'intent' => 'strategy', 'constraints' => $constraints, 'confidence' => 0.92];
        }

        if (preg_match('/certificat|rwa|authent|d[eé]bloqu|unlock|paywall|making/u', $m)) {
            $intent = preg_match('/certificat|rwa|authent/u', $m) ? 'certificate' : 'unlock';

            return ['name' => 'verify_claim', 'skill' => 'verify_claim', 'intent' => $intent, 'constraints' => [], 'confidence' => 0.88];
        }

        if (\App\Support\GhostEdit::looksLike($message) && ! preg_match('/rembourse/u', $m)) {
            return ['name' => 'edit_page', 'skill' => 'edit_page', 'intent' => 'studio', 'constraints' => $constraints, 'confidence' => 0.9];
        }

        $biz = \App\Support\GhostBiz::route($message);
        if ($biz === 'tracking') {
            return ['name' => 'send_tracking', 'skill' => 'send_tracking', 'intent' => 'tracking', 'constraints' => [], 'confidence' => 0.92];
        }
        if ($biz === 'relance' || $biz === 'campaign') {
            return ['name' => 'run_campaign', 'skill' => 'run_campaign', 'intent' => 'campaign', 'constraints' => [], 'confidence' => 0.9];
        }
        if ($biz === 'orders') {
            return ['name' => 'segment_customers', 'skill' => 'segment_customers', 'intent' => 'orders', 'constraints' => [], 'confidence' => 0.9];
        }

        if (preg_match('/dossier|candidat|postule|envoie ma/u', $m)) {
            return ['name' => 'build_application', 'skill' => 'build_application', 'intent' => 'jobs', 'constraints' => [], 'confidence' => 0.8];
        }

        if (preg_match('/[eé]preuve|test m[eé]tier|mission|offre d.emploi|cv|recrut|emploi|salaire/u', $m)) {
            return ['name' => 'match_job', 'skill' => 'match_job', 'intent' => 'jobs', 'constraints' => $constraints, 'confidence' => 0.87];
        }

        if (preg_match('/carnet|preuv|badge|inventaire/u', $m)) {
            return ['name' => 'inspect_carnet', 'skill' => 'inspect_carnet', 'intent' => 'carnet', 'constraints' => [], 'confidence' => 0.9];
        }

        if (preg_match('/fiche|guide|wiki|pack|geniuspedia/u', $m)) {
            return ['name' => 'investigate_place', 'skill' => 'investigate_place', 'intent' => 'fiches', 'constraints' => [], 'confidence' => 0.84];
        }

        if (preg_match('/salle|forum|vid[eé]o|boutique|magasin|lien|voisin|qui|montre/u', $m)) {
            $skill = preg_match('/montre|cherche|trouve/u', $m) && ($working['constraints']['style'] ?? null)
                ? 'find_product'
                : 'investigate_place';
            $intent = $skill === 'find_product' ? 'price' : 'navigate';

            return ['name' => $skill === 'find_product' ? 'find_product' : 'investigate_place', 'skill' => $skill, 'intent' => $intent, 'constraints' => $constraints, 'confidence' => 0.7];
        }

        if (preg_match('/taille|gravure|option|personnalis|commander|panier/u', $m)) {
            return ['name' => 'find_product', 'skill' => 'find_product', 'intent' => 'order', 'constraints' => $constraints, 'confidence' => 0.8];
        }

        return [
            'name' => 'investigate_place',
            'skill' => 'investigate_place',
            'intent' => Ghost::intent($message, $ctx),
            'constraints' => $constraints,
            'confidence' => 0.5,
        ];
    }
}
