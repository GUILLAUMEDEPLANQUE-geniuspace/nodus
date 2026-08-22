<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Thread extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function replies(): HasMany
    {
        return $this->hasMany(Reply::class, 'thread_id');
    }

    public function live(): HasMany
    {
        return $this->hasMany(LiveMessage::class, 'thread_id');
    }
}
