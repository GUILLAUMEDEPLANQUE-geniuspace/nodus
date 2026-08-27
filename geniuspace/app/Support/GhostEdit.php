<?php

namespace App\Support;

use App\Models\GpNode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * DSL d’édition. Ghost ne touche jamais cck_fields à la main.
 * Spatial : after / before. PLAN ≠ APPLY. Undo = snapshot.
 */
class GhostEdit
{
    public const MEDIA = [
        ['id' => 'm-atelier-nuit', 'title' => 'Atelier de nuit', 'kind' => 'image', 'tags' => ['atelier', 'nuit']],
        ['id' => 'm-atelier-jour', 'title' => 'Atelier, jour', 'kind' => 'image', 'tags' => ['atelier', 'jour']],
        ['id' => 'm-cel', 'title' => 'Cel plan 14', 'kind' => 'image', 'tags' => ['cel', 'relique']],
    ];

    public const PLAYLISTS = [
        ['id' => 'pl-ambient', 'title' => 'Ambient 01'],
        ['id' => 'pl-foley', 'title' => 'Foley plateau 3'],
    ];

    public const INDUSTRY = [
        ['type' => 'text', 'key' => 'presentation', 'label' => 'Présentation', 'value' => 'L’atelier tient l’offre. Salaire en clair.', 'catalog' => 'text'],
        ['type' => 'image', 'key' => 'visuel', 'label' => 'Image principale', 'catalog' => 'image'],
        ['type' => 'field', 'key' => 'salaire', 'label' => 'Salaire', 'value' => '2 180 – 2 420 €', 'catalog' => 'digits'],
        ['type' => 'field', 'key' => 'contrat', 'label' => 'Contrat', 'value' => 'CDI', 'catalog' => 'select'],
        ['type' => 'field', 'key' => 'teletravail', 'label' => 'Télétravail', 'value' => 'Sur site', 'catalog' => 'select'],
        ['type' => 'field', 'key' => 'competences', 'label' => 'Compétences', 'value' => 'Habilitation · CACES', 'catalog' => 'text'],
        ['type' => 'video', 'key' => 'video', 'label' => 'Vidéo métier', 'catalog' => 'video'],
        ['type' => 'playlist', 'key' => 'ambiance', 'label' => 'Ambient 01', 'catalog' => 'audio'],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function seed(): array
    {
        return [
            ['id' => 'b1', 'type' => 'text', 'key' => 'presentation', 'label' => 'Présentation', 'value' => 'Offre industrie — l’atelier, pas le discours.'],
            ['id' => 'b2', 'type' => 'field', 'key' => 'contrat', 'label' => 'Contrat', 'value' => 'CDI'],
            ['id' => 'b3', 'type' => 'field', 'key' => 'teletravail', 'label' => 'Télétravail', 'value' => 'Sur site'],
            ['id' => 'b4', 'type' => 'image', 'key' => 'visuel', 'label' => 'Atelier', 'mediaId' => 'm-atelier-nuit'],
            ['id' => 'b5', 'type' => 'field', 'key' => 'competences', 'label' => 'Compétences', 'value' => 'CACES · 3×8'],
        ];
    }

    public static function fold(string $s): string
    {
        $s = mb_strtolower($s);
        $map = ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ç' => 'c', 'î' => 'i', 'ï' => 'i', '’' => "'", '‘' => "'"];

        return strtr($s, $map);
    }

    public static function looksLike(string $message): bool
    {
        $m = self::fold($message);

        return (bool) preg_match('/ajoute.{0,40}(champ|salaire)|mets.{0,24}(photo|image|playlist)|playlist|fiche.{0,24}industrie|offre industrielle|deplace|rembourse/u', $m);
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function findBlock(array $blocks, string $needle): ?array
    {
        $n = self::fold($needle);
        foreach ($blocks as $b) {
            if (($b['id'] ?? '') === $needle || ($b['key'] ?? '') === $needle) {
                return $b;
            }
            if (self::fold($b['label'] ?? '') === $n || str_contains(self::fold($b['label'] ?? ''), $n)) {
                return $b;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function resolveAnchor(array $blocks, ?string $needle): ?string
    {
        if (! $needle) {
            return null;
        }
        $b = self::findBlock($blocks, $needle);
        if (! $b) {
            return $needle;
        }

        return $b['key'] ?? $b['id'] ?? $needle;
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array{after?:string, before?:string}
     */
    public static function ici(array $ctx): array
    {
        $id = $ctx['cursor']['block'] ?? $ctx['selected_block'] ?? null;
        if (! $id) {
            return [];
        }
        if (($ctx['cursor']['position'] ?? 'after') === 'before') {
            return ['before' => $id];
        }

        return ['after' => $id];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function searchMedia(string $q): array
    {
        $n = self::fold($q);
        if ($n === '') {
            return array_values(array_filter(self::MEDIA, fn ($m) => $m['kind'] === 'image'));
        }

        return array_values(array_filter(self::MEDIA, function ($m) use ($n) {
            if (str_contains(self::fold($m['title']), $n)) {
                return true;
            }
            foreach ($m['tags'] as $t) {
                if (str_contains(self::fold($t), $n)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function searchPlaylist(string $q): array
    {
        $n = self::fold($q);

        return array_values(array_filter(self::PLAYLISTS, fn ($p) => str_contains(self::fold($p['title']), $n)));
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    public static function parse(string $message, array $blocks, array $ctx = []): array
    {
        $m = mb_strtolower(trim($message));

        if (preg_match('/rembourse|refund|supprime (le )?client|modifie (le )?paiement/u', $m)) {
            return GhostAction::blocked('Refusé. Remboursement, suppression de client, paiement : hors Ghost.', $blocks);
        }

        if (preg_match('/fiche.{0,20}(offre )?industrie|offre industrielle/u', $m)) {
            $ops = [];
            foreach (self::INDUSTRY as $f) {
                if (self::findBlock($blocks, $f['key'])) {
                    continue;
                }
                if ($f['type'] === 'image') {
                    $ops[] = ['op' => 'media.insert', 'media_id' => 'm-atelier-nuit', 'after' => 'presentation'];
                } elseif ($f['type'] === 'playlist') {
                    $ops[] = ['op' => 'playlist.insert', 'playlist_id' => 'pl-ambient', 'after' => 'video'];
                } else {
                    $ops[] = [
                        'op' => 'field.add',
                        'type' => $f['catalog'],
                        'name' => $f['label'],
                        'key' => $f['key'],
                        'value' => $f['value'] ?? null,
                    ];
                }
            }
            if (! $ops) {
                return [
                    'id' => GhostAction::id(),
                    'action' => 'template.apply',
                    'level' => GhostAction::OBSERVE,
                    'autonomy' => GhostAction::AUTO,
                    'ops' => [],
                    'preview' => ['La fiche industrie est déjà posée.'],
                    'before' => array_column($blocks, 'id'),
                    'status' => 'preview',
                ];
            }

            return GhostAction::make('template.apply', $ops, $blocks, 'Fiche offre industrie · '.count($ops).' modifications.');
        }

        if (preg_match('/playlist/u', $m) && preg_match('/ajoute|mets/u', $m)) {
            preg_match('/playlist\s+([a-z0-9][\w \-]*?)(?:\s+(?:sous|après|apres|ici)|$)/u', $m, $pl);
            $q = trim($pl[1] ?? (str_contains($m, 'ambient') ? 'Ambient 01' : ''));
            $hits = $q !== '' ? self::searchPlaylist($q) : self::PLAYLISTS;
            if (count($hits) !== 1) {
                return [
                    'id' => GhostAction::id(),
                    'action' => 'playlist.insert',
                    'level' => GhostAction::OBSERVE,
                    'autonomy' => GhostAction::AUTO,
                    'ops' => [],
                    'preview' => $hits ? array_column($hits, 'title') : ['Aucune playlist de ce nom.'],
                    'before' => array_column($blocks, 'id'),
                    'status' => 'preview',
                    'ask' => $hits ? 'Laquelle ?' : 'Quelle playlist ?',
                    'citations' => array_map(fn ($h) => ['label' => $h['title'], 'id' => $h['id']], $hits),
                ];
            }
            $place = self::ici($ctx);
            if (preg_match('/ici/u', $m) && empty($place['after']) && empty($place['before'])) {
                return self::askWhere('playlist.insert', $blocks);
            }
            preg_match('/(?:sous|après|apres)\s+(?:la |le |l[\'’])?([a-zàâéèêëïôùç ]+)/u', $m, $an);
            $raw = trim($an[1] ?? ($place['after'] ?? ''));
            $after = self::resolveAnchor($blocks, $raw) ?? ($raw !== '' ? $raw : null);

            return GhostAction::make('playlist.insert', [[
                'op' => 'playlist.insert',
                'playlist_id' => $hits[0]['id'],
                'after' => $after,
                'before' => $place['before'] ?? null,
            ]], $blocks, 'Ajouter « '.$hits[0]['title'].' »'.($after ? ' après '.$after : '').'.');
        }

        if (preg_match('/(image|photo)/u', $m) && preg_match('/ajoute|mets/u', $m)) {
            $place = self::ici($ctx);
            if (preg_match('/ici/u', $m) && empty($place['after']) && empty($place['before'])) {
                return self::askWhere('media.insert', $blocks);
            }
            [$q, $specific] = self::mediaQuery($m);
            $hits = $specific ? self::searchMedia($q) : array_values(array_filter(self::MEDIA, fn ($x) => $x['kind'] === 'image'));
            if (! $hits) {
                return [
                    'id' => GhostAction::id(),
                    'action' => 'media.insert',
                    'level' => GhostAction::OBSERVE,
                    'autonomy' => GhostAction::AUTO,
                    'ops' => [],
                    'preview' => ['Aucune image de ce nom dans le coffre.'],
                    'before' => array_column($blocks, 'id'),
                    'status' => 'preview',
                    'ask' => 'Aucune image de ce nom dans le coffre.',
                ];
            }
            if (count($hits) > 1 && ! $specific) {
                return [
                    'id' => GhostAction::id(),
                    'action' => 'media.insert',
                    'level' => GhostAction::OBSERVE,
                    'autonomy' => GhostAction::AUTO,
                    'ops' => [],
                    'preview' => array_column($hits, 'title'),
                    'before' => array_column($blocks, 'id'),
                    'status' => 'preview',
                    'ask' => 'J’ai trouvé plusieurs images. Laquelle ?',
                    'citations' => array_map(fn ($h) => ['label' => $h['title'], 'id' => $h['id']], $hits),
                ];
            }
            $chosen = $hits[0];
            preg_match('/(?:après|apres)\s+(?:la |le |l[\'’])?([a-zàâéèêëïôùç]+)/u', $m, $af);
            $after = self::resolveAnchor($blocks, $af[1] ?? null)
                ?? self::resolveAnchor($blocks, $place['after'] ?? null)
                ?? ($place['after'] ?? null);

            return GhostAction::make('media.insert', [[
                'op' => 'media.insert',
                'media_id' => $chosen['id'],
                'after' => $after,
                'before' => $place['before'] ?? null,
            ]], $blocks, 'Insérer « '.$chosen['title'].' »'.($after ? ' après '.$after : '').'.');
        }

        if (preg_match('/ajoute|crée|creer/u', $m) && preg_match('/champ|salaire|prix/u', $m)) {
            preg_match('/champ\s+([a-zàâéèêëïôùç]+)/u', $m, $nh);
            $name = isset($nh[1]) ? mb_strtoupper(mb_substr($nh[1], 0, 1)).mb_substr($nh[1], 1) : (preg_match('/salaire/u', $m) ? 'Salaire' : 'Champ');
            $key = Str::slug($name);
            if (self::findBlock($blocks, $key) || self::findBlock($blocks, $name)) {
                return [
                    'id' => GhostAction::id(),
                    'action' => 'field.add',
                    'level' => GhostAction::OBSERVE,
                    'autonomy' => GhostAction::AUTO,
                    'ops' => [],
                    'preview' => ['« '.$name.' » existe déjà.'],
                    'before' => array_column($blocks, 'id'),
                    'status' => 'preview',
                ];
            }
            $place = self::ici($ctx);
            if (preg_match('/ici/u', $m) && empty($place['after']) && empty($place['before'])) {
                return self::askWhere('field.add', $blocks);
            }
            preg_match('/(?:après|apres)\s+(?:le |la |l[\'’])?([a-zàâéèêëïôùç]+)/u', $m, $af);
            $after = self::resolveAnchor($blocks, $af[1] ?? null)
                ?? self::resolveAnchor($blocks, $place['after'] ?? null)
                ?? ($place['after'] ?? null);
            preg_match('/(\d{3,5})/u', $m, $val);
            $type = preg_match('/salaire|prix|€/u', self::fold($name)) ? 'digits' : 'text';

            return GhostAction::make('field.add', [[
                'op' => 'field.add',
                'type' => $type,
                'name' => $name,
                'key' => $key,
                'value' => $val[1] ?? null,
                'after' => $after,
            ]], $blocks, 'Ajouter '.$name.' · Prix / nombre'.($after ? ' après '.$after : '').'.');
        }

        return [
            'id' => GhostAction::id(),
            'action' => 'observe',
            'level' => GhostAction::OBSERVE,
            'autonomy' => GhostAction::AUTO,
            'ops' => [],
            'preview' => ['Je n’ai pas d’opération d’édition dans cette phrase. Un champ, une image, une playlist, une fiche.'],
            'before' => array_column($blocks, 'id'),
            'status' => 'preview',
        ];
    }

    /**
     * @return array{0:string,1:bool}
     */
    private static function mediaQuery(string $m): array
    {
        $cleaned = preg_replace("/['’`]/u", ' ', self::fold($m)) ?? $m;
        preg_match('/(?:photo|image)\s+(?:de\s+)?([a-z0-9 ]*?)(?:\s+(?:ici|apres|sous)|$)/u', $cleaned, $hit);
        $raw = trim($hit[1] ?? '');
        $raw = trim(preg_replace('/\b(ici|apres|sous|la|le|une|un|cette|cet|ce|l)\b/u', ' ', $raw) ?? $raw);

        return [$raw, mb_strlen($raw) > 1];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private static function askWhere(string $action, array $blocks): array
    {
        return [
            'id' => GhostAction::id(),
            'action' => $action,
            'level' => GhostAction::OBSERVE,
            'autonomy' => GhostAction::AUTO,
            'ops' => [],
            'preview' => ['« Ici » n’a pas d’ancre. Cliquez le curseur entre deux blocs.'],
            'before' => array_column($blocks, 'id'),
            'status' => 'preview',
            'ask' => 'Où ?',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $action
     * @return array{blocks: list<array<string, mixed>>, action: array<string, mixed>}
     */
    public static function apply(array $blocks, array $action): array
    {
        if (($action['status'] ?? '') === 'blocked' || empty($action['ops'])) {
            $action['status'] = 'blocked';

            return compact('blocks', 'action');
        }
        $next = $blocks;
        foreach ($action['ops'] as $op) {
            $next = self::runOp($next, $op);
        }
        $action['status'] = 'applied';
        $action['after'] = array_column($next, 'id');
        $action['result'] = 'Appliqué.';
        $action = GhostActionContract::observe($action, $next);
        $action = GhostActionContract::verifyTransition($action, $blocks, $next);

        return ['blocks' => $next, 'action' => $action];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $op
     * @return list<array<string, mixed>>
     */
    public static function runOp(array $blocks, array $op): array
    {
        $name = $op['op'] ?? '';
        if ($name === 'field.add') {
            $key = $op['key'] ?? Str::slug($op['name'] ?? 'champ');
            if (self::findBlock($blocks, $key)) {
                return $blocks;
            }
            $item = [
                'id' => 'b-'.$key,
                'type' => in_array($op['type'] ?? '', ['image', 'video'], true) ? $op['type'] : 'field',
                'key' => $key,
                'label' => $op['name'] ?? $key,
                'value' => $op['value'] ?? null,
            ];

            return self::insertAt($blocks, $item, $op['after'] ?? null, $op['before'] ?? null);
        }
        if ($name === 'media.insert') {
            $media = collect(self::MEDIA)->firstWhere('id', $op['media_id'] ?? '');
            $item = [
                'id' => 'b-'.($op['media_id'] ?? 'media'),
                'type' => 'image',
                'key' => $op['media_id'] ?? 'media',
                'label' => $media['title'] ?? 'Image',
                'mediaId' => $op['media_id'] ?? null,
            ];

            return self::insertAt(array_values(array_filter($blocks, fn ($b) => $b['id'] !== $item['id'])), $item, $op['after'] ?? null, $op['before'] ?? null);
        }
        if ($name === 'playlist.insert') {
            $pl = collect(self::PLAYLISTS)->firstWhere('id', $op['playlist_id'] ?? '');
            $item = [
                'id' => 'b-'.($op['playlist_id'] ?? 'pl'),
                'type' => 'playlist',
                'key' => $op['playlist_id'] ?? 'pl',
                'label' => $pl['title'] ?? 'Playlist',
                'playlistId' => $op['playlist_id'] ?? null,
            ];

            return self::insertAt(array_values(array_filter($blocks, fn ($b) => $b['id'] !== $item['id'])), $item, $op['after'] ?? null, $op['before'] ?? null);
        }
        if ($name === 'field.move') {
            $cur = self::findBlock($blocks, $op['target'] ?? '');
            if (! $cur) {
                return $blocks;
            }
            $rest = array_values(array_filter($blocks, fn ($b) => $b['id'] !== $cur['id']));

            return self::insertAt($rest, $cur, $op['after'] ?? null, $op['before'] ?? null);
        }
        if ($name === 'field.delete') {
            $cur = self::findBlock($blocks, $op['target'] ?? '');

            return $cur ? array_values(array_filter($blocks, fn ($b) => $b['id'] !== $cur['id'])) : $blocks;
        }

        return $blocks;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $item
     * @return list<array<string, mixed>>
     */
    public static function insertAt(array $blocks, array $item, ?string $after, ?string $before): array
    {
        $next = array_values(array_filter($blocks, fn ($b) => $b['id'] !== $item['id']));
        if ($after) {
            foreach ($next as $i => $b) {
                if (($b['id'] ?? '') === $after || ($b['key'] ?? '') === $after || self::fold($b['label'] ?? '') === self::fold($after)) {
                    array_splice($next, $i + 1, 0, [$item]);

                    return $next;
                }
            }
        }
        if ($before) {
            foreach ($next as $i => $b) {
                if (($b['id'] ?? '') === $before || ($b['key'] ?? '') === $before || self::fold($b['label'] ?? '') === self::fold($before)) {
                    array_splice($next, $i, 0, [$item]);

                    return $next;
                }
            }
        }
        $next[] = $item;

        return $next;
    }

    /**
     * @param  list<array<string, mixed>>  $current
     * @param  array<string, mixed>  $action
     * @param  list<array<string, mixed>>  $snapshot
     * @return list<array<string, mixed>>
     */
    public static function undo(array $current, array $action, array $snapshot): array
    {
        return ($action['status'] ?? '') === 'applied' ? $snapshot : $current;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function read(GpNode $node): array
    {
        if (! Schema::hasTable('cck_fields')) {
            return self::seed();
        }
        $rows = DB::table('cck_fields')->where('node_id', $node->id)->orderBy('sort')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return self::seed();
        }
        $out = [];
        foreach ($rows as $i => $r) {
            $type = match ($r->type) {
                'image', 'gallery' => 'image',
                'video' => 'video',
                'audio' => 'playlist',
                'html', 'textarea' => 'text',
                default => 'field',
            };
            $out[] = [
                'id' => 'b'.($r->id ?: $i),
                'type' => $type,
                'key' => $r->field_key ?: Str::slug($r->name),
                'label' => $r->name,
                'value' => $r->value,
                'db_id' => $r->id,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $action
     */
    public static function store(GpNode $node, array $action): void
    {
        if (! Schema::hasTable('ghost_actions')) {
            return;
        }
        DB::table('ghost_actions')->updateOrInsert(['id' => $action['id']], [
            'node_id' => $node->id,
            'actor_id' => Auth::id(),
            'action' => $action['action'],
            'level' => $action['level'],
            'autonomy' => $action['autonomy'],
            'ops' => json_encode($action['ops'] ?? [], JSON_UNESCAPED_UNICODE),
            'preview' => json_encode($action['preview'] ?? [], JSON_UNESCAPED_UNICODE),
            'before' => json_encode($action['before'] ?? [], JSON_UNESCAPED_UNICODE),
            'after' => isset($action['after']) ? json_encode($action['after'], JSON_UNESCAPED_UNICODE) : null,
            'status' => $action['status'],
            'result' => $action['result'] ?? null,
            'ask' => $action['ask'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function load(string $id): ?array
    {
        if (! Schema::hasTable('ghost_actions')) {
            return null;
        }
        $row = DB::table('ghost_actions')->where('id', $id)->first();
        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'action' => $row->action,
            'level' => $row->level,
            'autonomy' => $row->autonomy,
            'ops' => json_decode($row->ops ?: '[]', true) ?: [],
            'preview' => json_decode($row->preview ?: '[]', true) ?: [],
            'before' => json_decode($row->before ?: '[]', true) ?: [],
            'after' => $row->after ? json_decode($row->after, true) : null,
            'status' => $row->status,
            'result' => $row->result,
            'ask' => $row->ask,
            'snapshot' => $row->snapshot ? json_decode($row->snapshot, true) : null,
        ];
    }

    /**
     * Écrit les ops via le DSL. Jamais StudioController::cck.
     *
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>
     */
    public static function commit(GpNode $node, array $action): array
    {
        $action = GhostActionContract::authorize($action, GhostManifest::of($node));
        if (($action['status'] ?? '') === 'blocked') {
            self::store($node, $action);

            return $action;
        }
        $blocks = self::read($node);
        $snapshot = $blocks;
        if (in_array($action['action'] ?? '', ['campaign.create', 'campaign.launch', 'message.send', 'customers.segment', 'orders.filter'], true)) {
            $action['status'] = 'applied';
            $action['result'] = $action['action'] === 'campaign.launch' || ($action['action'] ?? '') === 'message.send'
                ? 'Envoyé (ledger).'
                : 'Préparé.';
            $action = GhostActionContract::observe($action, []);
            $action = GhostActionContract::verifyTransition($action, [], []);
            self::store($node, $action);
            GhostProvenance::record($node, $action, 'applied');

            return $action;
        }
        $out = self::apply($blocks, $action);
        self::writeBlocks($node, $out['blocks']);
        $out['action']['snapshot'] = $snapshot;
        self::store($node, $out['action']);
        if (Schema::hasTable('ghost_actions')) {
            DB::table('ghost_actions')->where('id', $out['action']['id'])->update([
                'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ]);
        }
        GhostProvenance::record($node, $out['action'], 'applied');

        return $out['action'];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>
     */
    public static function revert(GpNode $node, array $action): array
    {
        $snap = $action['snapshot'] ?? null;
        if (! is_array($snap)) {
            $action['status'] = 'blocked';
            $action['result'] = 'Pas de snapshot.';

            return $action;
        }
        self::writeBlocks($node, $snap);
        $action['status'] = 'undone';
        $action['result'] = 'Annulé.';
        $action['stage'] = GhostAction::OBSERVE;
        self::store($node, $action);
        GhostProvenance::record($node, $action, 'undone');

        return $action;
    }

    /**
     * Recalcule sort (10, 20, …). Ghost ne pose jamais sort = 17.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function writeBlocks(GpNode $node, array $blocks): void
    {
        $have = DB::table('cck_fields')->where('node_id', $node->id)->get()->keyBy('field_key');
        $keep = [];
        $sort = 10;
        foreach ($blocks as $b) {
            $key = $b['key'] ?? Str::slug($b['label'] ?? 'champ');
            $keep[] = $key;
            $type = match ($b['type'] ?? 'field') {
                'image' => 'image',
                'video' => 'video',
                'playlist' => 'audio',
                'text' => 'text',
                default => ($b['type'] === 'field' && ($key === 'salaire') ? 'digits' : 'text'),
            };
            if ($key === 'salaire') {
                $type = 'digits';
            }
            $row = [
                'node_id' => $node->id,
                'name' => $b['label'] ?? $key,
                'type' => $type,
                'value' => (string) ($b['value'] ?? $b['mediaId'] ?? $b['playlistId'] ?? ''),
                'sort' => $sort,
                'field_key' => $key,
                'seo_title' => $b['label'] ?? $key,
                'schema_version' => 1,
            ];
            $sort += 10;
            if (Schema::hasColumn('cck_fields', 'audience')) {
                $row['audience'] = 'fiche';
            }
            if (Schema::hasColumn('cck_fields', 'unit') && ! isset($row['unit'])) {
                $row['unit'] = '';
            }
            if ($have->has($key)) {
                DB::table('cck_fields')->where('id', $have[$key]->id)->update($row);
            } else {
                $row['target_kind'] = 'node';
                $row['target_id'] = '';
                $row['options'] = '';
                DB::table('cck_fields')->insert($row);
            }
        }
        if ($keep) {
            DB::table('cck_fields')->where('node_id', $node->id)->whereNotIn('field_key', $keep)->delete();
        }
    }

    public static function plantDemo(string $nodeId): void
    {
        if (DB::table('cck_fields')->where('node_id', $nodeId)->exists()) {
            return;
        }
        $sort = 10;
        foreach (self::seed() as $b) {
            $row = [
                'node_id' => $nodeId,
                'name' => $b['label'],
                'type' => $b['type'] === 'image' ? 'image' : ($b['key'] === 'salaire' ? 'digits' : 'text'),
                'value' => $b['value'] ?? ($b['mediaId'] ?? ''),
                'target_kind' => 'node',
                'target_id' => '',
                'sort' => $sort,
                'options' => '',
                'seo_title' => $b['label'],
                'field_key' => $b['key'],
                'schema_version' => 1,
            ];
            if (Schema::hasColumn('cck_fields', 'audience')) {
                $row['audience'] = 'fiche';
            }
            DB::table('cck_fields')->insert($row);
            $sort += 10;
        }
    }
}
