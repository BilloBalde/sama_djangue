<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Portfolio extends Model
{
    protected $fillable = [ 'title', 'content'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
