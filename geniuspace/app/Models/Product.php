<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];
    protected $casts = ['rwa' => 'bool', 'votes' => 'int', 'energy' => 'int'];

    public function node(): BelongsTo
    {
        return $this->belongsTo(GpNode::class, 'node_id');
    }

    public function priceAmount(): string
    {
        return preg_replace('/[^\d.,]/', '', $this->price) ?: '0';
    }
}
