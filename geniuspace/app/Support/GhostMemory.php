<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Mémoire structurée — pas un dump de conversations.
 *
 * Fait = (subject, predicate, object) + confidence + source + status.
 * Une hypothèse n'est jamais un fait. Une hallucination n'entre pas ici.
 */
class GhostMemory
{
    public const KNOWN = 'known';

    public const INFERRED = 'inferred';

    public const UNCERTAIN = 'uncertain';

    public const CONTRADICTED = 'contradicted';

    public const UNKNOWN = 'unknown';

    /**
     * @return array{goal:?string, constraints:array, observations:list<array>, hypotheses:list<array>, plan:list<array>}
     */
    public static function load(GpNode $node): array
    {
        $bag = session()->get(self::key($node), [
            'goal' => null,
            'constraints' => [],
            'observations' => [],
            'hypotheses' => [],
            'plan' => [],
        ]);
        $bag['facts'] = self::factsForVisitor($node);

        return $bag;
    }

    public static function save(GpNode $node, array $working): void
    {
        session()->put(self::key($node), [
            'goal' => $working['goal'] ?? null,
            'constraints' => $working['constraints'] ?? [],
            'observations' => array_slice($working['observations'] ?? [], -12),
            'hypotheses' => array_slice($working['hypotheses'] ?? [], -8),
            'plan' => $working['plan'] ?? [],
        ]);
    }

    /**
     * Extraire des faits explicites du message. Jamais d'invention.
     *
     * @param  array<string, mixed>  $working
     */
    public static function ingest(GpNode $node, string $message, array &$working): void
    {
        $m = mb_strtolower($message);

        if (preg_match('/(?:moins de|max(?:imum)?|autour de|budget|jusqu.?à)\s*(\d{2,5})/u', $m, $hit)) {
            $n = (int) $hit[1];
            self::remember($node, 'visitor', 'budget_max', (string) $n, 0.9, 'explicit', self::KNOWN);
            $working['constraints']['max_price'] = $n;
        }

        if (preg_match('/(?:je préfère|plutôt|j.?aime)\s+(?:les? |l[ae] )?(?:choses )?(sombre|clair|simple|vintage|minimal|dark|classique)/u', $m, $hit)) {
            $style = $hit[1] === 'dark' ? 'sombre' : $hit[1];
            self::remember($node, 'visitor', 'prefers_style', $style, 0.91, 'explicit', self::KNOWN);
            $working['constraints']['style'] = $style;
        }

        if (preg_match('/(?:pas trop|je n.aime pas|évite|sans)\s+(?:les? |l[ae] )?(complexe|week-?end|nuit|sombre|flashy)/u', $m, $hit)) {
            self::remember($node, 'visitor', 'dislikes', $hit[1], 0.84, 'explicit', self::KNOWN);
            $working['constraints']['avoid'] = $hit[1];
        }

        if (preg_match('/disponible|rapidement|vite|tout de suite/u', $m)) {
            self::remember($node, 'visitor', 'availability', 'fast', 0.72, 'explicit', self::KNOWN);
            $working['constraints']['availability'] = 'fast';
        }

        if (preg_match('/(\d{3,5})\s*(?:€|euros?)?/u', $m, $hit) && preg_match('/offre|propose|prend|n[eé]goc/u', $m)) {
            self::remember($node, 'visitor', 'last_offer', $hit[1], 0.88, 'explicit', self::KNOWN);
        }
    }

    public static function remember(
        GpNode $node,
        string $subject,
        string $predicate,
        string $object,
        float $confidence,
        string $source,
        string $status
    ): void {
        if ($source === 'hallucination' || $status === self::UNKNOWN) {
            return;
        }
        $row = [
            'node_id' => $node->id,
            'session_id' => Grantor::guestId(),
            'user_id' => auth()->id(),
            'subject' => Str::limit($subject, 80),
            'predicate' => Str::limit($predicate, 80),
            'object' => Str::limit($object, 240),
            'confidence' => max(0, min(1, $confidence)),
            'source' => $source,
            'status' => $status,
            'last_confirmed_at' => now(),
            'updated_at' => now(),
        ];
        if (! Schema::hasTable('ghost_facts')) {
            $facts = session()->get(self::factsKey($node), []);
            $facts[$predicate] = $row + ['created_at' => now()->toIso8601String()];
            session()->put(self::factsKey($node), $facts);

            return;
        }
        try {
            $existing = DB::table('ghost_facts')
                ->where('node_id', $node->id)
                ->where('session_id', Grantor::guestId())
                ->where('subject', $subject)
                ->where('predicate', $predicate)
                ->first();
            if ($existing && (string) $existing->object !== $object) {
                $row['status'] = self::CONTRADICTED;
                $row['confidence'] = min(0.55, $confidence);
            }
            DB::table('ghost_facts')->updateOrInsert(
                [
                    'node_id' => $node->id,
                    'session_id' => Grantor::guestId(),
                    'subject' => $subject,
                    'predicate' => $predicate,
                ],
                $row + ['created_at' => $existing->created_at ?? now()]
            );
        } catch (\Throwable $e) {
            // non bloquant
        }
    }

    /**
     * @return list<array{predicate:string, object:string, confidence:float, source:string, status:string}>
     */
    public static function factsForVisitor(GpNode $node): array
    {
        if (! Schema::hasTable('ghost_facts')) {
            return array_values(session()->get(self::factsKey($node), []));
        }
        try {
            return DB::table('ghost_facts')
                ->where('node_id', $node->id)
                ->where('session_id', Grantor::guestId())
                ->orderByDesc('last_confirmed_at')
                ->limit(24)
                ->get()
                ->map(fn ($r) => [
                    'subject' => $r->subject,
                    'predicate' => $r->predicate,
                    'object' => $r->object,
                    'confidence' => (float) $r->confidence,
                    'source' => $r->source,
                    'status' => $r->status,
                ])
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Faits exposés à l'UI (sans jargon).
     *
     * @return list<array{label:string, value:string, confidence:float, status:string}>
     */
    public static function publicFacts(GpNode $node): array
    {
        $labels = [
            'budget_max' => 'Budget max',
            'prefers_style' => 'Préfère',
            'dislikes' => 'Évite',
            'availability' => 'Dispo',
            'last_offer' => 'Dernière offre',
        ];
        $out = [];
        foreach (self::factsForVisitor($node) as $f) {
            $pred = $f['predicate'] ?? '';
            if (! isset($labels[$pred])) {
                continue;
            }
            $out[] = [
                'label' => $labels[$pred],
                'value' => (string) ($f['object'] ?? ''),
                'confidence' => (float) ($f['confidence'] ?? 0),
                'status' => (string) ($f['status'] ?? self::KNOWN),
            ];
        }

        return $out;
    }

    public static function fact(GpNode $node, string $predicate): ?string
    {
        foreach (self::factsForVisitor($node) as $f) {
            if (($f['predicate'] ?? '') === $predicate && ($f['status'] ?? '') !== self::CONTRADICTED) {
                return (string) $f['object'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, mixed>  $exec
     * @param  array<string, mixed>  $check
     */
    public static function rememberTurn(GpNode $node, array $plan, array $exec, array $check): void
    {
        $working = self::load($node);
        $working['goal'] = $plan['goal'] ?? null;
        $working['plan'] = $plan['steps'] ?? [];
        $working['observations'][] = [
            'tools' => $exec['tools'] ?? [],
            'at' => now()->toIso8601String(),
        ];
        self::save($node, $working);

        if (! Schema::hasTable('ghost_experiences')) {
            return;
        }
        try {
            DB::table('ghost_experiences')->insert([
                'node_id' => $node->id,
                'session_id' => Grantor::guestId(),
                'kind' => 'turn',
                'payload' => json_encode([
                    'goal' => $plan['goal'] ?? null,
                    'skill' => $plan['skill'] ?? null,
                    'tools' => $exec['tools'] ?? [],
                    'valid' => $check['valid'] ?? true,
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // non bloquant
        }

        foreach ($exec['tools'] ?? [] as $tool) {
            self::touchTool((string) $tool, (bool) ($check['valid'] ?? true));
        }
    }

    private static function touchTool(string $tool, bool $ok): void
    {
        if (! Schema::hasTable('ghost_tool_stats') || $tool === '') {
            return;
        }
        try {
            $row = DB::table('ghost_tool_stats')->where('tool', $tool)->first();
            DB::table('ghost_tool_stats')->updateOrInsert(
                ['tool' => $tool],
                [
                    'success' => (int) ($row->success ?? 0) + ($ok ? 1 : 0),
                    'fail' => (int) ($row->fail ?? 0) + ($ok ? 0 : 1),
                    'updated_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // non bloquant
        }
    }

    private static function key(GpNode $node): string
    {
        return 'ghost_working.'.Grantor::guestId().'.'.$node->id;
    }

    private static function factsKey(GpNode $node): string
    {
        return 'ghost_facts.'.Grantor::guestId().'.'.$node->id;
    }
}
