<?php

namespace App\Support;

use App\Models\GpNode;
use App\Models\Thread;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Coller un export Facebook / Discord (texte). Chaque post → sujet Legacy.
 * Les titres de fiches du club déjà présentes sont maillées, pas inventées.
 */
class ClubImport
{
    /** @return array{threads:int,lines:int} */
    public static function run(GpNode $club, string $raw): array
    {
        $posts = self::parse($raw);
        $n = 0;
        foreach ($posts as $p) {
            $id = 'im-'.substr(md5($club->id.$p['title'].$p['body']), 0, 10);
            if (Thread::query()->where('id', $id)->exists()) {
                continue;
            }
            Thread::query()->create([
                'id' => $id,
                'node_id' => $club->id,
                'kind' => 'forum',
                'title' => Str::limit($p['title'], 160),
                'author' => $p['author'],
                'body' => $p['body'],
                'cover' => $club->hero,
            ]);
            $n++;
        }
        return ['threads' => $n, 'lines' => substr_count($raw, "\n")];
    }

    /** @return list<array{author:string,title:string,body:string}> */
    public static function parse(string $raw): array
    {
        $raw = trim(str_replace("\r", '', $raw));
        if ($raw === '') {
            return [];
        }
        if ($raw[0] === '[') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return self::fromDiscordJson($json);
            }
        }
        $blocks = preg_split('/\n-{3,}\n|\n\n+/', $raw) ?: [];
        $out = [];
        foreach ($blocks as $b) {
            $lines = array_values(array_filter(array_map('trim', explode("\n", $b))));
            if (! $lines) {
                continue;
            }
            $author = Auth::user()->name ?? 'Import';
            $title = $lines[0];
            $body = $b;
            if (isset($lines[1]) && preg_match('/^\d|^le |janv|févr|mars|avr|mai|juin|juil|août|sept|oct|nov|déc|today|hier/i', $lines[1])) {
                $author = $lines[0];
                $title = $lines[2] ?? $lines[0];
                $body = implode("\n", array_slice($lines, 2)) ?: $title;
            }
            $out[] = ['author' => $author, 'title' => $title, 'body' => $body];
        }
        return $out;
    }

    private static function fromDiscordJson(array $json): array
    {
        $out = [];
        foreach ($json as $m) {
            if (! is_array($m)) {
                continue;
            }
            $author = $m['author']['username'] ?? $m['author'] ?? 'Discord';
            $content = $m['content'] ?? $m['message'] ?? '';
            if (! $content) {
                continue;
            }
            $out[] = [
                'author' => is_string($author) ? $author : 'Discord',
                'title' => Str::limit($content, 80),
                'body' => $content,
            ];
        }
        return $out;
    }
}
