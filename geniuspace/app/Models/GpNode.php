<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Nœud du graphe. Lectures publiques (SEO). */
class GpNode extends Model
{
    protected $table = 'nodes';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['featured' => 'bool'];

    public function children(): HasMany
    {
        return $this->hasMany(Edge::class, 'from_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'node_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'node_id');
    }

    public function threads(): HasMany
    {
        return $this->hasMany(Thread::class, 'node_id');
    }

    public function wiki(): HasMany
    {
        return $this->hasMany(WikiPage::class, 'node_id');
    }

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class, 'node_id')->orderBy('step');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NodeI18n::class, 'node_id');
    }

    public function localized(string $locale): self
    {
        $tr = $this->translations->firstWhere('locale', $locale);
        if ($tr) {
            $this->title = $tr->title;
            $this->summary = $tr->summary ?: $this->summary;
        }
        return $this;
    }

    public function seoTitle(): string
    {
        return match ($this->kind) {
            'auto' => "{$this->title} — garage, fiches, pièces | Geniuspace",
            'company' => "{$this->title} — maison, salon, épreuves | Geniuspace",
            'product' => "{$this->title} — boutique | Geniuspace",
            default => "{$this->title} — {$this->kind} | Geniuspace",
        };
    }
}
