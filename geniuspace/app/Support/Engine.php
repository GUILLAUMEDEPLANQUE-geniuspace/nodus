<?php

namespace App\Support;

use App\Models\Edge;
use App\Models\GpNode;
use Illuminate\Support\Facades\DB;

/**
 * Moteur secret : graphe + champs de fiche = unique enregistrement de la vérité métier.
 * Toute valeur produit (honneur, alignement, carnet, héritage, reco) en est dérivée.
 * Aucune surface n’expose Node / edge / CCK : elle affiche des lieux, des preuves, des décisions.
 */
class Engine
{
    /** @return array<string, object> keyed by field_key */
    public static function fields(string $nodeId, ?string $audience = 'fiche'): array
    {
        $q = DB::table('cck_fields')->where('node_id', $nodeId)->orderBy('sort');
        if ($audience !== null && \Illuminate\Support\Facades\Schema::hasColumn('cck_fields', 'audience')) {
            if ($audience === 'fiche') {
                $q->where(function ($w) {
                    $w->where('audience', 'fiche')->orWhere('audience', '')->orWhereNull('audience');
                });
            } else {
                $q->where('audience', $audience);
            }
        }
        $rows = $q->get();
        $out = [];
        foreach ($rows as $r) {
            $key = $r->field_key !== '' ? $r->field_key : \Illuminate\Support\Str::slug($r->name);
            $out[$key] = $r;
        }

        return $out;
    }

    /** Un seul aller SQL pour N fiches. */
    public static function fieldsFor(array $nodeIds): array
    {
        if (! $nodeIds) {
            return [];
        }
        $rows = DB::table('cck_fields')->whereIn('node_id', $nodeIds)->orderBy('sort')->get();
        $out = [];
        foreach ($rows as $r) {
            $key = $r->field_key !== '' ? $r->field_key : \Illuminate\Support\Str::slug($r->name);
            $out[$r->node_id][$key] = $r;
        }

        return $out;
    }

    public static function fill(string $nodeId, array $values): void
    {
        foreach ($values as $key => $v) {
            $row = is_array($v) ? $v : ['value' => $v];
            $q = DB::table('cck_fields')->where('node_id', $nodeId)->where('field_key', $key);
            if (! $q->exists()) {
                continue;
            }
            $patch = [];
            if (array_key_exists('value', $row)) {
                $patch['value'] = (string) $row['value'];
            }
            if (array_key_exists('min', $row)) {
                $patch['min_val'] = $row['min'];
            }
            if (array_key_exists('max', $row)) {
                $patch['max_val'] = $row['max'];
            }
            if ($patch) {
                $q->update($patch);
            }
        }
    }

    /**
     * Un enregistrement, plusieurs surfaces : badge, fiche, comparateur, JSON-LD.
     */
    public static function surface(object $f, string $where = 'fiche'): string
    {
        $unit = $f->unit ?? '';
        $min = $f->min_val;
        $max = $f->max_val;
        $isScale = $unit === 'k€' || ($f->field_key ?? '') === 'salaire';
        if ($isScale && $min && $max && (float) $min !== (float) $max) {
            $range = ((int) $min).'–'.((int) $max)."\u{00a0}k€";

            return match ($where) {
                'badge' => $range,
                'fiche' => 'Fourchette '.$range.' brut annuel',
                'compare', 'jsonld' => $range,
                default => $f->value !== '' ? (string) $f->value : $range,
            };
        }
        $val = (string) ($f->value ?? '');
        if ($val === '') {
            return '';
        }
        if ($where === 'fiche' && $unit === 'jours') {
            return 'Réponse sous '.(int) ($min ?: $val).' jours';
        }

        return $val;
    }

    public static function href(GpNode $n): string
    {
        if ($n->id === 'vera' || $n->slug === 'vera') {
            return '/n/vera';
        }
        if ($n->id === 'lumen' || $n->slug === 'lumen') {
            return '/n/lumen';
        }

        return match ($n->kind) {
            'job' => '/n/vera/offres/'.$n->slug,
            'company' => '/n/vera/maisons/'.preg_replace('/^maison-/', '', $n->slug),
            'person' => '/n/vera/carnet',
            'product' => '/n/lumen/f/'.$n->slug,
            default => '/n/'.$n->slug,
        };
    }

    /** Parents et enfants, phrases humaines. Jamais « parent_of ». */
    public static function neighbors(GpNode $n): array
    {
        $out = Edge::query()->where('from_id', $n->id)->get();
        $in = Edge::query()->where('to_id', $n->id)->get();
        $ids = $out->pluck('to_id')->merge($in->pluck('from_id'))->unique()->filter()->all();
        $nodes = $ids ? GpNode::query()->whereIn('id', $ids)->get()->keyBy('id') : collect();
        $fait = [];
        $contient = [];
        foreach ($in as $e) {
            $t = $nodes[$e->from_id] ?? null;
            if (! $t) {
                continue;
            }
            $fait[] = [
                'titre' => $t->title,
                'nature' => Vocab::kind($t->kind),
                'url' => url(self::href($t)),
                'lien' => match (true) {
                    $e->kind === 'validated' => 'Épreuve validée par',
                    $t->id === 'vera' => 'Listée sur',
                    default => Vocab::parentPhrase($t->kind),
                },
            ];
        }
        foreach ($out as $e) {
            $t = $nodes[$e->to_id] ?? null;
            if (! $t) {
                continue;
            }
            $contient[] = [
                'titre' => $t->title,
                'nature' => Vocab::kind($t->kind),
                'url' => url(self::href($t)),
                'lien' => $e->kind === 'validated' ? 'Épreuve validée' : Vocab::childPhrase($t->kind),
            ];
        }

        return ['fait_partie_de' => $fait, 'contient' => $contient];
    }

    /** Offre → Maison → univers. Recalculable depuis les arêtes. */
    public static function trail(GpNode $n): array
    {
        $crumbs = [];
        $parentIds = Edge::query()->where('to_id', $n->id)->pluck('from_id');
        $parents = $parentIds->isNotEmpty() ? GpNode::query()->whereIn('id', $parentIds)->get() : collect();
        $house = $parents->first(fn ($p) => $p->kind === 'company' && $p->id !== 'vera');
        $world = $parents->first(fn ($p) => in_array($p->id, ['vera', 'lumen', 'onepiece'], true))
            ?: $parents->firstWhere('featured', true);
        if ($world) {
            $crumbs[] = ['title' => $world->title, 'href' => self::href($world)];
            if ($n->kind === 'job') {
                $crumbs[] = ['title' => 'Offres', 'href' => '/n/'.$world->slug.'/offres'];
            }
        }
        if ($house && (! $world || $house->id !== $world->id)) {
            $crumbs[] = ['title' => $house->title, 'href' => self::href($house)];
        }
        $crumbs[] = ['title' => $n->title, 'href' => null];

        return $crumbs;
    }

    /**
     * Héritage parent → enfant. Une offre Relève lit le délai et la fiabilité
     * de la maison, sans config manuelle.
     */
    public static function inherit(GpNode $child): array
    {
        $parentIds = Edge::query()->where('to_id', $child->id)->pluck('from_id');
        $parents = $parentIds->isNotEmpty() ? GpNode::query()->whereIn('id', $parentIds)->get() : collect();
        $house = $parents->first(fn ($p) => $p->kind === 'company' && $p->id !== 'vera');
        $out = ['from' => null, 'delayDays' => null, 'honor' => null, 'delayLabel' => null, 'honorLabel' => null];
        if (! $house) {
            return $out;
        }
        $f = self::fields($house->id);
        $out['from'] = $house;
        if (isset($f['delai_reponse'])) {
            $out['delayDays'] = (int) ($f['delai_reponse']->min_val ?: $f['delai_reponse']->value);
            $out['delayLabel'] = self::surface($f['delai_reponse'], 'fiche');
        }
        if (isset($f['fiabilite'])) {
            $out['honor'] = (int) ($f['fiabilite']->min_val ?: $f['fiabilite']->value);
            $out['honorLabel'] = (string) $f['fiabilite']->value;
        }

        return $out;
    }

    /**
     * Alignement carnet × offre. Fonction pure sur tokens, jamais « N hops ».
     *
     * @param  list<string>  $proofs
     * @param  list<string>  $wanted
     * @return array{level: string, word: string, plain: string, hits: list<string>, missing: list<string>, next: ?string}
     */
    public static function alignment(array $proofs, array $wanted): array
    {
        $norm = fn (array $xs) => array_values(array_unique(array_filter(array_map(
            fn ($x) => mb_strtolower(trim((string) $x)),
            $xs
        ), fn ($x) => mb_strlen($x) > 2)));
        $p = $norm($proofs);
        $w = $norm($wanted);
        if (! $w) {
            return [
                'level' => 'moyen',
                'word' => 'Alignement moyen',
                'plain' => 'Pas assez de critères pour trancher. Le test le dira.',
                'hits' => [],
                'missing' => [],
                'next' => null,
            ];
        }
        $hits = [];
        foreach ($w as $need) {
            foreach ($p as $have) {
                if ($need === $have || str_contains($have, $need) || str_contains($need, $have)) {
                    $hits[] = $need;
                    break;
                }
            }
        }
        $hits = array_values(array_unique($hits));
        $missing = array_values(array_diff($w, $hits));
        $ratio = count($hits) / max(1, count($w));
        $level = $ratio >= 0.55 ? 'fort' : ($ratio >= 0.25 ? 'moyen' : 'faible');
        $plain = match ($level) {
            'fort' => 'Le geste de votre carnet correspond à ce que le poste demande.',
            'moyen' => 'Quelques preuves collent, d’autres manquent. Le test tranche.',
            default => 'Le carnet et le poste parlent deux métiers. Lisez le difficile avant de postuler.',
        };
        $next = self::missingSentence($level, $missing);
        if ($next) {
            $plain = $next;
        }

        return [
            'level' => $level,
            'word' => 'Alignement '.$level,
            'plain' => $plain,
            'hits' => $hits,
            'missing' => $missing,
            'next' => $next,
        ];
    }

    public static function align(string $carnetId, string $jobId): array
    {
        return self::alignment(self::proofTokens($carnetId), self::tokens($jobId));
    }

    /** « Aussi dans cet univers » — co-validation, puis compétences, puis fratrie. */
    public static function alsoInWorld(GpNode $n, int $limit = 3): array
    {
        $scores = [];
        $carnets = Edge::query()->where('to_id', $n->id)->where('kind', 'validated')->pluck('from_id');
        if ($carnets->isNotEmpty()) {
            $co = Edge::query()->whereIn('from_id', $carnets)->where('kind', 'validated')->where('to_id', '!=', $n->id)->pluck('to_id');
            foreach ($co as $id) {
                $scores[$id] = ($scores[$id] ?? 0) + 50;
            }
        }
        $parentIds = Edge::query()->where('to_id', $n->id)->pluck('from_id');
        $sibIds = $parentIds->isNotEmpty()
            ? Edge::query()->whereIn('from_id', $parentIds)->where('to_id', '!=', $n->id)->pluck('to_id')->unique()->all()
            : [];
        $mine = self::tokens($n->id);
        $candidates = array_unique(array_merge(array_keys($scores), $sibIds));
        if (! $candidates) {
            return [];
        }
        $nodes = GpNode::query()->whereIn('id', $candidates)->where('kind', $n->kind)->get();
        $scored = [];
        foreach ($nodes as $s) {
            $scored[] = ['n' => $s, 'score' => ($scores[$s->id] ?? 0) + self::overlap($mine, self::tokens($s->id))];
        }
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score'] ?: strcmp($a['n']->title, $b['n']->title));
        $top = array_slice($scored, 0, $limit);

        return array_map(fn ($x) => [
            'title' => $x['n']->title,
            'href' => self::href($x['n']),
            'nature' => Vocab::kind($x['n']->kind),
            'plain' => $x['n']->subtitle,
        ], $top);
    }

    /** Carnet = projection du graphe candidat (arêtes validées + détails). */
    public static function passport(string $carnetId): array
    {
        $node = GpNode::query()->find($carnetId);
        if (! $node) {
            return ['titre' => 'Carnet', 'preuves' => [], 'details' => []];
        }
        $validated = Edge::query()->where('from_id', $carnetId)->where('kind', 'validated')->get();
        $ids = $validated->pluck('to_id');
        $jobs = $ids->isNotEmpty() ? GpNode::query()->whereIn('id', $ids)->get()->keyBy('id') : collect();
        $preuves = [];
        foreach ($validated as $e) {
            $j = $jobs[$e->to_id] ?? null;
            if (! $j) {
                continue;
            }
            $house = self::inherit($j);
            $preuves[] = [
                'titre' => $j->title,
                'maison' => $house['from']->title ?? '',
                'quoi' => 'Épreuve validée',
                'href' => self::href($j),
            ];
        }
        $details = [];
        foreach (self::fields($carnetId) as $key => $f) {
            $details[] = ['key' => $key, 'label' => $f->name, 'value' => self::surface($f, 'fiche')];
        }

        return ['titre' => $node->title, 'preuves' => $preuves, 'details' => $details];
    }

    public static function orphans(): \Illuminate\Support\Collection
    {
        $parented = DB::table('edges')->pluck('to_id')->unique()->all();
        $roots = GpNode::query()->where('featured', true)->pluck('id')->all();

        return GpNode::query()
            ->whereNotIn('id', array_merge($parented, $roots))
            ->orderBy('title')
            ->get();
    }

    public static function publicFields(GpNode $n): array
    {
        $details = [];
        foreach (self::fields($n->id) as $key => $f) {
            if (($f->type ?? '') === 'password') {
                continue;
            }
            $details[] = [
                'key' => $key,
                'label' => $f->name,
                'value' => self::surface($f, 'fiche'),
                'unit' => $f->unit ?: null,
                'min' => $f->min_val,
                'max' => $f->max_val,
            ];
        }

        return [
            'fiche' => [
                'slug' => $n->slug,
                'titre' => $n->title,
                'nature' => Vocab::kind($n->kind),
                'url' => url(self::href($n)),
            ],
            'details' => $details,
        ];
    }

    /** @return list<string> */
    public static function tokens(string $nodeId): array
    {
        $f = self::fields($nodeId);
        $blob = mb_strtolower(implode(' ', array_map(
            fn ($k) => $f[$k]->value ?? '',
            ['stack', 'habilitation', 'caces', 'trois_huit', 'geste', 'metier', 'visa']
        )));
        $parts = preg_split('/[,\s\/·]+/u', $blob) ?: [];

        return array_values(array_filter($parts, fn ($w) => mb_strlen($w) > 2));
    }

    /** @return list<string> */
    public static function proofTokens(string $carnetId): array
    {
        $mine = self::tokens($carnetId);
        $validated = Edge::query()->where('from_id', $carnetId)->where('kind', 'validated')->pluck('to_id');
        foreach ($validated as $id) {
            $mine = array_merge($mine, self::tokens($id));
        }

        return array_values(array_unique($mine));
    }

    /** @param list<string> $a @param list<string> $b */
    public static function overlap(array $a, array $b): int
    {
        if (! $a || ! $b) {
            return 0;
        }
        $n = 0;
        foreach ($a as $x) {
            foreach ($b as $y) {
                if ($x === $y || str_contains($x, $y) || str_contains($y, $x)) {
                    $n++;
                    break;
                }
            }
        }

        return $n;
    }

    /**
     * @param  list<string>  $missing
     */
    private static function missingSentence(string $level, array $missing): ?string
    {
        if ($missing === []) {
            return null;
        }
        $need = self::proofList($missing);
        if (count($missing) === 1) {
            return $level === 'fort'
                ? 'Presque tout colle. Il vous manque encore la preuve « '.$need.' ».'
                : 'Il vous manque la preuve « '.$need.' » pour viser un alignement fort.';
        }

        return 'Il vous manque : '.$need.'.';
    }

    /**
     * @param  list<string>  $tokens
     */
    private static function proofList(array $tokens): string
    {
        $pretty = array_map(fn ($t) => self::proofLabel($t), array_slice($tokens, 0, 4));
        if (count($pretty) === 1) {
            return $pretty[0];
        }
        $last = array_pop($pretty);

        return implode(', ', $pretty).' et '.$last;
    }

    private static function proofLabel(string $token): string
    {
        $k = mb_strtolower(trim($token));
        $known = [
            'caces' => 'CACES',
            'gmao' => 'GMAO',
            'cac' => 'CAC',
            'figma' => 'Figma',
            'react' => 'React',
            'typescript' => 'TypeScript',
        ];
        if (isset($known[$k])) {
            return $known[$k];
        }

        return mb_convert_case(str_replace('_', ' ', $k), MB_CASE_TITLE, 'UTF-8');
    }

    /** Carnet visiteur : preuves graphe (si maison). Les déblocages mérités vivent dans le coffre à preuves. */
}
