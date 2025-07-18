<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];
}
