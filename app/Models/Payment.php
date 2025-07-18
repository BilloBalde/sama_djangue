<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['student_id', 'amount', 'method', 'status', 'transaction_id', 'payment_date'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
