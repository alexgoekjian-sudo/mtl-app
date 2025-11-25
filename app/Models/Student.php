<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';

    protected $fillable = [
        'lead_id', 'first_name', 'last_name', 'email', 'phone', 
        'country_of_origin', 'city_of_residence', 'dob', 'languages', 'previous_courses',
        'initial_level', 'current_level', 'profile_notes', 'is_active'
    ];

    protected $casts = [
        'dob' => 'date',
        'languages' => 'array',
        'is_active' => 'boolean'
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

    /**
     * Get all activities for this student
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'related_entity', 'related_entity_type', 'related_entity_id');
    }

    /**
     * Add an activity to this student
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

    /**
     * Get current active enrollments
     */
    public function currentEnrollments()
    {
        return $this->enrollments()->whereIn('status', ['active', 'registered']);
    }

    /**
     * Get course history (completed and historical enrollments)
     */
    public function courseHistory()
    {
        return $this->enrollments()
            ->with('courseOffering')
            ->whereIn('status', ['completed', 'dropped'])
            ->orWhereHas('courseOffering', function($q) {
                $q->where('is_historical', true);
            })
            ->orderBy('enrolled_at', 'desc');
    }

    /**
     * Scope: Only active (non-archived) students
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Only archived students
     */
    public function scopeArchived($query)
    {
        return $query->where('is_active', 0);
    }

    /**
     * Archive this student
     */
    public function archive()
    {
        $this->update(['is_active' => 0]);
        $this->addActivity('admin', 'Student Archived', 'Student marked as inactive');
    }

    /**
     * Restore this student from archive
     */
    public function restore()
    {
        $this->update(['is_active' => 1]);
        $this->addActivity('admin', 'Student Restored', 'Student marked as active');
    }
}
