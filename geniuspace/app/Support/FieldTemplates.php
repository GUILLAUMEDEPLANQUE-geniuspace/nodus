<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Modèles de fiche. L’opérateur choisit un métier, les détails se posent.
 * Internement : lignes cck_fields à field_key stable.
 */
class FieldTemplates
{
    public static function all(): array
    {
        $core = [
            'offre-tech' => [
                'label' => 'Offre tech',
                'for' => 'job',
                'plain' => 'Salaire, télétravail, stack, séniorité, visa.',
                'fields' => array_merge(self::offreBase(), [
                    ['key' => 'stack', 'name' => 'Compétences', 'type' => 'text', 'unit' => ''],
                    ['key' => 'visa', 'name' => 'Visa', 'type' => 'select', 'unit' => '', 'options' => 'Non requis|Sponsorisé|Obligatoire'],
                ]),
            ],
            'offre-industrie' => [
                'label' => 'Offre industrie',
                'for' => 'job',
                'plain' => 'Salaire, habilitation, CACES, 3×8.',
                'fields' => array_merge(self::offreBase(), [
                    ['key' => 'stack', 'name' => 'Compétences', 'type' => 'text', 'unit' => ''],
                    ['key' => 'habilitation', 'name' => 'Habilitation', 'type' => 'text', 'unit' => ''],
                    ['key' => 'caces', 'name' => 'CACES', 'type' => 'select', 'unit' => '', 'options' => 'Non|Oui — à passer|Oui — financé'],
                    ['key' => 'trois_huit', 'name' => '3×8 / astreinte', 'type' => 'select', 'unit' => '', 'options' => 'Non|2×8|3×8|Astreinte écrite'],
                ]),
            ],
            'personnage' => [
                'label' => 'Personnage',
                'for' => 'character',
                'plain' => 'Fruit, prime, affiliation.',
                'fields' => [
                    ['key' => 'fruit', 'name' => 'Fruit', 'type' => 'text', 'unit' => ''],
                    ['key' => 'prime', 'name' => 'Prime', 'type' => 'digits', 'unit' => ''],
                    ['key' => 'affiliation', 'name' => 'Affiliation', 'type' => 'text', 'unit' => ''],
                ],
            ],
            'produit' => [
                'label' => 'Produit',
                'for' => 'product',
                'plain' => 'SKU, stock, matière.',
                'fields' => [
                    ['key' => 'sku', 'name' => 'Référence', 'type' => 'text', 'unit' => ''],
                    ['key' => 'stock', 'name' => 'Stock', 'type' => 'digits', 'unit' => ''],
                    ['key' => 'matiere', 'name' => 'Matière', 'type' => 'text', 'unit' => ''],
                    ['key' => 'prix', 'name' => 'Prix', 'type' => 'digits', 'unit' => '€'],
                ],
            ],
            'commande-print' => [
                'label' => 'Options d’achat (print)',
                'for' => 'product',
                'plain' => 'Taille, gravure, dos imprimé, logo — à la commande.',
                'fields' => [
                    ['key' => 'taille', 'name' => 'Taille', 'type' => 'select', 'unit' => '', 'options' => 'S|M|L|XL', 'audience' => 'commande'],
                    ['key' => 'gravure', 'name' => 'Gravure', 'type' => 'text', 'unit' => '', 'audience' => 'commande'],
                    ['key' => 'dos', 'name' => 'Dos imprimé', 'type' => 'select', 'unit' => '', 'options' => 'Sans:+0|Oui:+5', 'audience' => 'commande'],
                    ['key' => 'logo', 'name' => 'Logo client', 'type' => 'file', 'unit' => '', 'audience' => 'commande'],
                ],
            ],
            'maison' => [
                'label' => 'Maison',
                'for' => 'company',
                'plain' => 'Délai de réponse, fiabilité, industrie, ville.',
                'fields' => [
                    ['key' => 'delai_reponse', 'name' => 'Délai de réponse', 'type' => 'digits', 'unit' => 'jours'],
                    ['key' => 'fiabilite', 'name' => 'Fiabilité', 'type' => 'digits', 'unit' => ''],
                    ['key' => 'industrie', 'name' => 'Industrie', 'type' => 'text', 'unit' => ''],
                    ['key' => 'ville', 'name' => 'Ville', 'type' => 'geo', 'unit' => ''],
                ],
            ],
        ];

        return $core + FieldPacks::all();
    }

    public static function has(string $id): bool
    {
        return isset(self::all()[$id]);
    }

    public static function get(string $id): array
    {
        $all = self::all();
        abort_unless(isset($all[$id]), 404, 'Modèle inconnu.');

        return $all[$id];
    }

    /** Pose les détails manquants. Idempotent sur field_key. */
    public static function apply(string $nodeId, string $templateId): int
    {
        $tpl = self::get($templateId);
        $have = DB::table('cck_fields')->where('node_id', $nodeId)->pluck('id', 'field_key');
        $sort = (int) DB::table('cck_fields')->where('node_id', $nodeId)->max('sort');
        $n = 0;
        foreach ($tpl['fields'] as $f) {
            if (($f['key'] ?? '') !== '' && $have->has($f['key'])) {
                continue;
            }
            $sort++;
            $row = [
                'node_id' => $nodeId,
                'name' => $f['name'],
                'type' => $f['type'],
                'value' => $f['value'] ?? '',
                'target_kind' => $f['target_kind'] ?? 'node',
                'target_id' => $f['target_id'] ?? '',
                'sort' => $sort,
                'options' => $f['options'] ?? '',
                'seo_title' => $f['name'],
                'field_key' => $f['key'],
                'unit' => $f['unit'] ?? '',
                'min_val' => $f['min'] ?? null,
                'max_val' => $f['max'] ?? null,
                'schema_version' => 1,
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('cck_fields', 'audience')) {
                $row['audience'] = $f['audience'] ?? 'fiche';
            }
            DB::table('cck_fields')->insert($row);
            $n++;
        }

        return $n;
    }

    private static function offreBase(): array
    {
        return [
            ['key' => 'salaire', 'name' => 'Salaire', 'type' => 'digits', 'unit' => 'k€'],
            ['key' => 'remote', 'name' => 'Télétravail', 'type' => 'select', 'unit' => '', 'options' => 'Sur site|Hybride|Télétravail'],
            ['key' => 'contrat', 'name' => 'Contrat', 'type' => 'select', 'unit' => '', 'options' => 'CDI|CDD|Stage|Alternance|Freelance'],
            ['key' => 'seniorite', 'name' => 'Séniorité', 'type' => 'select', 'unit' => '', 'options' => 'Junior|Confirmé|Senior|Lead|Staff'],
        ];
    }
}
