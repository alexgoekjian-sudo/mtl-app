<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'source', 'first_name', 'last_name', 'email', 'phone', 
        'country', 'languages', 'activity_notes', 'reference', 'source_detail'
    ];

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get all activities for this lead
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'related_entity', 'related_entity_type', 'related_entity_id');
    }

    /**
     * Add an activity to this lead
     */
    public function addActivity($type, $subject, $body, $userId = null)
    {
        return $this->activities()->create([
            'activity_type' => $type,
            'subject' => $subject,
            'body' => $body,
            'created_by_user_id' => $userId
        ]);
    }
}
