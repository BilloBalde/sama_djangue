<?php

namespace App\Models;

use App\Models\Note;
use App\Models\Schedule;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;


class Subject extends Model
{
        protected $fillable = ['name', 'class_id'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

     public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
