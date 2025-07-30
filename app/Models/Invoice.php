<?php
namespace App\Models;

use App\Models\Payment;
use App\Models\Student;
use App\Models\FeePeriod;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['student_id', 'fee_period_id', 'amount', 'due_date', 'status'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feePeriod()
    {
        return $this->belongsTo(FeePeriod::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}