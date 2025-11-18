<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'source', 'first_name', 'last_name', 'email', 'phone', 
        'country', 'languages', 'activity_notes'
    ];

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
