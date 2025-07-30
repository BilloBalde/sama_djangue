<?php

namespace App\Models;

use App\Models\Note;
use App\Models\User;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassFee;
use App\Models\Schedule;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    protected $table = 'classes';
    protected $fillable = ['name', 'school_year_id', 'professeur_id'];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }


    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

     public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function professeur()
    {
        return $this->belongsTo(User::class, 'professeur_id');
    }

    public function classFees()
    {
        return $this->hasMany(ClassFee::class, 'class_id');
    }


}
