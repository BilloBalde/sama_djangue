<?php

namespace App\Models;

use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    protected $fillable = ['title', 'content', 'type', 'class_id'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}
