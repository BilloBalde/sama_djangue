<?php
namespace App\Models;

use App\Models\FeePeriod;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;

class ClassFee extends Model
{
    protected $fillable = ['class_id', 'fee_period_id', 'amount'];

    public function class()
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function feePeriod()
    {
        return $this->belongsTo(FeePeriod::class);
    }
}