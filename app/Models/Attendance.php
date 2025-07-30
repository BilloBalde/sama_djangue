<?php
namespace App\Models;

use App\Models\Student;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['student_id', 'date', 'status', 'reason', 'school_year_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}