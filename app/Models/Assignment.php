<?php

namespace App\Models;

use App\Models\Student;

use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'class_id',
        'subject_id',
        'student_id',
    ];

    /**
     * Relation avec la classe (school class)
     */
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Relation avec la matière (subject)
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Relation avec l'élève (student)
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}

