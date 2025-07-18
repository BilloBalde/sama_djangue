<?php

namespace App\Models;

use App\Models\Note;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
        protected $table = 'classes';
    protected $fillable = ['name', 'year'];

    public function students()
    {
        return $this->hasMany(Student::class);
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
}
