<?php

namespace App\Support;

/**
 * Catalogue Vera — clone fidèle de github.com/GUILLAUMEDEPLANQUE-geniuspace/vera
 * Source : JSON extraits de seed-data.ts, offer-data.ts, glossary.ts, viviers.ts, etc.
 * Les vues Blade lisent ici (pas un campus générique).
 */
class VeraCatalog
{
    private static array $cache = [];

    public static function dir(): string
    {
        return app_path('Support/vera');
    }

    public static function json(string $name): array
    {
        if (! isset(self::$cache[$name])) {
            $path = self::dir().'/'.$name.'.json';
            self::$cache[$name] = is_file($path) ? (json_decode(file_get_contents($path), true) ?: []) : [];
        }
        return self::$cache[$name];
    }

    public static function companies(): array
    {
        $pact = self::json('pact');
        $out = [];
        foreach (self::json('companies') as $c) {
            $p = $pact[$c['slug']] ?? ['slaDays' => 10, 'honorScore' => 80, 'honorAnswered' => 0, 'honorDue' => 0];
            $out[$c['slug']] = $c + $p;
        }
        return $out;
    }

    public static function company(string $slug): ?array
    {
        return self::companies()[$slug] ?? null;
    }

    public static function jobs(): array
    {
        $cos = self::companies();
        $packs = self::json('packs');
        $out = [];
        foreach (self::json('jobs') as $j) {
            $co = $cos[$j['companySlug']] ?? ['slug' => $j['companySlug'], 'name' => $j['companySlug'], 'industry' => '', 'honorScore' => 80, 'slaDays' => 10, 'hqCity' => $j['city'] ?? '', 'cultureScore' => 80, 'hiringVelocity' => 'steady', 'tagline' => '', 'about' => '', 'values' => []];
            $j['company'] = $co;
            $j['pack'] = $packs[$j['slug']] ?? null;
            $j['full'] = isset($packs[$j['slug']]);
            $j['location'] = $j['city'].', '.$j['country'];
            $j['salaryLabel'] = self::salary($j['salaryMin'] ?? null, $j['salaryMax'] ?? null);
            $j['remoteLabel'] = self::REMOTE[$j['remoteType'] ?? ''] ?? ($j['remoteType'] ?? '');
            $j['contractLabel'] = self::CONTRACT[$j['contract'] ?? ''] ?? ($j['contract'] ?? '');
            $j['seniorityLabel'] = self::SENIORITY[$j['seniority'] ?? ''] ?? ($j['seniority'] ?? '');
            $j['scarcity'] = self::scarcity($j);
            $j['ppqc'] = self::ppqc($j);
            $j['processHours'] = $j['processHours'] ?? match ($j['seniority'] ?? 'mid') {
                'junior' => 5, 'staff', 'lead' => 14, 'senior' => 10, default => 8,
            };
            $j['pass'] = (($j['ghostRisk'] ?? '') === 'high') || (($co['honorScore'] ?? 80) < 65);
            $j['honorCaption'] = self::honorCaption($co['honorScore'] ?? 80, $co['honorDue'] ?? 1);
            $j['honorTone'] = self::honorTone($co['honorScore'] ?? 80);
            $out[] = $j;
        }
        return $out;
    }

    public static function job(string $slug): ?array
    {
        foreach (self::jobs() as $j) {
            if ($j['slug'] === $slug) {
                return $j;
            }
        }
        return null;
    }

    public static function filter(array $q): array
    {
        $jobs = self::jobs();
        if (! empty($q['q'])) {
            $s = mb_strtolower($q['q']);
            $jobs = array_values(array_filter($jobs, fn ($j) => str_contains(mb_strtolower($j['title'].' '.$j['company']['name'].' '.implode(' ', $j['skills'])), $s)));
        }
        if (! empty($q['remote'])) {
            $jobs = array_values(array_filter($jobs, fn ($j) => $j['remoteType'] === $q['remote']));
        }
        if (! empty($q['contract'])) {
            $jobs = array_values(array_filter($jobs, fn ($j) => $j['contract'] === $q['contract']));
        }
        if (! empty($q['seniority'])) {
            $jobs = array_values(array_filter($jobs, fn ($j) => $j['seniority'] === $q['seniority']));
        }
        if (! empty($q['collection'])) {
            $jobs = array_values(array_filter($jobs, fn ($j) => ($j['collection'] ?? '') === $q['collection']));
        }
        if (($q['pacte'] ?? '') === 'solide') {
            $jobs = array_values(array_filter($jobs, fn ($j) => ($j['company']['honorScore'] ?? 0) >= 88));
        }
        if (! empty($q['country'])) {
            $jobs = array_values(array_filter($jobs, fn ($j) => strcasecmp($j['country'], $q['country']) === 0));
        }
        $sort = $q['sort'] ?? 'signal';
        usort($jobs, function ($a, $b) use ($sort) {
            return match ($sort) {
                'salary' => ($b['salaryMax'] ?? 0) <=> ($a['salaryMax'] ?? 0),
                'recent' => ($a['daysAgo'] ?? 99) <=> ($b['daysAgo'] ?? 99),
                'honneur' => ($b['company']['honorScore'] ?? 0) <=> ($a['company']['honorScore'] ?? 0),
                default => ($b['scarcity']['score'] <=> $a['scarcity']['score']) ?: (($a['daysAgo'] ?? 9) <=> ($b['daysAgo'] ?? 9)),
            };
        });
        return $jobs;
    }

    public static function pulse(): array
    {
        $jobs = self::jobs();
        $n = max(1, count($jobs));
        $published = count(array_filter($jobs, fn ($j) => ($j['salaryMin'] ?? 0) > 0));
        $ghost = count(array_filter($jobs, fn ($j) => ($j['ghostRisk'] ?? '') !== 'low'));
        $mids = array_map(fn ($j) => (int) ((($j['salaryMin'] ?? 0) + ($j['salaryMax'] ?? 0)) / 2), array_filter($jobs, fn ($j) => ($j['salaryMin'] ?? 0) > 0));
        sort($mids);
        $median = $mids ? $mids[(int) floor(count($mids) / 2)] : 0;
        return [
            'activeJobs' => count($jobs),
            'salaryPublishedPct' => (int) round(100 * $published / $n),
            'ghostFlagged' => $ghost,
            'medianSalary' => $median,
            'medianLabel' => self::salary($median, $median),
        ];
    }

    public static function honorLeague(): array
    {
        $cos = array_values(self::companies());
        usort($cos, fn ($a, $b) => ($b['honorScore'] ?? 0) <=> ($a['honorScore'] ?? 0));
        return $cos;
    }

    public static function salary(?int $min, ?int $max): string
    {
        if (! $min && ! $max) {
            return 'Salaire non publié';
        }
        $k = fn ($n) => (int) round($n / 1000)."\u{00a0}k€";
        if ($min && $max && $min !== $max) {
            return $k($min).'–'.$k($max);
        }
        return $k($min ?: $max);
    }

    public static function ppqc(array $job): array
    {
        $city = $job['city'] ?? '';
        $tension = match (true) {
            in_array($city, ['Fos-sur-Mer', 'Marseille', 'Toulouse'], true) => 78,
            in_array($city, ['Lyon', 'Lille', 'Amsterdam'], true) => 64,
            in_array($city, ['Lisbonne', 'Lisbon', 'Dublin', 'Berlin', 'Munich'], true) => 58,
            default => 52,
        };
        $euros = 160 + (int) round($tension * 4.2);
        $euros += match ($job['seniority'] ?? 'mid') {
            'staff', 'lead' => 220,
            'senior' => 90,
            'junior' => -40,
            default => 0,
        };
        $t = mb_strtolower($job['title'] ?? '');
        if (preg_match('/asie|mandarin|guidage|nucl/u', $t)) {
            $euros += 140;
        }
        $euros = max(120, min(980, $euros));
        $why = $tension >= 75
            ? "Bassin tendu ({$tension}/100) à {$city}. Un profil qui réussit le test coûte plus, le sourcing moins."
            : ($tension >= 55
                ? "Tension du bassin {$tension}/100. Prix selon le lieu, pas un clic Indeed."
                : "Tension {$tension}/100. Plus de candidats : le tarif reste bas, le test filtre.");
        return compact('euros', 'tension', 'why');
    }

    public static function scarcity(array $job): array
    {
        $rare = ['gnc', 'ada', 'rtos', 'fhir', 'hl7', 'guanxi', 'mandarin', 'clickhouse', 'ebpf', 'postgis', 'habilitation', 'caces', 'hydraulique', 'aml', 'recherche opérationnelle', 'embarqué'];
        $score = 42;
        foreach ($job['skills'] ?? [] as $s) {
            if (in_array(mb_strtolower($s), $rare, true)) {
                $score += 12;
            }
        }
        $score += match ($job['seniority'] ?? 'mid') {
            'staff', 'lead' => 18,
            'senior' => 8,
            'junior' => -10,
            default => 0,
        };
        $views = max(1, (int) ($job['views'] ?? 1));
        $conv = ((int) ($job['applicants'] ?? 0)) / $views;
        if ($conv < 0.04) {
            $score += 14;
        } elseif ($conv > 0.12) {
            $score -= 10;
        }
        $t = mb_strtolower($job['title'] ?? '');
        if (str_contains($t, 'asie') || str_contains($t, 'mandarin') || str_contains($t, 'guidage') || str_contains($t, 'maintenance')) {
            $score += 10;
        }
        if (($job['remoteType'] ?? '') === 'onsite' && in_array($job['city'] ?? '', ['Fos-sur-Mer', 'Toulouse', 'Marseille'], true)) {
            $score += 6;
        }
        $score = max(8, min(98, (int) round($score)));
        $band = $score >= 78 ? 'penurie' : ($score >= 62 ? 'rare' : ($score >= 45 ? 'tendu' : 'abondant'));
        $label = match ($band) {
            'penurie' => 'Pénurie',
            'rare' => 'Profil rare',
            'tendu' => 'Marché tendu',
            default => 'Profil fréquent',
        };
        $why = match ($band) {
            'penurie' => 'Peu de candidats tenables, compétences rares. Les entreprises sérieuses paient au-dessus du P75 et répondent vite — ou perdent.',
            'rare' => 'Le vivier est étroit. Un process long ou un salaire sous médiane tue l’offre.',
            'tendu' => 'On trouve, mais pas en trois jours. Le délai de réponse pèse plus que le sourcing.',
            default => 'Beaucoup de CV. Le filtre (test, grille, une page) sert surtout à éviter le bruit.',
        };
        return compact('score', 'band', 'label', 'why');
    }

    public static function honorTone(int $score): string
    {
        return $score >= 88 ? 'good' : ($score >= 72 ? 'warn' : 'bad');
    }

    public static function honorCaption(int $score, int $due): string
    {
        if ($due === 0) {
            return 'Nouveau';
        }
        if ($score >= 94) {
            return 'Toujours à l’heure';
        }
        if ($score >= 82) {
            return 'Répond à l’heure';
        }
        if ($score >= 70) {
            return 'Parfois en retard';
        }
        return 'Rate les délais';
    }

    public static function payPosition(array $job): ?array
    {
        $pack = $job['pack'] ?? null;
        if (! $pack || empty($pack['pay'])) {
            return null;
        }
        $min = $job['salaryMin'] ?? null;
        $max = $job['salaryMax'] ?? null;
        if (! $min && ! $max) {
            return null;
        }
        $mid = $min && $max ? (int) round(($min + $max) / 2) : ($min ?: $max);
        $p50 = (int) $pack['pay']['p50'];
        $delta = (int) round((($mid - $p50) / max(1, $p50)) * 100);
        $band = $mid >= $pack['pay']['p75'] ? 'above' : ($mid <= $pack['pay']['p25'] ? 'below' : 'market');
        $label = match ($band) {
            'above' => abs($delta)."\u{00a0}% au-dessus de la médiane",
            'below' => abs($delta)."\u{00a0}% sous la médiane",
            default => 'Dans le marché',
        };
        return compact('band', 'label', 'delta', 'mid');
    }

    public static function jobPostingLd(array $job): array
    {
        $url = url('/n/vera/offres/'.$job['slug']);
        return [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job['title'],
            'description' => $job['description'],
            'datePosted' => now()->subDays((int) ($job['daysAgo'] ?? 3))->toDateString(),
            'employmentType' => strtoupper($job['contract'] ?? 'FULL_TIME'),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $job['company']['name'],
                'sameAs' => $job['company']['website'] ?? null,
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job['city'],
                    'addressCountry' => $job['country'],
                ],
            ],
            'baseSalary' => [
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => $job['salaryMin'] ?? null,
                    'maxValue' => $job['salaryMax'] ?? null,
                    'unitText' => 'YEAR',
                ],
            ],
            'directApply' => true,
            'url' => $url,
            'identifier' => $job['slug'],
            'skills' => implode(', ', $job['skills'] ?? []),
        ];
    }

    public const REMOTE = ['remote' => 'Télétravail', 'hybrid' => 'Hybride', 'onsite' => 'Sur site'];
    public const CONTRACT = ['cdi' => 'CDI', 'cdd' => 'CDD', 'freelance' => 'Freelance', 'stage' => 'Stage', 'alternance' => 'Alternance'];
    public const SENIORITY = ['junior' => 'Junior', 'mid' => 'Confirmé', 'senior' => 'Senior', 'staff' => 'Staff', 'lead' => 'Lead'];

    /** Libellés d’interface : français d’abord. Les noms historiques restent dans le lexique. */
    public static function nav(): array
    {
        return [
            ['to' => '/n/vera/offres', 'key' => 'offres', 'label' => 'Offres'],
            ['to' => '/n/vera/europe', 'key' => 'europe', 'label' => 'Europe'],
            ['to' => '/n/vera/preuve', 'key' => 'preuve', 'label' => 'Tests métier'],
            ['to' => '/n/vera/carnet', 'key' => 'passport', 'label' => 'Mon carnet'],
            ['to' => '/n/vera/entreprises', 'key' => 'entreprises', 'label' => 'Entreprises'],
        ];
    }

    /** @return array{word:string,plain:string} */
    public static function say(string $key): array
    {
        return match ($key) {
            'verdict' => ['word' => 'Conseil', 'plain' => 'Allez, demandez, ou passez — avant d’écrire une candidature.'],
            'pacte' => ['word' => 'Délai de réponse', 'plain' => 'Une date écrite. Si l’entreprise rate, ça se voit.'],
            'brief' => ['word' => 'Une page à la place du CV', 'plain' => 'Livré, refusé, suite. Pas quatre pages.'],
            'ppqc' => ['word' => 'Candidat qualifié', 'plain' => 'L’entreprise ne paie que si quelqu’un a réussi le test.'],
            'epreuve' => ['word' => 'Test métier', 'plain' => 'Simulation de 6 minutes. Les coordonnées après, pas avant.'],
            'passport' => ['word' => 'Carnet de preuves', 'plain' => 'Les tests réussis, exportables. Pas un PDF LinkedIn.'],
            'honneur' => ['word' => 'Fiabilité', 'plain' => 'Note publique : elles répondent à l’heure, ou pas.'],
            'vivier' => ['word' => 'Profils oubliés', 'plain' => 'Seniors à la journée, RSA, multi-activité — pas un vivier CRM.'],
            'savoirs' => ['word' => 'Fiches métier', 'plain' => 'Guides liés aux offres. Si le geste manque, on l’apprend ici.'],
            'talent' => ['word' => 'Le geste', 'plain' => 'Ce que vous savez faire, pas le titre sur LinkedIn.'],
            'scarcity' => ['word' => 'Pénurie', 'plain' => 'Est-ce que ce métier se trouve, ou pas, dans ce bassin.'],
            default => ['word' => $key, 'plain' => ''],
        };
    }
}
