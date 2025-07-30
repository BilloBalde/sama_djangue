<?php

namespace App\Models;

use App\Models\Schedule;
use App\Models\FeePeriod;
use App\Models\Attendance;
use App\Models\ClassModel;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function classes()
    {
        return $this->hasMany(ClassModel::class);
    }

    public function feePeriods()
    {
        return $this->hasMany(FeePeriod::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
