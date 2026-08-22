<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrowdGoal extends Model
{
    protected $primaryKey = 'node_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
