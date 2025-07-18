<?php

namespace App\Models;

use App\Models\Student;
use App\Models\Competency;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['student_id', 'competency_id', 'value', 'date', 'comment'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }
}
