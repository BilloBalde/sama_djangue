<?php

namespace App\Models;

use App\Models\User;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
     protected $fillable = ['class_id', 'subject_id', 'teacher_id', 'day_of_week', 'start_time', 'end_time'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
