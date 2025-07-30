<?php

namespace App\Models;

use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    protected $fillable = ['student_id', 'invoice_id', 'amount', 'method', 
    'status', 'transaction_id', 'payment_date', 'created_by'];
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
