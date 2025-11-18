<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'lead_id', 'first_name', 'last_name', 'email', 'phone', 
        'country_of_origin', 'city_of_residence', 'dob', 'languages', 'previous_courses',
        'initial_level', 'current_level', 'profile_notes'
    ];

    protected $casts = [
        'dob' => 'date',
        'languages' => 'array'
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
