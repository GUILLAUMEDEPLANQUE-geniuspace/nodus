<?php

namespace App\Support;

use App\Models\GpNode;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Index lexical du lieu. BM25 + vecteur hashing-trick. Pas un embedding LLM.
 *
 * Chaque hit porte une couche : world | evidence | belief.
 * Le tribunal ne cite jamais belief comme preuve du monde.
 */
class GhostCortex
{
    public const WORLD = 'world';

    public const EVIDENCE = 'evidence';

    public const BELIEF = 'belief';

    public const DIMS = 64;

    public const MIN_SCORE = 0.18;

    /** @var list<string> */
    private const STOP = [
        'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'au', 'aux', 'et', 'ou', 'en', 'dans', 'sur',
        'avec', 'pour', 'par', 'ce', 'cet', 'cette', 'qui', 'que', 'pas', 'ne', 'se', 'sa', 'son',
        'the', 'a', 'an', 'and', 'or', 'in', 'on', 'to', 'of', 'for', 'is', 'are', 'was', 'were',
        'je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'elles', 'mon', 'ton', 'est', 'une',
    ];

    /**
     * @return list<array{id:string, layer:string, text:string, title:string, source:string, updated_at:int}>
     */
    public static function docs(GpNode $node): array
    {
        $out = [];
        foreach (GhostMemory::worldTruth($node) as $i => $f) {
            $text = trim(($f['predicate'] ?? '').' '.($f['object'] ?? ''));
            if ($text === '') {
                continue;
            }
            $out[] = self::doc('world:'.$i, self::WORLD, $text, (string) ($f['predicate'] ?? 'champ'), 'engine', time());
        }
        foreach (Product::query()->where('node_id', $node->id)->limit(24)->get() as $p) {
            $price = str_replace(' ', '', (string) $p->price);
            $text = trim($p->title.' '.$price.' '.(string) $p->summary);
            $out[] = self::doc('product:'.$p->id, self::WORLD, $text, (string) $p->title, 'product', time());
        }
        foreach (Media::query()->where('node_id', $node->id)->limit(16)->get() as $m) {
            $text = trim($m->title.' '.(string) ($m->transcript ?? '').' '.(string) ($m->price ?? ''));
            $out[] = self::doc('media:'.$m->id, self::WORLD, $text, (string) $m->title, 'media', time());
        }
        if (Schema::hasTable('ghost_chunks')) {
            foreach (DB::table('ghost_chunks')->where('node_id', $node->id)->orderByDesc('created_at')->limit(80)->get() as $c) {
                $out[] = self::doc((string) $c->id, self::EVIDENCE, (string) $c->text, Str::limit((string) $c->text, 48), (string) $c->source_kind, strtotime((string) $c->created_at) ?: time());
            }
        }
        foreach (GhostMemory::agentBelief($node) as $i => $f) {
            $text = trim(($f['predicate'] ?? '').' '.($f['object'] ?? ''));
            if ($text === '') {
                continue;
            }
            $out[] = self::doc('belief:'.$i, self::BELIEF, $text, (string) ($f['predicate'] ?? 'croyance'), 'visitor', time());
        }

        return $out;
    }

    /**
     * Relie les termes d’un même document. N’écrit pas le monde.
     */
    public static function ingestWorld(GpNode $node): int
    {
        $n = 0;
        foreach (self::docs($node) as $doc) {
            if ($doc['layer'] === self::BELIEF) {
                continue;
            }
            $tags = self::tags($doc['text'], 6);
            $from = $doc['id'];
            foreach ($tags as $tag) {
                GhostSynapse::reinforce((string) $node->id, $from, 'tag:'.$tag, 'TAG', 0.12);
                $n++;
            }
            for ($i = 0; $i < count($tags); $i++) {
                for ($j = $i + 1; $j < count($tags); $j++) {
                    GhostSynapse::reinforce((string) $node->id, 'tag:'.$tags[$i], 'tag:'.$tags[$j], 'CO_OCCURRENCE', 0.08);
                    $n++;
                }
            }
        }

        return $n;
    }

    /**
     * @param  list<string>|null  $layers
     * @return list<array{id:string, layer:string, text:string, title:string, source:string, score:float, lexical:float, semantic:float, recency:float, graph:float}>
     */
    public static function search(GpNode $node, string $query, int $limit = 6, ?array $layers = null, float $min = self::MIN_SCORE): array
    {
        $docs = self::docs($node);
        if ($layers) {
            $docs = array_values(array_filter($docs, fn ($d) => in_array($d['layer'], $layers, true)));
        }
        $qTokens = self::tokens($query);
        if ($qTokens === [] || $docs === []) {
            return [];
        }
        $N = count($docs);
        $df = [];
        $tfDocs = [];
        foreach ($docs as $i => $d) {
            $tf = self::termFreq(self::tokens($d['text']));
            $tfDocs[$i] = $tf;
            foreach (array_keys($tf) as $t) {
                $df[$t] = ($df[$t] ?? 0) + 1;
            }
        }
        $qVec = self::hashVector($query);
        $qTf = self::termFreq($qTokens);
        $lexRaw = [];
        foreach ($docs as $i => $d) {
            $s = 0.0;
            foreach ($qTf as $term => $_) {
                $tf = $tfDocs[$i][$term] ?? 0;
                if ($tf <= 0) {
                    continue;
                }
                $idf = self::idf($N, $df[$term] ?? 0);
                $s += $idf * (1 + log($tf));
            }
            $lexRaw[$i] = $s;
        }
        $maxLex = max($lexRaw ?: [0]);
        $now = time();
        $hits = [];
        foreach ($docs as $i => $d) {
            $lexical = $maxLex > 0 ? $lexRaw[$i] / $maxLex : 0.0;
            $semantic = self::cosine($qVec, self::hashVector($d['text']));
            $ageDays = max(0, ($now - (int) $d['updated_at']) / 86400);
            $recency = exp(-$ageDays / 30);
            $graph = GhostSynapse::boost((string) $node->id, $qTokens, (string) $d['id']);
            $score = 0.50 * $lexical + 0.25 * $semantic + 0.15 * $recency + 0.10 * $graph;
            if ($score < $min) {
                continue;
            }
            $hits[] = [
                'id' => $d['id'],
                'layer' => $d['layer'],
                'text' => $d['text'],
                'title' => $d['title'],
                'source' => $d['source'],
                'score' => round($score, 4),
                'lexical' => round($lexical, 4),
                'semantic' => round($semantic, 4),
                'recency' => round($recency, 4),
                'graph' => round($graph, 4),
            ];
        }
        usort($hits, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($hits, 0, $limit);
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        $norm = mb_strtolower($text);
        $norm = strtr($norm, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $norm) ?: [];
        $out = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) < 2 || in_array($p, self::STOP, true) || ctype_digit($p)) {
                continue;
            }
            $out[] = $p;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function tags(string $text, int $n = 6): array
    {
        $tf = self::termFreq(self::tokens($text));
        arsort($tf);
        $tags = [];
        foreach ($tf as $t => $_) {
            if (mb_strlen($t) < 4) {
                continue;
            }
            $tags[] = $t;
            if (count($tags) >= $n) {
                break;
            }
        }

        return $tags;
    }

    /**
     * Hashing-trick (signed TF). Pas une observation du monde.
     *
     * @return list<float>
     */
    public static function hashVector(string $text): array
    {
        $tf = self::termFreq(self::tokens($text));
        $vec = array_fill(0, self::DIMS, 0.0);
        if ($tf === []) {
            return $vec;
        }
        foreach ($tf as $tok => $count) {
            $h = (int) sprintf('%u', crc32((string) $tok));
            $idx = $h % self::DIMS;
            $sign = ($h & 1) === 0 ? 1.0 : -1.0;
            $vec[$idx] += $sign * sqrt((float) $count);
        }
        $norm = 0.0;
        foreach ($vec as $v) {
            $norm += $v * $v;
        }
        $norm = sqrt($norm) ?: 1.0;
        foreach ($vec as $i => $v) {
            $vec[$i] = $v / $norm;
        }

        return $vec;
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
        }

        return max(0.0, min(1.0, $dot));
    }

    public static function idf(int $n, int $df): float
    {
        return log(1 + ($n - $df + 0.5) / ($df + 0.5));
    }

    /**
     * @param  list<string>  $tokens
     * @return array<string, int>
     */
    public static function termFreq(array $tokens): array
    {
        $tf = [];
        foreach ($tokens as $t) {
            $tf[$t] = ($tf[$t] ?? 0) + 1;
        }

        return $tf;
    }

    /**
     * @return array{id:string, layer:string, text:string, title:string, source:string, updated_at:int}
     */
    private static function doc(string $id, string $layer, string $text, string $title, string $source, int $at): array
    {
        return [
            'id' => $id,
            'layer' => $layer,
            'text' => $text,
            'title' => $title,
            'source' => $source,
            'updated_at' => $at,
        ];
    }
}
