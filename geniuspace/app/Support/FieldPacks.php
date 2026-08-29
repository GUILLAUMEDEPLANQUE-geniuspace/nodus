<?php

namespace App\Support;

/** Packs de champs par métier. Fusionnés dans FieldTemplates::all(). */
class FieldPacks
{
    public static function all(): array
    {
        $st = ['key' => 'statut', 'name' => 'Statut', 'type' => 'select', 'unit' => '', 'options' => 'draft|published'];
        return [
            'organisation' => ['label' => 'Organisation', 'for' => 'organization', 'plain' => 'Siège, objet, registre.', 'fields' => [
                ['key' => 'siege', 'name' => 'Siège', 'type' => 'geo', 'unit' => ''],
                ['key' => 'objet', 'name' => 'Objet', 'type' => 'text', 'unit' => ''],
                ['key' => 'siret', 'name' => 'SIRET / registre', 'type' => 'text', 'unit' => ''], $st,
            ]],
            'lieu' => ['label' => 'Lieu', 'for' => 'place', 'plain' => 'Adresse, horaires, bassin.', 'fields' => [
                ['key' => 'adresse', 'name' => 'Adresse', 'type' => 'geo', 'unit' => ''],
                ['key' => 'horaires', 'name' => 'Horaires', 'type' => 'text', 'unit' => ''],
                ['key' => 'bassin', 'name' => 'Bassin / région', 'type' => 'text', 'unit' => ''], $st,
            ]],
            'jutsu' => ['label' => 'Technique', 'for' => 'technique', 'plain' => 'Ecole, coût, prérequis.', 'fields' => [
                ['key' => 'ecole', 'name' => 'Ecole / type', 'type' => 'text', 'unit' => ''],
                ['key' => 'cout', 'name' => 'Coût / rang', 'type' => 'text', 'unit' => ''],
                ['key' => 'prerequis', 'name' => 'Prérequis', 'type' => 'text', 'unit' => ''], $st,
            ]],
            'arc' => ['label' => 'Arc', 'for' => 'arc', 'plain' => 'Ordre, rideau.', 'fields' => [
                ['key' => 'ordre', 'name' => 'Ordre', 'type' => 'digits', 'unit' => ''],
                ['key' => 'rideau', 'name' => 'Rideau', 'type' => 'select', 'unit' => '', 'options' => 'Masqué|Visible'], $st,
            ]],
            'relique' => ['label' => 'Relique', 'for' => 'product', 'plain' => 'Certificat, plancher, rareté.', 'fields' => [
                ['key' => 'certificat', 'name' => 'Certificat', 'type' => 'text', 'unit' => ''],
                ['key' => 'prix_plancher', 'name' => 'Plancher', 'type' => 'digits', 'unit' => '€'],
                ['key' => 'rarete', 'name' => 'Rareté', 'type' => 'select', 'unit' => '', 'options' => 'Unique|Série limitée|Edition'],
                ['key' => 'provenance', 'name' => 'Provenance', 'type' => 'text', 'unit' => ''],
            ]],
            'oeuvre' => ['label' => 'Oeuvre', 'for' => 'creative', 'plain' => 'Auteur, année, support.', 'fields' => [
                ['key' => 'auteur', 'name' => 'Auteur', 'type' => 'text', 'unit' => ''],
                ['key' => 'annee', 'name' => 'Année', 'type' => 'digits', 'unit' => ''],
                ['key' => 'support', 'name' => 'Support', 'type' => 'text', 'unit' => ''],
            ]],
            'competence' => ['label' => 'Compétence', 'for' => 'skill', 'plain' => 'Geste, niveau, preuve.', 'fields' => [
                ['key' => 'geste', 'name' => 'Geste', 'type' => 'text', 'unit' => ''],
                ['key' => 'niveau', 'name' => 'Niveau', 'type' => 'select', 'unit' => '', 'options' => 'Découverte|Tenu|Maîtrisé'],
                ['key' => 'preuve', 'name' => 'Preuve', 'type' => 'text', 'unit' => ''],
            ]],
            'cours' => ['label' => 'Cours', 'for' => 'course', 'plain' => 'RNCP, durée, promo.', 'fields' => [
                ['key' => 'rncp', 'name' => 'RNCP / code', 'type' => 'text', 'unit' => ''],
                ['key' => 'duree', 'name' => 'Durée', 'type' => 'text', 'unit' => ''],
                ['key' => 'promo', 'name' => 'Promo', 'type' => 'text', 'unit' => ''],
            ]],
            'evenement' => ['label' => 'Evénement', 'for' => 'event', 'plain' => 'Date, lieu, type.', 'fields' => [
                ['key' => 'date_event', 'name' => 'Date', 'type' => 'datetime', 'unit' => ''],
                ['key' => 'lieu_event', 'name' => 'Lieu', 'type' => 'geo', 'unit' => ''],
                ['key' => 'type_event', 'name' => 'Type', 'type' => 'text', 'unit' => ''],
            ]],
            'vehicule' => ['label' => 'Véhicule', 'for' => 'auto', 'plain' => 'Marque, cylindrée, année.', 'fields' => [
                ['key' => 'marque', 'name' => 'Marque', 'type' => 'text', 'unit' => ''],
                ['key' => 'cylindree', 'name' => 'Cylindrée', 'type' => 'text', 'unit' => ''],
                ['key' => 'annee', 'name' => 'Année', 'type' => 'digits', 'unit' => ''],
            ]],
            'rituel' => ['label' => 'Rituel', 'for' => 'rite', 'plain' => 'Intention, durée, outils.', 'fields' => [
                ['key' => 'intention', 'name' => 'Intention', 'type' => 'text', 'unit' => ''],
                ['key' => 'duree', 'name' => 'Durée', 'type' => 'text', 'unit' => ''],
                ['key' => 'outils', 'name' => 'Outils / pierres', 'type' => 'text', 'unit' => ''],
            ]],
            'compte' => ['label' => 'Compte / ICP', 'for' => 'account', 'plain' => 'ICP, cycle, enseigne.', 'fields' => [
                ['key' => 'icp', 'name' => 'ICP', 'type' => 'text', 'unit' => ''],
                ['key' => 'cycle', 'name' => 'Cycle', 'type' => 'text', 'unit' => ''],
                ['key' => 'enseigne', 'name' => 'Enseigne', 'type' => 'text', 'unit' => ''],
            ]],
            'playbook' => ['label' => 'Playbook', 'for' => 'playbook', 'plain' => 'Etape, KPI, standard.', 'fields' => [
                ['key' => 'etape', 'name' => 'Etape', 'type' => 'text', 'unit' => ''],
                ['key' => 'kpi', 'name' => 'KPI', 'type' => 'text', 'unit' => ''],
                ['key' => 'standard', 'name' => 'Standard', 'type' => 'text', 'unit' => ''],
            ]],
            'livrable' => ['label' => 'Livrable', 'for' => 'deliverable', 'plain' => 'Type, date, version.', 'fields' => [
                ['key' => 'type_livrable', 'name' => 'Type', 'type' => 'text', 'unit' => ''],
                ['key' => 'date_livrable', 'name' => 'Date', 'type' => 'datetime', 'unit' => ''],
                ['key' => 'version', 'name' => 'Version', 'type' => 'text', 'unit' => ''],
            ]],
            'actif' => ['label' => 'Actif financier', 'for' => 'asset', 'plain' => 'ISIN / contrat, risque, frais.', 'fields' => [
                ['key' => 'isin', 'name' => 'ISIN / contrat', 'type' => 'text', 'unit' => ''],
                ['key' => 'risque', 'name' => 'Risque', 'type' => 'select', 'unit' => '', 'options' => '1|2|3|4|5|6|7'],
                ['key' => 'frais', 'name' => 'Frais', 'type' => 'text', 'unit' => ''],
                ['key' => 'horizon', 'name' => 'Horizon', 'type' => 'text', 'unit' => ''],
            ]],
            'protocole' => ['label' => 'Protocole', 'for' => 'protocol', 'plain' => 'Chaîne, contrat, audit.', 'fields' => [
                ['key' => 'chaine', 'name' => 'Chaîne', 'type' => 'text', 'unit' => ''],
                ['key' => 'contrat', 'name' => 'Contrat', 'type' => 'text', 'unit' => ''],
                ['key' => 'audit', 'name' => 'Audit', 'type' => 'text', 'unit' => ''],
                ['key' => 'tvl', 'name' => 'TVL / usage', 'type' => 'text', 'unit' => ''],
            ]],
            'bien' => ['label' => 'Bien immobilier', 'for' => 'listing', 'plain' => 'Surface, DPE, prix, ville.', 'fields' => [
                ['key' => 'surface', 'name' => 'Surface', 'type' => 'digits', 'unit' => 'm²'],
                ['key' => 'dpe', 'name' => 'DPE', 'type' => 'select', 'unit' => '', 'options' => 'A|B|C|D|E|F|G'],
                ['key' => 'prix', 'name' => 'Prix', 'type' => 'digits', 'unit' => '€'],
                ['key' => 'ville', 'name' => 'Ville', 'type' => 'geo', 'unit' => ''],
                ['key' => 'type_bien', 'name' => 'Type', 'type' => 'select', 'unit' => '', 'options' => 'Appartement|Maison|Immeuble|Terrain|Local'],
            ]],
            'mandat' => ['label' => 'Mandat', 'for' => 'mandate', 'plain' => 'Type, dates.', 'fields' => [
                ['key' => 'type_mandat', 'name' => 'Type', 'type' => 'select', 'unit' => '', 'options' => 'Simple|Exclusif|Délégation'],
                ['key' => 'debut', 'name' => 'Début', 'type' => 'datetime', 'unit' => ''],
                ['key' => 'fin', 'name' => 'Fin', 'type' => 'datetime', 'unit' => ''],
            ]],
            'dossier' => ['label' => 'Dossier', 'for' => 'case', 'plain' => 'Matière, juridiction, état.', 'fields' => [
                ['key' => 'matiere', 'name' => 'Matière', 'type' => 'text', 'unit' => ''],
                ['key' => 'juridiction', 'name' => 'Juridiction', 'type' => 'text', 'unit' => ''],
                ['key' => 'etat', 'name' => 'Etat', 'type' => 'select', 'unit' => '', 'options' => 'Ouvert|Instruction|Clos'],
            ]],
            'acte' => ['label' => 'Acte', 'for' => 'instrument', 'plain' => 'Nature, date, référence.', 'fields' => [
                ['key' => 'nature', 'name' => 'Nature', 'type' => 'text', 'unit' => ''],
                ['key' => 'date_acte', 'name' => 'Date', 'type' => 'datetime', 'unit' => ''],
                ['key' => 'ref_acte', 'name' => 'Référence', 'type' => 'text', 'unit' => ''],
            ]],
            'jurisprudence' => ['label' => 'Jurisprudence', 'for' => 'ruling', 'plain' => 'Juridiction, date, pourvoi.', 'fields' => [
                ['key' => 'juridiction', 'name' => 'Juridiction', 'type' => 'text', 'unit' => ''],
                ['key' => 'date_decision', 'name' => 'Date', 'type' => 'datetime', 'unit' => ''],
                ['key' => 'pourvoi', 'name' => 'Pourvoi / RG', 'type' => 'text', 'unit' => ''],
            ]],
        ];
    }
}
