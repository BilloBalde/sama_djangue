<?php
namespace App\Models;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ClassFee;
use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Model;

class FeePeriod extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'due_date', 'school_year_id'];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function classFees()
    {
        return $this->hasMany(ClassFee::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }


    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}