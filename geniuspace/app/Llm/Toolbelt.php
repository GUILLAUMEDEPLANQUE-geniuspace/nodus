<?php

namespace App\Llm;

use App\Models\DriveFile;
use App\Models\Edge;
use App\Models\GpNode;
use App\Support\SignedMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Outils que le LLM (et le créateur) appellent.
 * Même contrat : JSON in → graphe / CCK / spatial / SEO out.
 * Grok n'invente pas le monde dans le chat : il dispatch ces fonctions.
 */
class Toolbelt
{
    /** Schéma function-calling (xAI / OpenAI compatible). */
    public static function schema(): array
    {
        return [
            ['name' => 'compile_world', 'description' => 'Noyau vide + SEO. Ne spawn rien. Le créateur pose les briques.', 'parameters' => ['prompt' => 'string', 'slug' => 'string']],
            ['name' => 'propose_nodes', 'description' => 'Lit le texte du créateur (liste) et suggère des nœuds, sans les créer.', 'parameters' => ['slug' => 'string', 'prompt' => 'string']],
            ['name' => 'spawn_spatial_node', 'description' => 'Crée un nœud 3D (job, crypto, video, character, shop, ship) + arête parent.', 'parameters' => ['slug' => 'string', 'type' => 'string', 'title' => 'string']],
            ['name' => 'link_parent_child', 'description' => 'Rayon parent/enfant dans edges.', 'parameters' => ['from' => 'string', 'to' => 'string']],
            ['name' => 'add_cck_field', 'description' => 'Ajoute un champ du catalogue CCK (image, geo, drip, og…).', 'parameters' => ['node_id' => 'string', 'type' => 'string', 'name' => 'string', 'value' => 'string']],
            ['name' => 'seed_cck_schema', 'description' => 'Pose le kit de champs d’un archétype (vera, rwa, fleet, anime).', 'parameters' => ['node_id' => 'string', 'archetype' => 'string']],
            ['name' => 'write_seo', 'description' => 'Title, description, OG, JSON-LD sur un nœud.', 'parameters' => ['node_id' => 'string', 'title' => 'string', 'description' => 'string']],
            ['name' => 'set_geo', 'description' => 'Épingle une carte (lat/lng).', 'parameters' => ['node_id' => 'string', 'lat' => 'number', 'lng' => 'number']],
            ['name' => 'set_drip', 'description' => 'Déverrouillage temporel d’un champ.', 'parameters' => ['field_id' => 'int', 'at' => 'string']],
            ['name' => 'attach_media', 'description' => 'Lie une image/vidéo Drive à un nœud + champ CCK SEO.', 'parameters' => ['node_id' => 'string', 'path' => 'string', 'kind' => 'string']],
        ];
    }

    public static function dispatch(string $name, array $args): mixed
    {
        return match ($name) {
            'compile_world' => WorldCompiler::run($args['slug'], $args['prompt'] ?? ''),
            'propose_nodes' => WorldCompiler::propose($args['slug'] ?? '', $args['prompt'] ?? ''),
            'spawn_spatial_node' => self::spawn($args),
            'link_parent_child' => self::link($args['from'], $args['to']),
            'add_cck_field' => self::field($args),
            'seed_cck_schema' => self::seedSchema($args['node_id'], $args['archetype'] ?? 'anime'),
            'write_seo' => self::seo($args),
            'set_geo' => self::geo($args),
            'set_drip' => self::drip($args),
            'attach_media' => self::media($args),
            default => ['error' => 'unknown tool '.$name],
        };
    }

    public static function spawn(array $a): array
    {
        $uni = GpNode::query()->where('slug', $a['slug'])->firstOrFail();
        $type = $a['type'] ?? 'shop';
        $title = $a['title'] ?? Str::title($type);
        $id = substr(md5($title.microtime()), 0, 12);
        $nslug = Str::slug($title).'-'.substr($id, 0, 4);
        GpNode::query()->create([
            'id' => $id, 'slug' => $nslug, 'kind' => $type === 'job' ? 'job' : ($type === 'character' ? 'character' : 'product'),
            'title' => $title, 'subtitle' => $type, 'summary' => $a['summary'] ?? 'Actif spatial.',
            'body' => '', 'hero' => $uni->hero, 'skin' => $uni->skin, 'featured' => false,
        ]);
        Edge::query()->create(['from_id' => $uni->id, 'to_id' => $id, 'kind' => 'parent_of', 'label' => $type]);
        $angle = (float) ($a['angle'] ?? (mt_rand(0, 628) / 100));
        $radius = (float) ($a['radius'] ?? (70 + mt_rand(0, 50)));
        DB::table('spatial_nodes')->insert([
            'universe_id' => $uni->id, 'node_id' => $id, 'kind' => $type,
            'x' => cos($angle) * $radius, 'y' => mt_rand(-8, 24), 'z' => sin($angle) * $radius,
            'radius' => $radius, 'angle' => $angle, 'speed' => 0.0015 + mt_rand(0, 4) / 2000,
        ]);
        self::seo(['node_id' => $id, 'title' => $title.' | '.$uni->title, 'description' => $title.' dans '.$uni->title]);
        return ['id' => $id, 'slug' => $nslug];
    }

    public static function link(string $from, string $to): array
    {
        Edge::query()->create(['from_id' => $from, 'to_id' => $to, 'kind' => 'parent_of', 'label' => 'rayon']);
        return ['ok' => true];
    }

    public static function field(array $a): array
    {
        $id = DB::table('cck_fields')->insertGetId([
            'node_id' => $a['node_id'],
            'name' => $a['name'] ?? 'Champ',
            'type' => $a['type'] ?? 'text',
            'value' => $a['value'] ?? '',
            'target_kind' => 'node',
            'target_id' => '',
            'sort' => 0,
            'options' => $a['options'] ?? '',
            'seo_title' => $a['seo_title'] ?? '',
            'drip_at' => $a['drip_at'] ?? null,
            'lat' => $a['lat'] ?? null,
            'lng' => $a['lng'] ?? null,
        ]);
        return ['id' => $id];
    }

    public static function seedSchema(string $nodeId, string $archetype): array
    {
        $out = [];
        foreach (CckCatalog::schemaFor($archetype) as [$name, $type]) {
            $out[] = self::field(['node_id' => $nodeId, 'name' => $name, 'type' => $type, 'value' => '']);
        }
        return $out;
    }

    public static function seo(array $a): array
    {
        DB::table('node_seo')->updateOrInsert(['node_id' => $a['node_id']], [
            'title' => $a['title'] ?? '',
            'description' => $a['description'] ?? '',
            'keywords' => $a['keywords'] ?? '',
            'noindex' => 0,
        ]);
        self::field([
            'node_id' => $a['node_id'], 'name' => 'Open Graph', 'type' => 'og',
            'value' => ($a['title'] ?? '').' | '.($a['description'] ?? ''),
        ]);
        return ['ok' => true];
    }

    public static function geo(array $a): array
    {
        return self::field([
            'node_id' => $a['node_id'], 'name' => 'Geo', 'type' => 'geo',
            'lat' => $a['lat'] ?? 48.85, 'lng' => $a['lng'] ?? 2.35,
        ]);
    }

    public static function drip(array $a): array
    {
        DB::table('cck_fields')->where('id', $a['field_id'])->update(['drip_at' => $a['at']]);
        return ['ok' => true];
    }

    public static function media(array $a): array
    {
        $kind = $a['kind'] ?? 'image';
        $type = $kind === 'video' ? 'video' : ($kind === 'audio' ? 'audio' : 'image');
        return self::field([
            'node_id' => $a['node_id'],
            'name' => 'Média '.$kind,
            'type' => $type,
            'value' => $a['path'],
            'seo_title' => basename($a['path']),
        ]);
    }

    public static function storeUpload(GpNode $node, UploadedFile $file): array
    {
        $path = SignedMedia::storeUpload($file);
        $url = '/'.$path;
        $mime = $file->getMimeType() ?: '';
        $kind = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'audio/') ? 'audio' : (str_starts_with($mime, 'image/') ? 'image' : 'file'));
        DriveFile::query()->create([
            'node_id' => $node->id,
            'title' => $file->getClientOriginalName(),
            'path' => $url,
            'kind' => $kind,
            'locked' => false,
            'mime' => $mime,
            'size' => $file->getSize() ?: 0,
        ]);
        self::media(['node_id' => $node->id, 'path' => $url, 'kind' => $kind]);
        if ($kind === 'image') {
            $node->hero = $url;
            $node->save();
        }
        return ['path' => $url, 'kind' => $kind];
    }
}
