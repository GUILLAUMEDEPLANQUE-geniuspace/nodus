<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Str;

/**
 * Ingest → propositions. applied = false. Aucune écriture de nœud.
 */
class EntityProposer
{
    /**
     * @return list<array{title:string,slug:string,entity:string,status:string,applied:bool}>
     */
    public static function fromText(GpNode $world, string $text): array
    {
        $out = [];
        $seen = [];
        foreach (preg_split("/\r\n|\n|\r/", $text) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $title = null;
            $entity = 'personnage';
            if (preg_match('/^#{1,3}\s+(.+)$/u', $line, $m)) {
                $title = trim($m[1]);
            } elseif (preg_match('/^(fiche|perso|personnage|lieu|village|clan|orga|organisation|jutsu|technique|arc|produit|relique|oeuvre|offre|mission|competence|cours|event|evenement|vehicule|voiture|rituel|compte|playbook|livrable|actif|token|protocole|bien|mandat|dossier|acte|jurisprudence)\s*[:\-\x{2013}]\s*(.+)$/iu', $line, $m)) {
                $title = trim($m[2]);
                $entity = self::guess($m[1]);
            }
            if (! $title || mb_strlen($title) < 2 || mb_strlen($title) > 80) {
                continue;
            }
            $slug = Str::slug($title);
            if ($slug === '' || isset($seen[$slug])) {
                continue;
            }
            $seen[$slug] = true;
            $out[] = [
                'title' => $title,
                'slug' => $slug,
                'entity' => $entity,
                'status' => 'draft',
                'applied' => false,
            ];
            if (count($out) >= 24) {
                break;
            }
        }

        return $out;
    }

    public static function guess(string $hint): string
    {
        $h = mb_strtolower($hint);

        return match (true) {
            str_contains($h, 'lieu') || str_contains($h, 'village') => 'lieu',
            str_contains($h, 'clan') || str_contains($h, 'orga') => 'organisation',
            str_contains($h, 'jutsu') || str_contains($h, 'technique') => 'jutsu',
            str_contains($h, 'arc') => 'arc',
            str_contains($h, 'relique') => 'relique',
            str_contains($h, 'oeuvre') => 'oeuvre',
            str_contains($h, 'produit') => 'produit',
            str_contains($h, 'offre') || str_contains($h, 'mission') => 'offre',
            str_contains($h, 'competence') => 'competence',
            str_contains($h, 'cours') => 'cours',
            str_contains($h, 'event') || str_contains($h, 'evenement') => 'evenement',
            str_contains($h, 'vehicule') || str_contains($h, 'voiture') => 'vehicule',
            str_contains($h, 'rituel') => 'rituel',
            str_contains($h, 'compte') => 'compte',
            str_contains($h, 'playbook') => 'playbook',
            str_contains($h, 'livrable') => 'livrable',
            str_contains($h, 'actif') || str_contains($h, 'token') => 'actif',
            str_contains($h, 'protocole') => 'protocole',
            str_contains($h, 'bien') => 'bien',
            str_contains($h, 'mandat') => 'mandat',
            str_contains($h, 'dossier') => 'dossier',
            str_contains($h, 'jurisprudence') => 'jurisprudence',
            str_contains($h, 'acte') => 'acte',
            default => 'personnage',
        };
    }
}
