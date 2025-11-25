<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'student_id', 'course_offering_id', 'status', 'enrolled_at', 'dropped_at',
        'mid_course_level', 'mid_course_notes', 'is_trial', 'payment_override_reason',
        'transferred_from_enrollment_id', 'transferred_to_enrollment_id', 'historical_metadata'
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'dropped_at' => 'datetime',
        'is_trial' => 'boolean',
        'historical_metadata' => 'array'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id', 'student_id')
                    ->whereHas('session', function($q) {
                        $q->where('course_offering_id', $this->course_offering_id);
                    });
    }

    /**
     * Get all activities for this enrollment
     */
    public function activities()
    {
        return $this->morphMany(Activity::class, 'related_entity', 'related_entity_type', 'related_entity_id');
    }

    /**
     * Get the enrollment this was transferred from
     */
    public function transferredFrom()
    {
        return $this->belongsTo(Enrollment::class, 'transferred_from_enrollment_id');
    }

    /**
     * Get the enrollment this was transferred to
     */
    public function transferredTo()
    {
        return $this->belongsTo(Enrollment::class, 'transferred_to_enrollment_id');
    }

    /**
     * Scope for pending enrollments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for active enrollments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if enrollment has payment override
     */
    public function hasPaymentOverride()
    {
        return !empty($this->payment_override_reason);
    }

    /**
     * Activate enrollment (manual or payment confirmed)
     */
    public function activate($reason = null)
    {
        $this->status = 'active';
        if ($reason) {
            $this->payment_override_reason = $reason;
        }
        $this->save();

        // Log activity
        $this->activities()->create([
            'activity_type' => 'enrollment',
            'subject' => 'Enrollment Activated',
            'body' => $reason ? "Activated with override: {$reason}" : 'Activated via payment confirmation'
        ]);
    }
}
