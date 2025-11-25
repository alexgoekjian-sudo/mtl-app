<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseOffering extends Model
{
    protected $table = 'course_offerings';

    protected $fillable = [
        'attendance_id','round','course_key','course_full_name','level','program','type','start_date','end_date','hours_total',
        'schedule','price','teacher_hourly_rate','classroom_cost','admin_overhead','capacity','location','online','book_included','course_book','status'
    ];

    protected $casts = [
        'schedule' => 'array',
        'online' => 'boolean',
        'book_included' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Scope: Only active courses
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Only upcoming courses (active and start date in future)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'active')
                     ->whereDate('start_date', '>', now());
    }

    /**
     * Scope: Only ongoing courses (active and currently running)
     */
    public function scopeOngoing($query)
    {
        return $query->where('status', 'active')
                     ->whereDate('start_date', '<=', now())
                     ->whereDate('end_date', '>=', now());
    }

    /**
     * Scope: Only completed courses
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed')
                     ->orWhere(function($q) {
                         $q->where('status', 'active')
                           ->whereDate('end_date', '<', now());
                     });
    }

    /**
     * Mark course as completed
     */
    public function markCompleted()
    {
        return $this->update(['status' => 'completed']);
    }

    /**
     * Mark course as cancelled
     */
    public function markCancelled()
    {
        return $this->update(['status' => 'cancelled']);
    }

    /**
     * Check if course is in progress
     */
    public function isOngoing()
    {
        return $this->status === 'active' 
            && $this->start_date <= now() 
            && $this->end_date >= now();
    }

    /**
     * Check if course has finished
     */
    public function hasEnded()
    {
        return $this->status === 'completed' 
            || ($this->status === 'active' && $this->end_date < now());
    }
}
