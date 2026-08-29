<?php

namespace App\Support;

/** Germes absents du zip historique : Finance. */
class WorldGermsFinance
{
    /** @return list<array> même tuple que WorldTemplates::$rows */
    public static function rows(array $R, array $h): array
    {
        return [
            ['crypto-onchain', 'Finance', 'Crypto / on-chain', 'Protocoles, audits, magazine', 'FinancialProduct + audit tenu, pas un ticker', 'product', 'living', '#f59e0b', $h['p'], 'FinancialProduct', array_merge($R, ['boutique', 'reviews']), [['Chaîne', 'text'], ['Contrat', 'text']], ['Launch', 'Audit', 'Post-TGE'], '{name} — protocoles, audits', 'FinancialProduct + Article risque.'],
            ['immo-agence', 'Finance', 'Agence immobilière', 'Biens, visites, mag quartier', 'Accommodation + Event visite + geo', 'product', 'living', '#0f766e', $h['m'], 'RealEstateAgent', ['classifieds', 'carte', 'agenda', 'journal', 'videos', 'guides', 'forum'], [['Mandat', 'text'], ['Ville', 'geo']], [], '{name} — biens, visites', 'Accommodation + Offer + Event.'],
            ['banque-fintech', 'Finance', 'Banque / fintech', 'Produits, agences, mag risque', 'BankAccount + FinancialProduct + Place', 'company', 'living', '#1e3a8a', $h['d'], 'BankOrCreditUnion', ['guides', 'journal', 'forum', 'personnages', 'carte', 'offres'], [['Agréments', 'text'], ['Pays', 'text']], [], '{name} — produits, agences', 'FinancialProduct + Place agence.'],
            ['cabinet-droit', 'Finance', 'Cabinet d’avocat', 'Dossiers, actes, veille', 'LegalService + Article jurisprudence', 'company', 'vera', '#44403c', $h['s'], 'LegalService', ['journal', 'guides', 'forum', 'offres', 'personnages'], [['Barreau', 'text'], ['Matière', 'text']], [], '{name} — droit, veille', 'LegalService + Article.'],
        ];
    }
}
