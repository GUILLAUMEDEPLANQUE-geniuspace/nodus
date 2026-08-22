<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Edge extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function child(): BelongsTo
    {
        return $this->belongsTo(GpNode::class, 'to_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GpNode::class, 'from_id');
    }
}
