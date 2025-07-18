<?php

namespace App\Models;

use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
          protected $fillable = ['name', 'description', 'class_id', 'subject_id', 'subject_id'];

          public function grades()
          {
              return $this->hasMany(Grade::class);
          }

          public function class()
          {
              return $this->belongsTo(ClassModel::class, 'class_id');
          }

          public function subject()
          {
              return $this->belongsTo(Subject::class);
          }
            
          public function student()
          {
              return $this->belongsTo(Student::class);
          }
}
