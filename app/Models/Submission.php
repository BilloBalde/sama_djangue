<?php

namespace App\Models;

use App\Models\Student;
use App\Models\Assignment;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = ['student_id', 'assignment_id', 'content', 'submitted_at'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
