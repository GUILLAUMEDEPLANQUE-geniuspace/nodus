<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

/** Germes Finance. Branche /create tant que WorldTemplates::all ne les fusionne pas. */
class WorldGermsFinance
{
    public static function heroes(): array
    {
        return [
            'sea' => '/realms/sea-hero.jpg', 'g' => '/realms/205-garage.jpg', 's' => '/realms/studio-hero.jpg',
            'p' => '/realms/portal-hero.jpg', 'm' => '/realms/205-meet.jpg', 'd' => '/realms/205-dash.jpg',
        ];
    }

    /** @return list<array> même tuple que WorldTemplates rows */
    public static function rows(?array $R = null, ?array $h = null): array
    {
        $R = $R ?? ['forum', 'personnages', 'journal', 'videos', 'guides'];
        $h = $h ?? self::heroes();

        return [
            ['crypto-onchain', 'Finance', 'Crypto / on-chain', 'Protocoles, audits, magazine', 'FinancialProduct + audit tenu, pas un ticker', 'product', 'living', '#f59e0b', $h['p'], 'FinancialProduct', array_merge($R, ['boutique', 'reviews']), [['Chaîne', 'text'], ['Contrat', 'text']], ['Launch', 'Audit', 'Post-TGE'], '{name} — protocoles, audits', 'FinancialProduct + Article risque.'],
            ['immo-agence', 'Finance', 'Agence immobilière', 'Biens, visites, mag quartier', 'Accommodation + Event visite + geo', 'product', 'living', '#0f766e', $h['m'], 'RealEstateAgent', ['classifieds', 'carte', 'agenda', 'journal', 'videos', 'guides', 'forum'], [['Mandat', 'text'], ['Ville', 'geo']], [], '{name} — biens, visites', 'Accommodation + Offer + Event.'],
            ['banque-fintech', 'Finance', 'Banque / fintech', 'Produits, agences, mag risque', 'BankAccount + FinancialProduct + Place', 'company', 'living', '#1e3a8a', $h['d'], 'BankOrCreditUnion', ['guides', 'journal', 'forum', 'personnages', 'carte', 'offres'], [['Agréments', 'text'], ['Pays', 'text']], [], '{name} — produits, agences', 'FinancialProduct + Place agence.'],
            ['cabinet-droit', 'Finance', 'Cabinet d’avocat', 'Dossiers, actes, veille', 'LegalService + Article jurisprudence', 'company', 'vera', '#44403c', $h['s'], 'LegalService', ['journal', 'guides', 'forum', 'offres', 'personnages'], [['Barreau', 'text'], ['Matière', 'text']], [], '{name} — droit, veille', 'LegalService + Article.'],
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function asTemplates(): array
    {
        $out = [];
        foreach (self::rows() as $r) {
            $out[] = [
                'id' => $r[0], 'group' => $r[1], 'label' => $r[2], 'pitch' => $r[3], 'innovation' => $r[4],
                'kind' => $r[5], 'skin' => $r[6], 'primary' => $r[7], 'hero' => $r[8], 'schema' => $r[9],
                'rooms' => $r[10], 'cck' => $r[11], 'arcs' => $r[12], 'title' => $r[13], 'seo' => $r[14],
            ];
        }

        return $out;
    }

    public static function template(string $id): ?array
    {
        return collect(self::asTemplates())->firstWhere('id', $id);
    }

    public static function groupsWith(array $groups): array
    {
        foreach (self::asTemplates() as $t) {
            $groups[$t['group']][] = $t;
        }

        return $groups;
    }
}
