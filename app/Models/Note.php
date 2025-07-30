<?php

namespace App\Models;

use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = ['student_id', 'subject_id', 'value', 'term', 'date', 'comment', 'teacher_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
        public function classe()
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
