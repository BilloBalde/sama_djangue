<?php

namespace App\Models;

use App\Models\Note;
use App\Models\User;
use App\Models\Grade;
use App\Models\Payment;
use App\Models\ClassModel;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
     use HasRoles;

    protected $fillable = ['first_name', 'last_name', 'email', 'birth_date', 'class_id', 'tutor_id'];

    protected $guard_name = 'sanctum'; 
    
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
      
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classe()
{
    return $this->belongsTo(ClassModel::class);
}

}
