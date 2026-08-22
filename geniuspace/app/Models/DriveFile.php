<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['locked' => 'bool'];
}
